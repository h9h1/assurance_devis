<?php


namespace App\Enum;

enum QuoteStatus: string
{
    case CONFIRMED = 'confirmed';
    case SUBMITTED = 'submitted';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
}
