<?php

namespace App\Enums;

enum Role: string
{
    case IT = 'IT';
    case COORDINATOR = 'Audit Coordinator';
    case COUNTER_SERVICE = 'Counter Service';
    case CASHIER = 'Cashier';
    case FINANCE_SPV = 'Finance Spv';
    case FINANCE_MANAGER = 'Finance Manager';
    case FINANCE_OPERATION = 'Finance Operation';

    public function label(): string
    {
        return match ($this) {
            self::IT => 'Administrator IT',
            self::COORDINATOR => 'Koordinator Audit',
            self::COUNTER_SERVICE => 'Layanan Counter',
            self::CASHIER => 'Kasir',
            self::FINANCE_SPV => 'Supervisor Finance',
            self::FINANCE_MANAGER => 'Manager Finance',
            self::FINANCE_OPERATION => 'Operasional Finance',
        };
    }
}
