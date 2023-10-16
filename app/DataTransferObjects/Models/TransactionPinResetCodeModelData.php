<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use App\Contracts\Auth\HasTransactionPin;

class TransactionPinResetCodeModelData
{
    /**
     * The code.
     *
     * @var string
     */
    private string $code;

    /**
     * The user.
     *
     * @var \App\Contracts\Auth\HasTransactionPin
     */
    private HasTransactionPin $user;

    /**
     * Get the code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set the code.
     *
     * @param string $code The code.
     *
     * @return self
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the user.
     *
     * @return \App\Contracts\Auth\HasTransactionPin
     */
    public function getUser(): HasTransactionPin
    {
        return $this->user;
    }

    /**
     * Set the user.
     *
     * @param \App\Contracts\Auth\HasTransactionPin $user The user.
     *
     * @return self
     */
    public function setUser(HasTransactionPin $user): self
    {
        $this->user = $user;

        return $this;
    }
}
