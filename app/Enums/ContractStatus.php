<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Pending = 'pending';
    case Formalized = 'formalized';
    case Lapsed = 'lapsed';
}
