<?php



namespace App\Enum;

enum City: string
{
    case Casablanca = 'Casablanca';
    case Rabat = 'Rabat';
    case Sale = 'Sale';
    case Fes = 'Fes';
    case Marrakech = 'Marrakech';
    case Tanger = 'Tanger';
    case Agadir = 'Agadir';
    case Meknes = 'Meknes';
    case Oujda = 'Oujda';
    case Kenitra = 'Kenitra';
    case Tetouan = 'Tetouan';
    case Safi = 'Safi';
    case El_Jadida = 'El Jadida';
    case Nador = 'Nador';
    case Laâyoune = 'Laâyoune';
    case Dakhla = 'Dakhla';
    case Beni_Mellal = 'Beni Mellal';
    case Khouribga = 'Khouribga';
    case Taza = 'Taza';
    case Errachidia = 'Errachidia';
    case Essaouira = 'Essaouira';
    case Ouarzazate = 'Ouarzazate';
    case Guelmim = 'Guelmim';
    case Ifrane = 'Ifrane';
    case Chefchaouen = 'Chefchaouen';
    case Mohammedia = 'Mohammedia';
    case Larache = 'Larache';
    case Ksar_El_Kebir = 'Ksar El Kebir';
    case Berkane = 'Berkane';
    case Al_Hoceïma = 'Al Hoceïma';
    case Sidi_Kacem = 'Sidi Kacem';
    case Sidi_Slimane = 'Sidi Slimane';
    case Taroudant = 'Taroudant';
    case Azrou = 'Azrou';
    case Tiznit = 'Tiznit';
    case Midelt = 'Midelt';
    case Taourirt = 'Taourirt';
    case Settat = 'Settat';
    case Temara = 'Temara';
    case Unknown = '';

    public static function values(): array
    {
        return array_map(static fn(self $city) => $city->value, self::cases());
    }
}
