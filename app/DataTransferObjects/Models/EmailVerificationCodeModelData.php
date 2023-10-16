<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use App\Contracts\Auth\MustVerifyEmail;

class EmailVerificationCodeModelData
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
     * @var \App\Contracts\Auth\MustVerifyEmail
     */
    private MustVerifyEmail $user;

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
     * @return \App\Contracts\Auth\MustVerifyEmail
     */
    public function getUser(): MustVerifyEmail
    {
        return $this->user;
    }

    /**
     * Set the user.
     *
     * @param \App\Contracts\Auth\MustVerifyEmail $user The user.
     *
     * @return self
     */
    public function setUser(MustVerifyEmail $user): self
    {
        $this->user = $user;

        return $this;
    }
}
