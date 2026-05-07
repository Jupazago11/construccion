<?php

namespace App\Enums;

enum SystemRole: string
{
    case SuperAdmin = 'SuperAdmin';
    case CompanyAdmin = 'CompanyAdmin';
    case Operator = 'Operator';
    case Viewer = 'Viewer';
    case BuyerUser = 'BuyerUser';
}
