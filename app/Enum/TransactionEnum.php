<?php

namespace App\Enum;

enum TransactionEnum: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::PAID => 'Lunas',
            self::FAILED => 'Gagal',
            self::CANCELLED => 'Dibatalkan',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning', // Kuning
            self::PAID => 'success',    // Hijau
            self::FAILED => 'danger',   // Merah
            self::CANCELLED => 'secondary', // Abu-abu
        };
    }
}
