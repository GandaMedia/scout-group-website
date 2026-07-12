<?php

namespace App\Enums;

enum Store: string
{
    case ALDI = 'Aldi';
    case COOP = 'Co-op';
    case SAINSBURY = 'Sainsbury';
    case TESCO = 'Tesco';
    case ASDA = 'Asda';
    case MORRISONS = 'Morrisons';
    case WHOLEFOODS = 'Wholefoods';
    case BOOKERS = 'Bookers';

    case OTHER = 'Other';
}
