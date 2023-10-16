<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use Illuminate\Http\UploadedFile;

class AdminModelData
{
    /**
     * The country ID.
     *
     * @var string|null
     */
    private string|null $countryId = null;

    /**
     * The firstname.
     *
     * @var string|null
     */
    private string|null $firstname = null;

    /**
     * The lastname.
     *
     * @var string|null
     */
    private string|null $lastname = null;

    /**
     * The email.
     *
     * @var string|null
     */
    private string|null $email = null;

    /**
     * The phone number.
     *
     * @var string|null
     */
    private string|null $phoneNumber = null;

    /**
     * The avatar.
     *
     * @var \Illuminate\Http\UploadedFile|null
     */
    private UploadedFile|null $avatar = null;

    /**
     * The FCM Token.
     *
     * @var string|null
     */
    private string|null $fcmToken = null;

    /**
     * Get the country ID.
     *
     * @return string|null
     */
    public function getCountryId(): string|null
    {
        return $this->countryId;
    }

    /**
     * Set the country ID.
     *
     * @param string|null $countryId The country ID.
     *
     * @return self
     */
    public function setCountryId(string|null $countryId): self
    {
        $this->countryId = $countryId;

        return $this;
    }

    /**
     * Get the firstname.
     *
     * @return string|null
     */
    public function getFirstname(): string|null
    {
        return $this->firstname;
    }

    /**
     * Set the firstname.
     *
     * @param string|null $firstname The firstname.
     *
     * @return self
     */
    public function setFirstname(string|null $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get the lastname.
     *
     * @return string|null
     */
    public function getLastname(): string|null
    {
        return $this->lastname;
    }

    /**
     * Set the lastname.
     *
     * @param string|null $lastname The lastname.
     *
     * @return self
     */
    public function setLastname(string|null $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get the email.
     *
     * @return string|null
     */
    public function getEmail(): string|null
    {
        return $this->email;
    }

    /**
     * Set the email.
     *
     * @param string|null $email The email.
     *
     * @return self
     */
    public function setEmail(string|null $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the phone number.
     *
     * @return string|null
     */
    public function getPhoneNumber(): string|null
    {
        return $this->phoneNumber;
    }

    /**
     * Set the phone number.
     *
     * @param string|null $phoneNumber The phone number.
     *
     * @return self
     */
    public function setPhoneNumber(string|null $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber ? str_replace(' ', '', $phoneNumber) : null;

        return $this;
    }

    /**
     * Get the avatar.
     *
     * @return \Illuminate\Http\UploadedFile|null
     */
    public function getAvatar(): \Illuminate\Http\UploadedFile|string|null
    {
        return $this->avatar;
    }

    /**
     * Set the avatar.
     *
     * @param \Illuminate\Http\UploadedFile|null $avatar The avatar.
     *
     * @return self
     */
    public function setAvatar(UploadedFile|null $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get the FCM Token.
     *
     * @return string|null
     */
    public function getFcmToken(): string|null
    {
        return $this->fcmToken;
    }

    /**
     * Set the FCM Token.
     *
     * @param string|null $fcmToken The FCM Token.
     *
     * @return self
     */
    public function setFcmToken(?string $fcmToken): self
    {
        $this->fcmToken = $fcmToken;

        return $this;
    }
}
