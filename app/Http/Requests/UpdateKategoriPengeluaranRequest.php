<?php

namespace App\Http\Requests;

use App\Models\KategoriPengeluaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKategoriPengeluaranRequest extends FormRequest
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
        $kategori = $this->route('kategori_pengeluaran');

        return [
            'nama' => ['required', 'string', 'max:100', 'unique:kategori_pengeluaran,nama,'.($kategori instanceof KategoriPengeluaran ? $kategori->id : $kategori)],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
