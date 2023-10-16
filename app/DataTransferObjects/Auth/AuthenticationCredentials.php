<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Auth;

use Illuminate\Foundation\Auth\User;

class AuthenticationCredentials
{
    /**
     * The logged-in user.
     *
     * @var \Illuminate\Foundation\Auth\User
     */
    private User $user;

    /**
     * The authentication token.
     *
     * @var string
     */
    private string $token;

    /**
     * The API message.
     *
     * @var string
     */
    private string $apiMessage;

    /**
     * The Two-FA Status.
     *
     * @var bool
     */
    private bool $twoFaRequired = false;

    /**
     * Get the logged-in user.
     *
     * @return \Illuminate\Foundation\Auth\User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the logged-in user.
     *
     * @param \Illuminate\Foundation\Auth\User $user The logged-in user
     *
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the authentication token.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set the authentication token.
     *
     * @param string $token The authentication token.
     *
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get the API message.
     *
     * @return string
     */
    public function getApiMessage(): string
    {
        return $this->apiMessage;
    }

    /**
     * Set the API message.
     *
     * @param string $apiMessage The API message.
     *
     * @return self
     */
    public function setApiMessage(string $apiMessage): self
    {
        $this->apiMessage = $apiMessage;

        return $this;
    }

    /**
     * Get the Two-FA Status.
     *
     * @return bool
     */
    public function getTwoFaRequired(): bool
    {
        return $this->twoFaRequired;
    }

    /**
     * Set the Two-FA Status.
     *
     * @param bool $twoFaRequired The Two-FA Status.
     *
     * @return self
     */
    public function setTwoFaRequired(bool $twoFaRequired): self
    {
        $this->twoFaRequired = $twoFaRequired;

        return $this;
    }
}
