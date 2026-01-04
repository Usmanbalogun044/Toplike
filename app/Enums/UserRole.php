<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CREATOR = 'creator';
    case USER = 'user';
}
