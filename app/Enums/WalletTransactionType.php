<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumTrait;

enum WalletTransactionType: string
{
    use EnumTrait;

    case DEBIT = 'debit';
    case CREDIT = 'credit';

    /**
     * Get the sentence term.
     *
     * @return string
     */
    public function sentenceTerm(): string
    {
        return match ($this) {
            WalletTransactionType::CREDIT => 'credited to',
            WalletTransactionType::DEBIT => 'debited from',
        };
    }
}
