<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequest;
use App\Http\Requests\ValidatePromoRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promo;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PromoController extends Controller
{
    public function index(): View
    {
        $promos = Promo::query()
            ->with(['product', 'category'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('promos.index', compact('promos'));
    }

    public function create(): View
    {
        return view('promos.form', $this->formData());
    }

    public function store(StorePromoRequest $request): RedirectResponse
    {
        Promo::create($this->preparePayload($request->validated()));

        return redirect()->route('promos.index')->with('success', 'Promo berhasil ditambahkan.');
    }

    public function edit(Promo $promo): View
    {
        return view('promos.form', ['promo' => $promo] + $this->formData());
    }

    public function update(UpdatePromoRequest $request, Promo $promo): RedirectResponse
    {
        $promo->update($this->preparePayload($request->validated()));

        return redirect()->route('promos.index')->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promo $promo): RedirectResponse
    {
        $promo->delete();

        return redirect()->route('promos.index')->with('success', 'Promo "'.$promo->code.'" berhasil dihapus. Riwayat transaksi tetap utuh.');
    }

    /**
     * Preview a promo against the cashier's cart using database prices.
     */
    public function validatePromo(ValidatePromoRequest $request, OrderService $orders): JsonResponse
    {
        return response()->json($orders->previewPromo($request->validated()));
    }

    /**
     * Normalize the validated form payload for storage.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function preparePayload(array $validated): array
    {
        $scope = $validated['scope'];

        return [
            'code' => Str::upper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'scope' => $scope,
            'product_id' => $scope === 'product' ? $validated['product_id'] : null,
            'category_id' => $scope === 'category' ? $validated['category_id'] : null,
            'min_subtotal' => $validated['min_subtotal'] ?? 0,
            'max_discount' => $validated['max_discount'] ?? null,
            'starts_at' => Carbon::parse($validated['starts_at'], OrderService::LOCAL_TIMEZONE)->setTimezone(config('app.timezone')),
            'ends_at' => Carbon::parse($validated['ends_at'], OrderService::LOCAL_TIMEZONE)->setTimezone(config('app.timezone')),
            'is_active' => filter_var($validated['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'usage_limit' => $validated['usage_limit'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
            'products' => Product::with('category')->orderBy('name')->get(),
        ];
    }
}
