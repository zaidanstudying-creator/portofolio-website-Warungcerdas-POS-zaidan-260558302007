<?php

namespace App\Models;

use Database\Factories\PengeluaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tanggal',
    'nominal',
    'kategori_pengeluaran_id',
    'metode_pembayaran',
    'keterangan',
    'user_id',
    'bukti_path',
])]
class Pengeluaran extends Model
{
    /** @use HasFactory<PengeluaranFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pengeluaran';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
            'metode_pembayaran' => 'string',
        ];
    }

    /** @return BelongsTo<KategoriPengeluaran, $this> */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
