<?php



namespace App\Enum;

enum InsuranceType: string
{
    case AUTO = 'auto';
    case MOTO = 'moto';
    case UNKNOWN = '';
}
