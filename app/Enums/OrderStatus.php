<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';

    case Preparing = 'preparing';

    case Ready = 'ready';

    case Completed = 'completed';

    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Preparing => 'Disiapkan',
            self::Ready => 'Siap',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
