<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePemasukanLainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'nominal' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'kategori' => ['required', 'in:modal,lain'],
            'metode_pembayaran' => ['required', 'in:cash,qris,transfer,lain'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
