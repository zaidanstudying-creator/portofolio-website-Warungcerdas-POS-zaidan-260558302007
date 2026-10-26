<?php

use App\Enums\UserRole;
use App\Models\ArsipTransaksi;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Topping;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function promoCheckoutPayload(Promo $promo, array $items): array
{
    return [
        'type' => 'TA',
        'promo_code' => $promo->code,
        'items' => $items,
    ];
}

function promoFormPayload(array $overrides = []): array
{
    return $overrides + [
        'code' => 'HEMAT10',
        'name' => 'Hemat 10 Persen',
        'type' => 'percentage',
        'value' => 10,
        'scope' => 'global',
        'min_subtotal' => 0,
        'starts_at' => '2026-11-01T08:00',
        'ends_at' => '2026-11-30T23:59',
        'is_active' => '1',
    ];
}

test('a global percentage promo discounts the cart including toppings', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $topping = Topping::factory()->create(['price' => 3000]);
    $product->toppings()->attach($topping);
    $promo = Promo::factory()->create(['type' => 'percentage', 'value' => 10]);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [[
        'product_id' => $product->id,
        'quantity' => 2,
        'toppings' => [['id' => $topping->id, 'quantity' => 1]],
    ]]))->assertCreated();

    $order = Order::sole();
    expect($order->subtotal)->toBe('26000.00')
        ->and($order->promo_amount)->toBe('2600.00')
        ->and($order->total)->toBe('23400.00')
        ->and($order->promo_code)->toBe($promo->code)
        ->and($order->promo_id)->toBe($promo->id);
    expect($promo->refresh()->usage_count)->toBe(1);
});

test('a fixed promo is deducted once from the eligible subtotal', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->fixed(5000)->create();

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [[
        'product_id' => $product->id,
        'quantity' => 3,
    ]]))->assertCreated();

    $order = Order::sole();
    expect($order->subtotal)->toBe('30000.00')
        ->and($order->promo_amount)->toBe('5000.00')
        ->and($order->total)->toBe('25000.00');
});

test('a product promo only discounts the matching product', function () {
    actingAsUser();
    $eligible = Product::factory()->create(['price' => 10000]);
    $other = Product::factory()->create(['price' => 20000]);
    $promo = Promo::factory()->forProduct($eligible->id)->create(['type' => 'percentage', 'value' => 50]);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $eligible->id, 'quantity' => 2],
        ['product_id' => $other->id, 'quantity' => 1],
    ]))->assertCreated();

    $order = Order::sole();
    expect($order->subtotal)->toBe('40000.00')
        ->and($order->promo_amount)->toBe('10000.00')
        ->and($order->total)->toBe('30000.00');
});

test('a category promo only discounts products in that category', function () {
    actingAsUser();
    $eligibleCategory = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $eligible = Product::factory()->create(['category_id' => $eligibleCategory->id, 'price' => 10000]);
    $other = Product::factory()->create(['category_id' => $otherCategory->id, 'price' => 20000]);
    $promo = Promo::factory()->forCategory($eligibleCategory->id)->create(['type' => 'percentage', 'value' => 50]);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $eligible->id, 'quantity' => 2],
        ['product_id' => $other->id, 'quantity' => 1],
    ]))->assertCreated();

    $order = Order::sole();
    expect($order->subtotal)->toBe('40000.00')
        ->and($order->promo_amount)->toBe('10000.00')
        ->and($order->total)->toBe('30000.00');
});

test('a promo is rejected when no cart item is eligible', function () {
    actingAsUser();
    $eligible = Product::factory()->create(['price' => 10000]);
    $other = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->forProduct($eligible->id)->create();

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $other->id, 'quantity' => 1],
    ]))->assertUnprocessable()
        ->assertJsonPath('errors.promo_code.0', 'Tidak ada item pada pesanan yang memenuhi syarat promo ini.');

    $this->assertDatabaseCount('orders', 0);
    expect($promo->refresh()->usage_count)->toBe(0);
});

test('a promo is rejected below its minimum subtotal', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->fixed(1000)->create(['min_subtotal' => 15000]);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]))->assertUnprocessable()
        ->assertJsonPath('errors.promo_code.0', 'Minimal belanja untuk promo ini adalah Rp 15.000.');

    $this->assertDatabaseCount('orders', 0);
});

test('a promo discount is capped by max_discount', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 20000]);
    $promo = Promo::factory()->create(['type' => 'percentage', 'value' => 50, 'max_discount' => 3000]);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]))->assertCreated();

    $order = Order::sole();
    expect($order->promo_amount)->toBe('3000.00')
        ->and($order->total)->toBe('17000.00');
});

test('a promo cannot be used after its quota is exhausted', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->fixed(2000)->create(['usage_limit' => 1]);
    $payload = promoCheckoutPayload($promo, [['product_id' => $product->id, 'quantity' => 1]]);

    $this->postJson(route('orders.store'), $payload)->assertCreated();
    $this->postJson(route('orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('promo_code');

    $this->assertDatabaseCount('orders', 1);
    expect($promo->refresh()->usage_count)->toBe(1);
});

test('cancelling an order returns its promo quota exactly once', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->fixed(2000)->create(['usage_limit' => 1]);
    $payload = promoCheckoutPayload($promo, [['product_id' => $product->id, 'quantity' => 1]]);

    $this->postJson(route('orders.store'), $payload)->assertCreated();
    expect($promo->refresh()->usage_count)->toBe(1);

    $order = Order::sole();
    $this->patch(route('orders.status', $order), ['status' => 'cancelled'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('orders.show', $order));
    expect($promo->refresh()->usage_count)->toBe(0);

    $this->patch(route('orders.status', $order), ['status' => 'cancelled'])
        ->assertSessionHasErrors('status');
    expect($promo->refresh()->usage_count)->toBe(0);

    $this->postJson(route('orders.store'), $payload)->assertCreated();
    expect($promo->refresh()->usage_count)->toBe(1);
});

test('replaying a checkout does not consume promo quota twice', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->fixed(2000)->create(['usage_limit' => 5]);
    $payload = promoCheckoutPayload($promo, [['product_id' => $product->id, 'quantity' => 1]]);
    $headers = ['Idempotency-Key' => (string) Str::uuid()];

    $first = $this->postJson(route('orders.store'), $payload, $headers)->assertCreated();
    $second = $this->postJson(route('orders.store'), $payload, $headers)->assertCreated();

    expect($second->json('id'))->toBe($first->json('id'));
    $this->assertDatabaseCount('orders', 1);
    expect($promo->refresh()->usage_count)->toBe(1);
});

test('a percentage manual discount is calculated after the promo', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 20000]);
    $promo = Promo::factory()->fixed(5000)->create();

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]) + ['discount_type' => 'percentage', 'discount_value' => 10])->assertCreated();

    $order = Order::sole();
    expect($order->promo_amount)->toBe('5000.00')
        ->and($order->discount_amount)->toBe('1500.00')
        ->and($order->total)->toBe('13500.00');
});

test('a manual discount and additional fee are applied after the promo', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 20000]);
    $promo = Promo::factory()->create(['type' => 'percentage', 'value' => 10]);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]) + [
        'discount_type' => 'fixed',
        'discount_value' => 3000,
        'additional_fee_label' => 'Tip',
        'additional_fee_amount' => 2000,
    ])->assertCreated();

    $order = Order::sole();
    expect($order->promo_amount)->toBe('2000.00')
        ->and($order->discount_amount)->toBe('3000.00')
        ->and($order->additional_fee_amount)->toBe('2000.00')
        ->and($order->total)->toBe('17000.00');
});

test('a manual discount above the subtotal after promo is rejected and rolls back usage', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 20000]);
    $promo = Promo::factory()->fixed(15000)->create();

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]) + ['discount_type' => 'fixed', 'discount_value' => 6000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('discount_value');

    $this->assertDatabaseCount('orders', 0);
    expect($promo->refresh()->usage_count)->toBe(0);
});

test('a promo that is inactive, expired, or exhausted is rejected', function (array $attributes) {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->create($attributes);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]))->assertUnprocessable()->assertJsonValidationErrors('promo_code');

    $this->assertDatabaseCount('orders', 0);
})->with([
    'inactive' => [['is_active' => false]],
    'expired' => [['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]],
    'quota exhausted' => [['usage_limit' => 1, 'usage_count' => 1]],
]);

test('promo preview returns the server-calculated amount without consuming quota', function () {
    actingAsUser();
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->create(['type' => 'percentage', 'value' => 10]);

    $response = $this->postJson(route('promo.validate'), [
        'code' => strtolower($promo->code),
        'items' => [['product_id' => $product->id, 'quantity' => 2]],
    ])->assertOk();

    expect($response->json('subtotal'))->toBe(20000)
        ->and($response->json('promo_amount'))->toBe(2000)
        ->and($response->json('promo_code'))->toBe($promo->code);
    expect($promo->refresh()->usage_count)->toBe(0);
});

test('promo preview rejects an unknown code', function () {
    actingAsUser();
    $product = Product::factory()->create();

    $this->postJson(route('promo.validate'), [
        'code' => 'TIDAKADA',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

test('an admin can create a promo with WIB dates and normalized scope', function () {
    actingAsUser(['role' => UserRole::Admin]);
    $product = Product::factory()->create();
    $category = Category::factory()->create();

    $this->post(route('promos.store'), promoFormPayload([
        'code' => 'hemat10',
        'scope' => 'product',
        'product_id' => $product->id,
        'category_id' => $category->id,
    ]))->assertRedirect(route('promos.index'));

    $promo = Promo::sole();
    expect($promo->code)->toBe('HEMAT10')
        ->and($promo->scope)->toBe('product')
        ->and($promo->product_id)->toBe($product->id)
        ->and($promo->category_id)->toBeNull()
        ->and($promo->starts_at->format('Y-m-d H:i:s'))->toBe('2026-11-01 01:00:00')
        ->and($promo->ends_at->format('Y-m-d H:i:s'))->toBe('2026-11-30 16:59:00')
        ->and($promo->is_active)->toBeTrue();
});

test('cashiers cannot manage promos', function () {
    actingAsUser();

    $this->get(route('promos.index'))->assertForbidden();
    $this->post(route('promos.store'), promoFormPayload())->assertForbidden();

    $this->assertDatabaseCount('promos', 0);
});

test('an admin can update a promo and clear unrelated relations', function () {
    actingAsUser(['role' => UserRole::Admin]);
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $promo = Promo::factory()->forCategory($category->id)->create();

    $this->put(route('promos.update', $promo), promoFormPayload([
        'code' => 'baru20',
        'name' => 'Promo Baru',
        'type' => 'fixed',
        'value' => 20000,
        'scope' => 'global',
        'product_id' => $product->id,
        'category_id' => $category->id,
    ]))->assertRedirect(route('promos.index'));

    $promo->refresh();
    expect($promo->code)->toBe('BARU20')
        ->and($promo->name)->toBe('Promo Baru')
        ->and($promo->type)->toBe('fixed')
        ->and($promo->scope)->toBe('global')
        ->and($promo->product_id)->toBeNull()
        ->and($promo->category_id)->toBeNull();
});

test('deleting a promo keeps historical order promo data', function () {
    actingAsUser(['role' => UserRole::Admin]);
    $product = Product::factory()->create(['price' => 10000]);
    $promo = Promo::factory()->create(['type' => 'percentage', 'value' => 10]);

    $this->postJson(route('orders.store'), promoCheckoutPayload($promo, [
        ['product_id' => $product->id, 'quantity' => 2],
    ]))->assertCreated();

    $this->delete(route('promos.destroy', $promo))->assertRedirect(route('promos.index'));

    $order = Order::sole();
    expect($order->promo_id)->toBeNull()
        ->and($order->promo_code)->toBe($promo->code)
        ->and($order->promo_amount)->toBe('2000.00');
    $this->assertDatabaseCount('promos', 0);
});

test('invalid promo definitions are rejected', function (array $overrides, string $field) {
    actingAsUser(['role' => UserRole::Admin]);

    $this->post(route('promos.store'), promoFormPayload($overrides))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('promos', 0);
})->with([
    'percentage above 100' => [['type' => 'percentage', 'value' => 101], 'value'],
    'product scope without product' => [['scope' => 'product'], 'product_id'],
    'category scope without category' => [['scope' => 'category'], 'category_id'],
    'ends before starts' => [['starts_at' => '2026-11-30T10:00', 'ends_at' => '2026-11-01T10:00'], 'ends_at'],
    'zero value' => [['value' => 0], 'value'],
    'unknown type' => [['type' => 'bonus'], 'type'],
    'zero usage limit' => [['usage_limit' => 0], 'usage_limit'],
]);

test('a promo code must be unique', function () {
    actingAsUser(['role' => UserRole::Admin]);
    Promo::factory()->create(['code' => 'HEMAT10']);

    $this->post(route('promos.store'), promoFormPayload(['code' => 'hemat10']))->assertSessionHasErrors('code');

    $this->assertDatabaseCount('promos', 1);
});

test('the receipt and report export show the stored promo data', function () {
    actingAsUser(['role' => UserRole::Admin]);
    $order = Order::factory()->paid()->create([
        'subtotal' => 20000,
        'total' => 18000,
        'promo_code' => 'HEMAT10',
        'promo_amount' => 2000,
        'created_at' => now(),
    ]);

    $this->get(route('orders.receipt', $order))->assertOk()
        ->assertSee('Promo HEMAT10')
        ->assertSee('Rp 2.000');

    $csv = $this->get(route('reports.export', ['period' => 'today']))->assertOk()->streamedContent();
    expect($csv)->toContain('Kode Promo')
        ->toContain('HEMAT10')
        ->toContain('2000');
});

test('the archive csv exports the stored promo data as-is', function () {
    actingAsUser(['role' => UserRole::Admin]);
    Storage::fake('local');
    Order::factory()->paid()->create([
        'subtotal' => 20000,
        'total' => 18000,
        'promo_code' => 'HEMAT10',
        'promo_amount' => 2000,
        'created_at' => now(),
    ]);

    $this->post(route('archive.create'), [
        'tanggal_mulai' => now()->format('Y-m-d'),
        'tanggal_akhir' => now()->format('Y-m-d'),
        'status_filter' => 'paid',
        'format' => 'csv',
    ])->assertOk()->assertDownload();

    $archive = ArsipTransaksi::sole();
    $csv = Storage::disk('local')->get('archives/'.basename($archive->file_path));

    expect($csv)->toContain('Kode Promo')
        ->toContain('HEMAT10')
        ->toContain('2000');
});
