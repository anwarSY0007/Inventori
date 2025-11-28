<?php

namespace App\Enum;

enum PaymentEnum:string
{
    case CASH = 'cash';
    case TRANSFER = 'transfer';
    case QRIS = 'qris';
    case DEBIT = 'debit_card';
    case CREDIT = 'credit_card';

    public function label(): string
    {
        return match($this) {
            self::CASH => 'Tunai',
            self::TRANSFER => 'Transfer Bank',
            self::QRIS => 'QRIS',
            self::DEBIT => 'Kartu Debit',
            self::CREDIT => 'Kartu Kredit',
        };
    }
}
