<?php



namespace App\Enum;

enum FuelType: string
{
    case ESSENCE = 'essence';
    case DIESEL = 'diesel';
    case HYBRIDE = 'hybride';
    case ELECTRIQUE = 'electrique';
    case GPL = 'gpl';
    case Unknown = '';
}
