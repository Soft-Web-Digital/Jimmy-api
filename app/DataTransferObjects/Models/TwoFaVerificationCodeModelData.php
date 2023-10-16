<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use App\Contracts\Auth\MustSatisfyTwoFa;

class TwoFaVerificationCodeModelData
{
    /**
     * The code.
     *
     * @var string
     */
    private string $code;

    /**
     * The ip address.
     *
     * @var string
     */
    private string $ipAddress;

    /**
     * The user.
     *
     * @var \App\Contracts\Auth\MustSatisfyTwoFa
     */
    private MustSatisfyTwoFa $user;

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
     * Get the ip address.
     *
     * @return string
     */
    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    /**
     * Set the ip address.
     *
     * @param string $ipAddress The ip address.
     *
     * @return self
     */
    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get the user.
     *
     * @return \App\Contracts\Auth\MustSatisfyTwoFa
     */
    public function getUser(): MustSatisfyTwoFa
    {
        return $this->user;
    }

    /**
     * Set the user.
     *
     * @param \App\Contracts\Auth\MustSatisfyTwoFa $user The user.
     *
     * @return self
     */
    public function setUser(MustSatisfyTwoFa $user): self
    {
        $this->user = $user;

        return $this;
    }
}
