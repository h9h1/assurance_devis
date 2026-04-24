<?php



namespace App\Enum;

enum City: string
{
    case CASABLANCA = 'Casablanca';
    case RABAT = 'Rabat';
    case MARRAKECH = 'Marrakech';
    case FES = 'Fes';
    case TANGER = 'Tanger';
    case AGADIR = 'Agadir';

    public static function values(): array
    {
        return array_map(static fn(self $city) => $city->value, self::cases());
    }
}
