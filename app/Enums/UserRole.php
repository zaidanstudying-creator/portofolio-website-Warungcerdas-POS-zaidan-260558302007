<?php

namespace App\Enums;

enum UserRole: string
{
    case AdministratorUtama = 'administrator_utama';

    case Admin = 'admin';

    case Kasir = 'kasir';
}
