<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case ENTRY_FEE = 'entry_fee';
    case PRIZE_CREDIT = 'prize_credit';
    case REFUND = 'refund';
}
