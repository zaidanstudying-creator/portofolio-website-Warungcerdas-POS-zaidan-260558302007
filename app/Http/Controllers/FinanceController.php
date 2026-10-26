<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Requests\StoreKategoriPengeluaranRequest;
use App\Http\Requests\StorePemasukanLainRequest;
use App\Http\Requests\StorePengeluaranRequest;
use App\Http\Requests\UpdateKategoriPengeluaranRequest;
use App\Http\Requests\UpdatePemasukanLainRequest;
use App\Http\Requests\UpdatePengeluaranRequest;
use App\Models\KategoriPengeluaran;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PemasukanLain;
use App\Models\Pengeluaran;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    /**
     * Finance dashboard - ringkasan keuangan
     */
    public function index(Request $request): View
    {
        $period = $request->query('period', 'today');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        [$resolvedStart, $resolvedEnd] = $this->resolveDateRange($period, $startDate, $endDate);

        // Penjualan (dari Order)
        $validOrdersQuery = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [
                $resolvedStart->copy()->setTimezone(config('app.timezone')),
                $resolvedEnd->copy()->setTimezone(config('app.timezone')),
            ]);

        $totalPenjualan = (float) $validOrdersQuery->sum('total');
        $totalDiskon = (float) $validOrdersQuery->sum('discount_amount');
        $totalBiayaTambahan = (float) $validOrdersQuery->sum('additional_fee_amount');
        $totalDeliveryFee = (float) $validOrdersQuery->sum('delivery_fee');
        $totalPlatformCommission = (float) $validOrdersQuery->sum('platform_commission');

        // HPP
        $hppData = $this->calculateHpp($resolvedStart, $resolvedEnd);
        $totalHpp = $hppData['total_hpp'];

        // Pemasukan Lain
        $pemasukanLainQuery = PemasukanLain::query()
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')]);
        $totalPemasukanLain = (float) $pemasukanLainQuery->sum('nominal');
        $pemasukanModal = (float) (clone $pemasukanLainQuery)->where('kategori', 'modal')->sum('nominal');
        $pemasukanLainnya = (float) (clone $pemasukanLainQuery)->where('kategori', 'lain')->sum('nominal');

        // Pengeluaran
        $pengeluaranQuery = Pengeluaran::query()
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')]);
        $totalPengeluaran = (float) $pengeluaranQuery->sum('nominal');

        // Pengeluaran per kategori
        $pengeluaranPerKategori = $pengeluaranQuery->clone()
            ->join('kategori_pengeluaran', 'kategori_pengeluaran.id', '=', 'pengeluaran.kategori_pengeluaran_id')
            ->selectRaw('kategori_pengeluaran.nama, SUM(pengeluaran.nominal) as total')
            ->groupBy('kategori_pengeluaran.nama')
            ->orderByDesc('total')
            ->get();

        // Perhitungan laba
        $omzetBersih = $totalPenjualan;
        $labaKotor = $omzetBersih - $totalHpp;
        $labaOperasional = $labaKotor - $totalPengeluaran;
        $marginLabaOperasional = $omzetBersih > 0 ? ($labaOperasional / $omzetBersih) * 100 : 0;

        // Arus kas
        $kasMasuk = $totalPenjualan + $totalPemasukanLain;
        $kasKeluar = $totalPengeluaran;
        $saldoAkhir = $kasMasuk - $kasKeluar;

        // Harian untuk grafik
        $dailyData = $this->getDailyFinanceData($resolvedStart, $resolvedEnd);

        // HPP warning
        $productsWithoutHpp = $hppData['products_without_hpp'];
        $toppingsWithoutHpp = $hppData['toppings_without_hpp'];
        $hasIncompleteHpp = $productsWithoutHpp > 0 || $toppingsWithoutHpp > 0;

        return view('finance.index', compact(
            'period',
            'startDate',
            'endDate',
            'resolvedStart',
            'resolvedEnd',
            'totalPenjualan',
            'totalDiskon',
            'totalBiayaTambahan',
            'totalDeliveryFee',
            'totalPlatformCommission',
            'totalHpp',
            'totalPemasukanLain',
            'pemasukanModal',
            'pemasukanLainnya',
            'totalPengeluaran',
            'pengeluaranPerKategori',
            'omzetBersih',
            'labaKotor',
            'labaOperasional',
            'marginLabaOperasional',
            'kasMasuk',
            'kasKeluar',
            'saldoAkhir',
            'dailyData',
            'hasIncompleteHpp',
            'productsWithoutHpp',
            'toppingsWithoutHpp',
        ));
    }

    /**
     * Kategori Pengeluaran - Index
     */
    public function kategoriIndex(): View
    {
        $kategoris = KategoriPengeluaran::orderBy('sort_order')->paginate(15);

        return view('finance.kategori.index', compact('kategoris'));
    }

    public function kategoriCreate(): View
    {
        return view('finance.kategori.create');
    }

    public function kategoriStore(StoreKategoriPengeluaranRequest $request): RedirectResponse
    {
        KategoriPengeluaran::create($request->validated());

        return redirect()->route('finance.kategori.index')->with('success', 'Kategori pengeluaran berhasil ditambahkan.');
    }

    public function kategoriEdit(KategoriPengeluaran $kategori): View
    {
        return view('finance.kategori.edit', compact('kategori'));
    }

    public function kategoriUpdate(UpdateKategoriPengeluaranRequest $request, KategoriPengeluaran $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return redirect()->route('finance.kategori.index')->with('success', 'Kategori pengeluaran berhasil diperbarui.');
    }

    public function kategoriDestroy(KategoriPengeluaran $kategori): RedirectResponse
    {
        if ($kategori->pengeluaran()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki data pengeluaran.');
        }
        $kategori->delete();

        return redirect()->route('finance.kategori.index')->with('success', 'Kategori pengeluaran berhasil dihapus.');
    }

    /**
     * Pengeluaran - Index
     */
    public function pengeluaranIndex(Request $request): View
    {
        $kategoris = KategoriPengeluaran::active()->get();
        $period = $request->query('period', 'today');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $kategoriId = $request->query('kategori_id');

        [$resolvedStart, $resolvedEnd] = $this->resolveDateRange($period, $startDate, $endDate);

        $pengeluarans = Pengeluaran::query()
            ->with(['kategori', 'user'])
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
            ->when($kategoriId, fn ($q) => $q->where('kategori_pengeluaran_id', $kategoriId))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $total = (float) Pengeluaran::query()
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
            ->when($kategoriId, fn ($q) => $q->where('kategori_pengeluaran_id', $kategoriId))
            ->sum('nominal');

        return view('finance.pengeluaran.index', compact(
            'pengeluarans',
            'kategoris',
            'period',
            'startDate',
            'endDate',
            'kategoriId',
            'resolvedStart',
            'resolvedEnd',
            'total'
        ));
    }

    public function pengeluaranCreate(): View
    {
        $kategoris = KategoriPengeluaran::active()->get();

        return view('finance.pengeluaran.create', compact('kategoris'));
    }

    public function pengeluaranStore(StorePengeluaranRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('bukti')) {
            $data['bukti_path'] = $request->file('bukti')->store('pengeluaran', 'public');
        }

        Pengeluaran::create($data);

        return redirect()->route('finance.pengeluaran.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function pengeluaranEdit(Pengeluaran $pengeluaran): View
    {
        $kategoris = KategoriPengeluaran::active()->get();

        return view('finance.pengeluaran.edit', compact('pengeluaran', 'kategoris'));
    }

    public function pengeluaranUpdate(UpdatePengeluaranRequest $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('bukti')) {
            if ($pengeluaran->bukti_path) {
                Storage::disk('public')->delete($pengeluaran->bukti_path);
            }
            $data['bukti_path'] = $request->file('bukti')->store('pengeluaran', 'public');
        } elseif ($request->boolean('remove_bukti')) {
            if ($pengeluaran->bukti_path) {
                Storage::disk('public')->delete($pengeluaran->bukti_path);
            }
            $data['bukti_path'] = null;
        }

        $pengeluaran->update($data);

        return redirect()->route('finance.pengeluaran.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function pengeluaranDestroy(Pengeluaran $pengeluaran): RedirectResponse
    {
        if ($pengeluaran->bukti_path) {
            Storage::disk('public')->delete($pengeluaran->bukti_path);
        }
        $pengeluaran->delete();

        return redirect()->route('finance.pengeluaran.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }

    /**
     * Pemasukan Lain - Index
     */
    public function pemasukanLainIndex(Request $request): View
    {
        $period = $request->query('period', 'today');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $kategori = $request->query('kategori');

        [$resolvedStart, $resolvedEnd] = $this->resolveDateRange($period, $startDate, $endDate);

        $pemasukanLains = PemasukanLain::query()
            ->with('user')
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $total = (float) PemasukanLain::query()
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->sum('nominal');

        $totalModal = (float) PemasukanLain::query()
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
            ->where('kategori', 'modal')
            ->sum('nominal');

        $totalLain = (float) PemasukanLain::query()
            ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
            ->where('kategori', 'lain')
            ->sum('nominal');

        return view('finance.pemasukan-lain.index', compact(
            'pemasukanLains',
            'period',
            'startDate',
            'endDate',
            'kategori',
            'resolvedStart',
            'resolvedEnd',
            'total',
            'totalModal',
            'totalLain'
        ));
    }

    public function pemasukanLainCreate(): View
    {
        return view('finance.pemasukan-lain.create');
    }

    public function pemasukanLainStore(StorePemasukanLainRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        PemasukanLain::create($data);

        return redirect()->route('finance.pemasukan-lain.index')->with('success', 'Pemasukan lain berhasil dicatat.');
    }

    public function pemasukanLainEdit(PemasukanLain $pemasukanLain): View
    {
        return view('finance.pemasukan-lain.edit', compact('pemasukanLain'));
    }

    public function pemasukanLainUpdate(UpdatePemasukanLainRequest $request, PemasukanLain $pemasukanLain): RedirectResponse
    {
        $pemasukanLain->update($request->validated());

        return redirect()->route('finance.pemasukan-lain.index')->with('success', 'Pemasukan lain berhasil diperbarui.');
    }

    public function pemasukanLainDestroy(PemasukanLain $pemasukanLain): RedirectResponse
    {
        $pemasukanLain->delete();

        return redirect()->route('finance.pemasukan-lain.index')->with('success', 'Pemasukan lain berhasil dihapus.');
    }

    /**
     * Export CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $period = $request->query('period', 'today');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        [$resolvedStart, $resolvedEnd] = $this->resolveDateRange($period, $startDate, $endDate);

        $filename = 'laporan-keuangan-'.$period.'-'.$resolvedStart->format('Ymd').'-'.$resolvedEnd->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($resolvedStart, $resolvedEnd): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            // Header
            fputcsv($handle, [
                'Tanggal',
                'Kategori',
                'Sub Kategori',
                'Nominal',
                'Metode Pembayaran',
                'Keterangan',
                'Pencatat',
                'Tipe',
            ]);

            // Penjualan
            $orders = Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->where('status', '!=', 'cancelled')
                ->whereBetween('created_at', [
                    $resolvedStart->copy()->setTimezone(config('app.timezone')),
                    $resolvedEnd->copy()->setTimezone(config('app.timezone')),
                ])
                ->with('user')
                ->orderBy('created_at')
                ->get();

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->created_at->timezone(OrderService::LOCAL_TIMEZONE)->format('Y-m-d'),
                    'Penjualan',
                    $order->type->label(),
                    $order->total,
                    $order->payment_method?->value ?? '-',
                    $order->notes ?? '-',
                    $order->user?->name ?? '-',
                    'Masuk',
                ]);
            }

            // Pemasukan Lain
            $pemasukanLain = PemasukanLain::query()
                ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
                ->with('user')
                ->orderBy('tanggal')
                ->get();

            foreach ($pemasukanLain as $item) {
                fputcsv($handle, [
                    $item->tanggal->format('Y-m-d'),
                    'Pemasukan Lain',
                    ucfirst($item->kategori),
                    $item->nominal,
                    $item->metode_pembayaran,
                    $item->keterangan ?? '-',
                    $item->user?->name ?? '-',
                    'Masuk',
                ]);
            }

            // Pengeluaran
            $pengeluaran = Pengeluaran::query()
                ->whereBetween('tanggal', [$resolvedStart->format('Y-m-d'), $resolvedEnd->format('Y-m-d')])
                ->with(['kategori', 'user'])
                ->orderBy('tanggal')
                ->get();

            foreach ($pengeluaran as $item) {
                fputcsv($handle, [
                    $item->tanggal->format('Y-m-d'),
                    'Pengeluaran',
                    $item->kategori->nama,
                    $item->nominal,
                    $item->metode_pembayaran,
                    $item->keterangan ?? '-',
                    $item->user?->name ?? '-',
                    'Keluar',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Calculate HPP for date range
     */
    private function calculateHpp(Carbon $startDate, Carbon $endDate): array
    {
        $orderItems = OrderItem::query()
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('payment_status', PaymentStatus::Paid)
                    ->where('status', '!=', 'cancelled')
                    ->whereBetween('created_at', [
                        $startDate->copy()->setTimezone(config('app.timezone')),
                        $endDate->copy()->setTimezone(config('app.timezone')),
                    ]);
            })
            ->with(['toppings'])
            ->get();

        $productHpp = 0.0;
        $toppingHpp = 0.0;
        $productsWithoutHpp = 0;
        $toppingsWithoutHpp = 0;
        $seenProductsWithoutHpp = [];
        $seenToppingsWithoutHpp = [];

        foreach ($orderItems as $item) {
            $quantity = $item->quantity;

            if ($item->hpp !== null) {
                $productHpp += (float) $item->hpp * $quantity;
            } else {
                if (! in_array($item->product_id, $seenProductsWithoutHpp)) {
                    $seenProductsWithoutHpp[] = $item->product_id;
                    $productsWithoutHpp++;
                }
            }

            foreach ($item->toppings as $topping) {
                $toppingQty = $topping->quantity * $quantity;
                if ($topping->hpp !== null) {
                    $toppingHpp += (float) $topping->hpp * $toppingQty;
                } else {
                    if (! in_array($topping->topping_id, $seenToppingsWithoutHpp)) {
                        $seenToppingsWithoutHpp[] = $topping->topping_id;
                        $toppingsWithoutHpp++;
                    }
                }
            }
        }

        return [
            'total_hpp' => $productHpp + $toppingHpp,
            'product_hpp' => $productHpp,
            'topping_hpp' => $toppingHpp,
            'products_without_hpp' => $productsWithoutHpp,
            'toppings_without_hpp' => $toppingsWithoutHpp,
        ];
    }

    /**
     * Get daily finance data for charts
     */
    private function getDailyFinanceData(Carbon $startDate, Carbon $endDate): array
    {
        $data = [];

        for ($day = $startDate->copy(); $day <= $endDate; $day->addDay()) {
            $dateKey = $day->format('Y-m-d');

            // Penjualan hari ini
            $penjualan = (float) Order::query()
                ->where('payment_status', PaymentStatus::Paid)
                ->where('status', '!=', 'cancelled')
                ->whereDate('created_at', $dateKey)
                ->sum('total');

            // HPP hari ini
            $hppData = $this->calculateHpp($day->copy()->startOfDay(), $day->copy()->endOfDay());
            $hpp = $hppData['total_hpp'];

            // Pengeluaran hari ini
            $pengeluaran = (float) Pengeluaran::query()
                ->whereDate('tanggal', $dateKey)
                ->sum('nominal');

            // Pemasukan lain hari ini
            $pemasukanLain = (float) PemasukanLain::query()
                ->whereDate('tanggal', $dateKey)
                ->sum('nominal');

            $data[$dateKey] = [
                'penjualan' => $penjualan,
                'hpp' => $hpp,
                'pengeluaran' => $pengeluaran,
                'pemasukan_lain' => $pemasukanLain,
                'laba_kotor' => $penjualan - $hpp,
                'laba_operasional' => ($penjualan - $hpp) - $pengeluaran + $pemasukanLain,
            ];
        }

        return $data;
    }

    /**
     * Resolve date range
     */
    private function resolveDateRange(string $period, ?string $startDate, ?string $endDate): array
    {
        $tz = OrderService::LOCAL_TIMEZONE;

        if ($period === 'custom' && $startDate && $endDate) {
            $start = Carbon::createFromFormat('Y-m-d', $startDate, $tz)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $endDate, $tz)->endOfDay();

            if ($start > $end) {
                $start = $end->copy()->startOfDay();
            }

            return [$start, $end];
        }

        $now = Carbon::now($tz);

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth()->startOfDay(), $now->copy()->endOfMonth()->endOfDay()],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}
