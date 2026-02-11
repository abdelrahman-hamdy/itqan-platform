<?php

namespace App\Enums;

enum PurchaseSource: string
{
    case WEB = 'web';
    case ADMIN = 'admin';
    case LEGACY = 'legacy';

    public function label(): string
    {
        return match ($this) {
            self::WEB => __('enums.purchase_source.web'),
            self::ADMIN => __('enums.purchase_source.admin'),
            self::LEGACY => __('enums.purchase_source.legacy'),
        };
    }
}
