<?php


namespace App\Enum;

enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
}
