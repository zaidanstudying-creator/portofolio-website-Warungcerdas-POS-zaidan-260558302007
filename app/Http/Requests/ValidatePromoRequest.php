<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ValidatePromoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the promo code before validation so lookups are consistent.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => Str::upper(trim((string) $this->input('code')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.toppings' => ['nullable', 'array'],
            'items.*.toppings.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.topping_ids' => ['nullable', 'array'],
        ];

        $items = $this->input('items', []);

        if (is_array($items)) {
            foreach (array_keys($items) as $index) {
                $rules["items.{$index}.toppings.*.id"] = ['required', 'integer', 'distinct', 'exists:toppings,id'];
                $rules["items.{$index}.topping_ids.*"] = ['integer', 'distinct', 'exists:toppings,id'];
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode promo wajib diisi.',
            'code.max' => 'Kode promo maksimal 50 karakter.',
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Keranjang masih kosong.',
            'items.*.product_id.exists' => 'Ada produk yang tidak ditemukan. Muat ulang halaman kasir lalu coba lagi.',
            'items.*.quantity.min' => 'Jumlah pesanan minimal 1.',
            'items.*.toppings.*.id.exists' => 'Ada topping yang tidak ditemukan. Muat ulang halaman kasir lalu coba lagi.',
            'items.*.toppings.*.quantity.min' => 'Jumlah topping minimal 1.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'kode promo',
            'items' => 'item pesanan',
        ];
    }
}
