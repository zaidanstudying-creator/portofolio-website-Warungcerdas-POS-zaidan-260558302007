<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePromoRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('promos', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2', Rule::when($this->input('type') === 'percentage', ['max:100'])],
            'scope' => ['required', Rule::in(['global', 'product', 'category'])],
            'product_id' => ['nullable', 'integer', 'required_if:scope,product', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'required_if:scope,category', 'exists:categories,id'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'max_discount' => ['nullable', 'numeric', 'min:0.01', 'max:999999999', 'decimal:0,2'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
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
            'code.unique' => 'Kode promo sudah digunakan.',
            'code.max' => 'Kode promo maksimal 50 karakter.',
            'name.required' => 'Nama promo wajib diisi.',
            'type.required' => 'Jenis promo wajib dipilih.',
            'type.in' => 'Jenis promo tidak dikenali.',
            'value.required' => 'Nilai promo wajib diisi.',
            'value.min' => 'Nilai promo harus lebih dari 0.',
            'value.max' => 'Nilai promo melebihi batas yang diperbolehkan.',
            'value.decimal' => 'Nilai promo maksimal 2 angka desimal.',
            'scope.required' => 'Cakupan promo wajib dipilih.',
            'scope.in' => 'Cakupan promo tidak dikenali.',
            'product_id.required_if' => 'Produk wajib dipilih untuk promo produk.',
            'product_id.exists' => 'Produk yang dipilih tidak ditemukan.',
            'category_id.required_if' => 'Kategori wajib dipilih untuk promo kategori.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'min_subtotal.min' => 'Minimal belanja tidak boleh negatif.',
            'max_discount.min' => 'Maksimal diskon harus lebih dari 0.',
            'starts_at.required' => 'Tanggal mulai wajib diisi.',
            'ends_at.required' => 'Tanggal berakhir wajib diisi.',
            'ends_at.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'usage_limit.min' => 'Batas penggunaan minimal 1.',
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
            'name' => 'nama promo',
            'type' => 'jenis promo',
            'value' => 'nilai promo',
            'scope' => 'cakupan promo',
            'product_id' => 'produk',
            'category_id' => 'kategori',
            'min_subtotal' => 'minimal belanja',
            'max_discount' => 'maksimal diskon',
            'starts_at' => 'tanggal mulai',
            'ends_at' => 'tanggal berakhir',
            'usage_limit' => 'batas penggunaan',
        ];
    }
}
