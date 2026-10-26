<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Topping;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Local timezone used for the daily order number (WC-YYYYMMDD-###) and for
     * displaying order timestamps. Storage stays in the app timezone (UTC).
     */
    public const LOCAL_TIMEZONE = 'Asia/Jakarta';

    /**
     * Maximum attempts when the order number collides with a concurrent request.
     */
    private const MAX_ORDER_NUMBER_ATTEMPTS = 3;

    /**
     * Store an order with its items and toppings inside a single transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Order
    {
        $attempt = 0;

        while (true) {
            try {
                return DB::transaction(fn (): Order => $this->persist($data), attempts: 3);
            } catch (QueryException $exception) {
                $attempt++;

                if ($attempt >= self::MAX_ORDER_NUMBER_ATTEMPTS || ! $this->isOrderNumberConflict($exception)) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Build a server-authoritative promo preview for the cashier.
     *
     * Catalog prices, eligibility, and the discount amount are resolved from
     * the database. The client preview is never trusted at checkout.
     *
     * @param  array<string, mixed>  $data
     * @return array{subtotal: float, eligible_subtotal: float, promo_amount: float, promo_code: string, message: string}
     */
    public function previewPromo(array $data): array
    {
        $products = Product::query()
            ->available()
            ->whereIn('id', array_column($data['items'], 'product_id'))
            ->with(['toppings' => fn ($query) => $query->where('is_available', true)])
            ->get()
            ->keyBy('id');

        $toppings = Topping::query()
            ->whereIn('id', $this->requestedToppingIds($data['items']))
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        $items = $this->prepareItems($data['items'], $products, $toppings);
        $subtotal = round($items->sum('subtotal'), 2);
        $resolved = $this->resolvePromo($data['code'] ?? null, $subtotal, $items, $products, lock: false, errorKey: 'code');

        if ($resolved === null) {
            throw ValidationException::withMessages(['code' => 'Kode promo wajib diisi.']);
        }

        return [
            'subtotal' => $subtotal,
            'eligible_subtotal' => $resolved['eligible_subtotal'],
            'promo_amount' => $resolved['amount'],
            'promo_code' => $resolved['promo']->code,
            'message' => 'Promo '.$resolved['promo']->code.' berhasil diterapkan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(array $data): Order
    {
        app(StoreOperationService::class)->assertOpenForTransaction();

        $type = OrderType::from($data['type']);

        $products = Product::query()
            ->available()
            ->whereIn('id', array_column($data['items'], 'product_id'))
            ->with(['toppings' => fn ($query) => $query->where('is_available', true)])
            ->get()
            ->keyBy('id');

        $toppings = Topping::query()
            ->whereIn('id', $this->requestedToppingIds($data['items']))
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        $items = $this->prepareItems($data['items'], $products, $toppings);
        $subtotal = round($items->sum('subtotal'), 2);

        $promo = $this->resolvePromo($data['promo_code'] ?? null, $subtotal, $items, $products, lock: true);
        $promoAmount = $promo['amount'] ?? 0.0;

        if ($promo !== null) {
            $this->consumePromoUsage($promo['promo']);
        }

        $subtotalAfterPromo = round($subtotal - $promoAmount, 2);

        $discountType = $data['discount_type'] ?? null;
        $discountValue = round((float) ($data['discount_value'] ?? 0), 2);
        $fee = round((float) ($data['additional_fee_amount'] ?? 0), 2);
        $feeLabel = $this->normalizeText($data['additional_fee_label'] ?? null);

        if ($discountValue < 0 || ! in_array($discountType, [null, 'fixed', 'percentage'], true) || ($discountType === 'percentage' && $discountValue > 100) || ($discountType === null && $discountValue > 0)) {
            throw ValidationException::withMessages(['discount_value' => 'Nilai diskon tidak valid.']);
        }

        $discount = $discountType === 'percentage' ? round($subtotalAfterPromo * $discountValue / 100, 2) : $discountValue;

        if ($discount > $subtotalAfterPromo) {
            throw ValidationException::withMessages(['discount_value' => 'Diskon tidak boleh melebihi subtotal setelah promo.']);
        }

        if ($fee < 0 || ($fee > 0 && $feeLabel === null)) {
            throw ValidationException::withMessages(['additional_fee_amount' => 'Isi label dan nominal biaya tambahan yang valid.']);
        }

        $total = round($subtotalAfterPromo - $discount + $fee, 2);
        if ($subtotal > 9999999999.99 || $total > 9999999999.99) {
            throw ValidationException::withMessages(['items' => 'Total transaksi melebihi batas penyimpanan.']);
        }
        $payment = $this->resolvePayment($data, $total);

        $order = Order::create([
            'order_number' => $this->nextOrderNumber(),
            'type' => $data['type'],
            'status' => OrderStatus::Preparing,
            'customer_name' => $this->normalizeText($data['customer_name'] ?? null),
            'table_number' => $type === OrderType::DI ? $this->normalizeText($data['table_number'] ?? null) : null,
            'delivery_address' => $type === OrderType::DLV ? $this->normalizeText($data['delivery_address'] ?? null) : null,
            'delivery_platform' => $type === OrderType::DLV ? $this->normalizeText($data['delivery_platform'] ?? null) : null,
            'subtotal' => $subtotal,
            'total' => $total,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discount,
            'additional_fee_label' => $fee > 0 ? $feeLabel : null,
            'additional_fee_amount' => $fee,
            'promo_id' => $promo['promo']->id ?? null,
            'promo_amount' => $promoAmount,
            'promo_code' => $promo['promo']->code ?? null,
            'payment_method' => $payment['payment_method'],
            'amount_paid' => $payment['amount_paid'],
            'change' => $payment['change'],
            'payment_status' => $payment['payment_status'],
            'paid_at' => $payment['paid_at'],
            'notes' => $this->normalizeText($data['notes'] ?? null),
        ]);

        foreach ($items as $item) {
            $orderItem = $order->items()->create(Arr::except($item, ['toppings']));

            $orderItem->toppings()->createMany($item['toppings']);
        }

        return $order;
    }

    /**
     * Resolve the stored payment fields from the request, never trusting client
     * supplied totals, change, paid_at, or payment_status.
     *
     * Unpaid orders (or orders without payment data) keep amount_paid, change,
     * and paid_at as null. A paid order additionally requires the cashier to
     * explicitly confirm the payment (payment_confirmed); selecting a method
     * alone never marks an order as paid. Paid cash orders require the amount
     * received to be at least the server-calculated total; QRIS records the
     * total server-side. QRIS is a manual record and is not verified by any
     * payment provider.
     *
     * @param  array<string, mixed>  $data
     * @return array{payment_method: ?PaymentMethod, amount_paid: ?float, change: ?float, payment_status: PaymentStatus, paid_at: ?Carbon}
     */
    private function resolvePayment(array $data, float $total): array
    {
        $status = isset($data['payment_status'])
            ? PaymentStatus::from($data['payment_status'])
            : PaymentStatus::Unpaid;
        $method = isset($data['payment_method'])
            ? PaymentMethod::from($data['payment_method'])
            : null;

        if ($status === PaymentStatus::Unpaid) {
            return [
                'payment_method' => $method,
                'amount_paid' => null,
                'change' => null,
                'payment_status' => PaymentStatus::Unpaid,
                'paid_at' => null,
            ];
        }

        if (! filter_var($data['payment_confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            throw ValidationException::withMessages([
                'payment_confirmed' => 'Konfirmasi pembayaran wajib dicentang sebelum menandai pesanan lunas.',
            ]);
        }

        if ($method === PaymentMethod::Qris) {
            return [
                'payment_method' => PaymentMethod::Qris,
                'amount_paid' => $total,
                'change' => 0.0,
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ];
        }

        $amountPaid = round((float) ($data['amount_paid'] ?? 0), 2);

        if ($amountPaid < $total) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Uang tunai yang diterima kurang dari total pesanan.',
            ]);
        }

        return [
            'payment_method' => PaymentMethod::Cash,
            'amount_paid' => $amountPaid,
            'change' => round($amountPaid - $total, 2),
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ];
    }

    /**
     * Load and validate the requested catalog, then snapshot names and prices from the database.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Topping>  $toppings
     * @return Collection<int, array<string, mixed>>
     */
    private function prepareItems(array $items, Collection $products, Collection $toppings): Collection
    {
        return collect($items)->map(function (array $item) use ($products, $toppings): array {
            $product = $products->get((int) $item['product_id']);

            if (! $product instanceof Product) {
                throw ValidationException::withMessages([
                    'items' => 'Ada produk yang tidak tersedia. Muat ulang halaman kasir lalu coba lagi.',
                ]);
            }

            $allowedToppingIds = $product->toppings->modelKeys();

            $itemToppings = $this->normalizeItemToppings($item)->map(function (array $entry) use ($toppings, $allowedToppingIds): array {
                $topping = $toppings->get((int) $entry['id']);

                if (! $topping instanceof Topping) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada topping yang tidak tersedia. Muat ulang halaman kasir lalu coba lagi.',
                    ]);
                }

                if (! in_array($topping->id, $allowedToppingIds, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada topping yang tidak tersedia untuk produk ini. Muat ulang halaman kasir lalu coba lagi.',
                    ]);
                }

                return ['topping' => $topping, 'quantity' => $entry['quantity']];
            });

            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->price;
            $productHpp = $product->hpp !== null ? (float) $product->hpp : null;
            $toppingPrice = $itemToppings->sum(
                fn (array $entry): float => (float) $entry['topping']->price * $entry['quantity'],
            );

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $unitPrice,
                'hpp' => $productHpp,
                'quantity' => $quantity,
                'subtotal' => round(($unitPrice + $toppingPrice) * $quantity, 2),
                'notes' => $this->normalizeText($item['notes'] ?? null),
                'toppings' => $itemToppings->map(fn (array $entry): array => [
                    'topping_id' => $entry['topping']->id,
                    'topping_name' => $entry['topping']->name,
                    'price' => (float) $entry['topping']->price,
                    'hpp' => $entry['topping']->hpp !== null ? (float) $entry['topping']->hpp : null,
                    'quantity' => $entry['quantity'],
                ])->all(),
            ];
        });
    }

    /**
     * Normalize the per-item toppings to a uniform list of id/quantity pairs.
     *
     * Accepts the structured "toppings" payload ({id, quantity}); falls back to
     * the legacy flat "topping_ids" list with an implicit quantity of one.
     *
     * @param  array<string, mixed>  $item
     * @return Collection<int, array{id: int, quantity: int}>
     */
    private function normalizeItemToppings(array $item): Collection
    {
        if (isset($item['toppings']) && is_array($item['toppings'])) {
            return collect($item['toppings'])->map(fn (array $entry): array => [
                'id' => (int) $entry['id'],
                'quantity' => (int) $entry['quantity'],
            ]);
        }

        return collect($item['topping_ids'] ?? [])->map(fn ($toppingId): array => [
            'id' => (int) $toppingId,
            'quantity' => 1,
        ]);
    }

    /**
     * Collect every topping id requested by the cart.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, int>
     */
    private function requestedToppingIds(array $items): array
    {
        return collect($items)
            ->flatMap(fn (array $item): array => $this->normalizeItemToppings($item)->pluck('id')->all())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Build the next daily order number, e.g. WC-20260301-001.
     *
     * The daily sequence is read with a row lock where the driver supports it
     * (MySQL); uniqueness is ultimately guaranteed by the unique index on
     * order_number plus the bounded retry in create().
     */
    private function nextOrderNumber(): string
    {
        $prefix = 'WC-'.now(self::LOCAL_TIMEZONE)->format('Ymd').'-';

        $lastSequence = Order::query()
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('order_number')
            ->map(fn (string $orderNumber): int => (int) substr($orderNumber, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Determine whether the failed query was an order_number uniqueness violation.
     */
    private function isOrderNumberConflict(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23000'
            && str_contains($exception->getMessage(), 'order_number');
    }

    /**
     * Resolve an active promo and calculate its discount against the prepared cart.
     *
     * @param  Collection<int, array<string, mixed>>  $items
     * @param  Collection<int, Product>  $products
     * @return array{promo: Promo, eligible_subtotal: float, amount: float}|null
     */
    private function resolvePromo(?string $rawCode, float $subtotal, Collection $items, Collection $products, bool $lock, string $errorKey = 'promo_code'): ?array
    {
        $code = $this->normalizePromoCode($rawCode);

        if ($code === null) {
            return null;
        }

        $query = Promo::query()->valid()->where('code', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        $promo = $query->first();

        if (! $promo instanceof Promo) {
            throw ValidationException::withMessages([
                $errorKey => 'Kode promo tidak valid, tidak aktif, di luar periode, atau sudah habis.',
            ]);
        }

        if ($subtotal < (float) $promo->min_subtotal) {
            throw ValidationException::withMessages([
                $errorKey => 'Minimal belanja untuk promo ini adalah Rp '.number_format((float) $promo->min_subtotal, 0, ',', '.').'.',
            ]);
        }

        $eligibleSubtotal = round($items->filter(function (array $item) use ($promo, $products): bool {
            $product = $products->get((int) $item['product_id']);

            return $product instanceof Product
                && $promo->matchesItem((int) $item['product_id'], $product->category_id === null ? null : (int) $product->category_id);
        })->sum('subtotal'), 2);

        if ($eligibleSubtotal <= 0) {
            throw ValidationException::withMessages([
                $errorKey => 'Tidak ada item pada pesanan yang memenuhi syarat promo ini.',
            ]);
        }

        return [
            'promo' => $promo,
            'eligible_subtotal' => $eligibleSubtotal,
            'amount' => $promo->calculateDiscount($eligibleSubtotal),
        ];
    }

    /**
     * Consume one use of the promo quota inside the checkout transaction.
     *
     * The conditional update keeps usage_count from exceeding usage_limit even
     * if two checkouts read the same quota at the same time.
     */
    private function consumePromoUsage(Promo $promo): void
    {
        $affected = Promo::query()
            ->whereKey($promo->getKey())
            ->where(function ($query): void {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->increment('usage_count');

        if ($affected === 0) {
            throw ValidationException::withMessages([
                'promo_code' => 'Promo sudah habis digunakan.',
            ]);
        }
    }

    /**
     * Normalize a promo code so case and surrounding whitespace do not change its meaning.
     */
    private function normalizePromoCode(?string $code): ?string
    {
        $code = $code === null ? null : strtoupper(trim($code));

        return $code === null || $code === '' ? null : $code;
    }

    /**
     * Normalize optional text so blank input is stored as null.
     */
    private function normalizeText(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
