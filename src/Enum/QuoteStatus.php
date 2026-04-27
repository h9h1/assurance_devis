<?php


namespace App\Enum;

enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case SUBMITTED = 'submitted';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
}
