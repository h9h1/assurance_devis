<?php



namespace App\Enum;

enum Company: string
{
    case Axa_Assurance = 'Axa Assurance';
    case Wafa_Assurance = 'Wafa Assurance';
    case RMA = 'RMA';
    case Unknown = '';

    public static function values(): array
    {
        return array_map(static fn(self $company) => $company->value, self::cases());
    }

    public static function visibleCases(): array
    {
        return array_filter(
            self::cases(),
            static fn(self $company) => $company !== self::Unknown
        );
    }
}
