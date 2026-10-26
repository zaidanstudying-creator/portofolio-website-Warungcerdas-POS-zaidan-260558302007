<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\ArsipTransaksi;
use App\Models\Order;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ArchiveController extends Controller
{
    /**
     * Archive dashboard - list and create archives
     */
    public function index(Request $request): View
    {
        $archives = ArsipTransaksi::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('archive.index', compact('archives'));
    }

    /**
     * Show preview of archive before creating
     */
    public function preview(Request $request): View
    {
        $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_akhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_filter' => ['required', 'in:all,paid,unpaid,cancelled'],
            'format' => ['required', 'in:zip,csv,pdf'],
        ]);

        $startDate = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $endDate = Carbon::parse($request->tanggal_akhir)->endOfDay();
        $statusFilter = $request->status_filter;
        $format = $request->format;

        $query = $this->buildArchiveQuery($startDate, $endDate, $statusFilter);

        $recordCount = (int) $query->count();
        $totalNominal = (float) $query->sum('total');

        // Get sample data (first 10 records)
        $sampleOrders = $query->with(['items.toppings', 'user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('archive.preview', compact(
            'startDate',
            'endDate',
            'statusFilter',
            'format',
            'recordCount',
            'totalNominal',
            'sampleOrders'
        ));
    }

    /**
     * Create and download archive
     */
    public function create(Request $request): StreamedResponse|RedirectResponse|BinaryFileResponse
    {
        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_akhir' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status_filter' => ['required', 'in:all,paid,unpaid,cancelled'],
            'format' => ['required', 'in:zip,csv,pdf'],
        ]);

        $startDate = Carbon::parse($validated['tanggal_mulai'])->startOfDay();
        $endDate = Carbon::parse($validated['tanggal_akhir'])->endOfDay();
        $statusFilter = $validated['status_filter'];
        $format = $validated['format'];

        // Limit: max 1 year range, max 50000 records
        if ($startDate->diffInDays($endDate) > 366) {
            return back()->withErrors(['tanggal_akhir' => 'Rentang tanggal maksimal 1 tahun.']);
        }

        $query = $this->buildArchiveQuery($startDate, $endDate, $statusFilter);
        $recordCount = (int) $query->count();

        if ($recordCount > 50000) {
            return back()->withErrors(['tanggal_akhir' => 'Terlalu banyak data (maksimal 50.000 transaksi). Perkecil rentang tanggal.']);
        }

        if ($recordCount === 0) {
            return back()->withErrors(['tanggal_mulai' => 'Tidak ada transaksi pada rentang tanggal dan filter tersebut.']);
        }

        $totalNominal = (float) $query->sum('total');

        // Generate archive
        $filename = $this->generateArchiveFilename($startDate, $endDate, $statusFilter, $format);
        $tempPath = storage_path('app/temp/'.$filename);
        $archiveRecord = null;

        try {
            // Ensure temp directory exists
            if (! is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            // Generate based on format
            $checksum = match ($format) {
                'zip' => $this->generateZipArchive($query, $tempPath, $startDate, $endDate, $statusFilter),
                'csv' => $this->generateCsvArchive($query, $tempPath, $startDate, $endDate, $statusFilter),
                'pdf' => $this->generatePdfArchive($query, $tempPath, $startDate, $endDate, $statusFilter),
            };

            // Store archive record
            $archiveRecord = ArsipTransaksi::create([
                'tanggal_mulai' => $startDate,
                'tanggal_akhir' => $endDate,
                'status_filter' => $statusFilter,
                'format' => $format,
                'record_count' => $recordCount,
                'total_nominal' => $totalNominal,
                'checksum' => $checksum,
                'file_path' => 'archives/'.$filename,
                'user_id' => $request->user()->id,
            ]);

            // Move to permanent storage
            Storage::disk('local')->putFileAs('archives', new File($tempPath), $filename);
            unlink($tempPath);

            // Return download
            return response()->download(
                Storage::disk('local')->path('archives/'.$filename),
                $filename,
                [
                    'Content-Type' => $this->getMimeType($format),
                    'Cache-Control' => 'no-store, private',
                ]
            )->deleteFileAfterSend(false);

        } catch (\Throwable $e) {
            // Cleanup on error
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            if ($archiveRecord) {
                $archiveRecord->delete();
            }

            return back()->withErrors(['error' => 'Gagal membuat arsip: '.$e->getMessage()]);
        }
    }

    /**
     * Download existing archive
     */
    public function download(ArsipTransaksi $arsip): StreamedResponse|BinaryFileResponse
    {
        $path = 'archives/'.basename($arsip->file_path);

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'File arsip tidak ditemukan.');
        }

        $filename = basename($arsip->file_path);

        return response()->download(
            Storage::disk('local')->path($path),
            $filename,
            [
                'Content-Type' => $this->getMimeType($arsip->format),
                'Cache-Control' => 'no-store, private',
            ]
        )->deleteFileAfterSend(false);
    }

    /**
     * Delete archive record (not the transactions)
     */
    public function destroy(ArsipTransaksi $arsip): RedirectResponse
    {
        $path = 'archives/'.basename($arsip->file_path);

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $arsip->delete();

        return redirect()->route('archive.index')->with('success', 'Riwayat arsip berhasil dihapus.');
    }

    /**
     * Build the base query for archive
     */
    private function buildArchiveQuery(Carbon $startDate, Carbon $endDate, string $statusFilter)
    {
        $query = Order::query()
            ->with(['items.toppings', 'user'])
            ->whereBetween('created_at', [
                $startDate->copy()->setTimezone(config('app.timezone')),
                $endDate->copy()->setTimezone(config('app.timezone')),
            ]);

        if ($statusFilter !== 'all') {
            if ($statusFilter === 'paid') {
                $query->where('payment_status', PaymentStatus::Paid)
                    ->where('status', '!=', OrderStatus::Cancelled);
            } elseif ($statusFilter === 'unpaid') {
                $query->where('payment_status', PaymentStatus::Unpaid);
            } elseif ($statusFilter === 'cancelled') {
                $query->where('status', OrderStatus::Cancelled);
            }
        }

        return $query;
    }

    /**
     * Generate archive filename
     */
    private function generateArchiveFilename(Carbon $startDate, Carbon $endDate, string $statusFilter, string $format): string
    {
        $period = $startDate->format('Ymd').'-'.$endDate->format('Ymd');
        $timestamp = now()->format('His');
        $ext = match ($format) {
            'zip' => 'zip',
            'csv' => 'csv',
            'pdf' => 'pdf',
        };

        return "arsip-transaksi-{$period}-{$statusFilter}-{$timestamp}.{$ext}";
    }

    /**
     * Get MIME type for format
     */
    private function getMimeType(string $format): string
    {
        return match ($format) {
            'zip' => 'application/zip',
            'csv' => 'text/csv; charset=UTF-8',
            'pdf' => 'application/pdf',
        };
    }

    /**
     * Generate ZIP archive with CSV + manifest
     */
    private function generateZipArchive($query, string $tempPath, Carbon $startDate, Carbon $endDate, string $statusFilter): string
    {
        $csvPath = $tempPath.'.csv';
        $manifestPath = $tempPath.'.manifest.json';

        // Generate CSV
        $this->generateCsvArchive($query, $csvPath, $startDate, $endDate, $statusFilter);

        // Generate manifest
        $recordCount = (int) $query->count();
        $totalNominal = (float) $query->sum('total');

        $manifest = [
            'version' => '1.0',
            'created_at' => now()->toISOString(),
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'status_filter' => $statusFilter,
            'record_count' => $recordCount,
            'total_nominal' => $totalNominal,
            'files' => [
                'transactions.csv' => [
                    'size' => filesize($csvPath),
                    'records' => $recordCount,
                ],
            ],
        ];

        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Create ZIP
        $zip = new ZipArchive;
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file ZIP.');
        }

        $zip->addFile($csvPath, 'transaksi.csv');
        $zip->addFile($manifestPath, 'manifest.json');
        $zip->close();

        // Cleanup temp files
        unlink($csvPath);
        unlink($manifestPath);

        // Calculate SHA256 checksum
        return hash_file('sha256', $tempPath);
    }

    /**
     * Generate CSV archive
     */
    private function generateCsvArchive($query, string $tempPath, Carbon $startDate, Carbon $endDate, string $statusFilter): string
    {
        $handle = fopen($tempPath, 'w');

        // BOM for UTF-8
        fwrite($handle, "\xEF\xBB\xBF");

        // Headers
        fputcsv($handle, [
            'No',
            'Nomor Transaksi',
            'Waktu',
            'Tipe Pesanan',
            'Customer',
            'Meja/Platform',
            'Subtotal',
            'Kode Promo',
            'Promo',
            'Diskon',
            'Biaya Tambahan',
            'Biaya Antar',
            'Komisi Platform',
            'Total',
            'Metode Pembayaran',
            'Status Pembayaran',
            'Status Pesanan',
            'Pencatat',
            'Catatan',
        ]);

        $orders = $query->orderByDesc('created_at')->get();
        $rowNum = 1;

        foreach ($orders as $order) {
            $items = $order->items;
            $totalToppingPrice = $items->sum(fn ($item) => $item->toppings->sum(fn ($t) => $t->price * $t->quantity));
            $totalDiscount = $order->discount_amount ?? 0;
            $totalFee = $order->additional_fee_amount ?? 0;
            $deliveryFee = $order->delivery_fee ?? 0;
            $platformCommission = $order->platform_commission ?? 0;

            fputcsv($handle, [
                $rowNum++,
                $order->order_number,
                $order->created_at->timezone(OrderService::LOCAL_TIMEZONE)->format('d/m/Y H:i'),
                $order->type->label(),
                $order->customer_name ?? '-',
                $this->getOrderLocation($order),
                $order->subtotal - $totalToppingPrice,
                $order->promo_code ?? '-',
                $order->promo_amount ?? 0,
                $totalDiscount,
                $totalFee,
                $deliveryFee,
                $platformCommission,
                $order->total,
                $order->payment_method?->value ?? '-',
                $order->payment_status->label() ?? $order->payment_status->value,
                $order->status->label() ?? $order->status->value,
                $order->user?->name ?? '-',
                $order->notes ?? '-',
            ]);
        }

        fclose($handle);

        return hash_file('sha256', $tempPath);
    }

    /**
     * Get order location string
     */
    private function getOrderLocation(Order $order): string
    {
        return match ($order->type->value) {
            'DI' => 'Meja: '.($order->table_number ?? '-'),
            'DLV' => $order->delivery_platform.': '.($order->delivery_address ?? '-'),
            default => '-',
        };
    }

    /**
     * Generate PDF archive
     */
    private function generatePdfArchive($query, string $tempPath, Carbon $startDate, Carbon $endDate, string $statusFilter): string
    {
        $orders = $query->orderByDesc('created_at')->get();
        $recordCount = $orders->count();
        $totalNominal = (float) $orders->sum('total');

        $pdf = Pdf::loadView('archive.pdf', compact(
            'orders',
            'startDate',
            'endDate',
            'statusFilter',
            'recordCount',
            'totalNominal'
        ))->setPaper('a4', 'landscape');

        $pdf->save($tempPath);

        return hash_file('sha256', $tempPath);
    }
}
