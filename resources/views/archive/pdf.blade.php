<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Arsip Transaksi - {{ $startDate->format('d/m/Y') }} s.d. {{ $endDate->format('d/m/Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 0; padding: 10px; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 11px; margin: 10px 0 5px; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
        .meta { font-size: 8px; text-align: center; margin-bottom: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 3px 4px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; font-size: 8px; }
        td { font-size: 8px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 10px; font-size: 9px; }
        .summary table { width: auto; }
        .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-unpaid { background: #fef3c7; color: #92400e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-dinein { background: #d1fae5; color: #065f46; }
        .badge-takeaway { background: #dbeafe; color: #1e40af; }
        .badge-delivery { background: #e9d5ff; color: #6b21a8; }
    </style>
</head>
<body>
    <h1>Arsip Transaksi WarungCerdas POS</h1>
    <div class="meta">
        Periode: {{ $startDate->format('d/m/Y') }} s.d. {{ $endDate->format('d/m/Y') }} |
        Filter: {{ ucfirst($statusFilter) }} |
        Format: {{ strtoupper($format) }} |
        Dibuat: {{ now()->format('d/m/Y H:i') }}
    </div>

    <h2>Ringkasan</h2>
    <table class="summary">
        <tr>
            <td>Total Transaksi:</td>
            <td class="text-right">{{ number_format($recordCount) }}</td>
            <td>Total Nominal:</td>
            <td class="text-right">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
        </tr>
    </table>

    <h2>Detail Transaksi ({{ $recordCount }} records)</h2>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th style="width: 90px;">Nomor Transaksi</th>
                <th style="width: 70px;">Waktu</th>
                <th style="width: 50px;">Tipe</th>
                <th style="width: 70px;">Customer</th>
                <th style="width: 80px;">Lokasi</th>
                <th style="width: 60px;">Subtotal</th>
                <th style="width: 70px;">Promo</th>
                <th style="width: 50px;">Diskon</th>
                <th style="width: 50px;">Fee</th>
                <th style="width: 50px;">Biaya Antar</th>
                <th style="width: 50px;">Komisi</th>
                <th style="width: 70px;">Total</th>
                <th style="width: 60px;">Metode</th>
                <th style="width: 60px;">Status Bayar</th>
                <th style="width: 60px;">Status Pesanan</th>
                <th style="width: 60px;">Pencatat</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach ($orders as $order)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td>{{ $order->order_number }}</td>
                <td>{{ $order->created_at->timezone(\App\Services\OrderService::LOCAL_TIMEZONE)->format('d/m/Y H:i') }}</td>
                <td class="text-center">
                    @php
                        $typeClass = match($order->type->value) {
                            'DI' => 'badge-dinein',
                            'TA' => 'badge-takeaway',
                            'DLV' => 'badge-delivery',
                            default => '',
                        };
                        $typeLabel = $order->type->label();
                    @endphp
                    <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
                </td>
                <td>{{ $order->customer_name ?? '-' }}</td>
                <td>
                    @if ($order->type->value === 'DI')
                        Meja: {{ $order->table_number ?? '-' }}
                    @elseif ($order->type->value === 'DLV')
                        {{ $order->delivery_platform }}: {{ Str::limit($order->delivery_address ?? '-', 20) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">Rp {{ number_format($order->subtotal - $order->items->sum(fn($i) => $i->toppings->sum(fn($t) => $t->price * $t->quantity)), 0, ',', '.') }}</td>
                <td class="text-right">
                    @if ((float) $order->promo_amount > 0)
                        {{ $order->promo_code ? $order->promo_code.' · ' : '' }}Rp {{ number_format($order->promo_amount, 0, ',', '.') }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">Rp {{ number_format($order->discount_amount ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($order->additional_fee_amount ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($order->delivery_fee ?? 0, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($order->platform_commission ?? 0, 0, ',', '.') }}</td>
                <td class="text-right font-bold">Rp {{ number_format($order->total, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if ($order->payment_method)
                        @php
                            $payClass = $order->payment_method === \App\Enums\PaymentMethod::Cash ? 'badge-paid' : 'badge-unpaid';
                            $payLabel = $order->payment_method === \App\Enums\PaymentMethod::Cash ? 'Tunai' : 'QRIS';
                        @endphp
                        <span class="badge {{ $payClass }}">{{ $payLabel }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $statusClass = match($order->status->value) {
                            'paid' => 'badge-paid',
                            'cancelled' => 'badge-cancelled',
                            default => 'badge-unpaid',
                        };
                        $statusLabel = $order->status->label() ?? $order->status->value;
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </td>
                <td>{{ $order->user?->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 15px; text-align: right; font-size: 8px; color: #666;">
        Dicetak otomatis oleh sistem WarungCerdas POS pada {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>