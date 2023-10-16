<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use Illuminate\Http\UploadedFile;

class GiftcardCategoryModelData
{
    /**
     * The name.
     *
     * @var string|null
     */
    private string|null $name = null;

    /**
     * The sale term.
     *
     * @var string|null
     */
    private string|null $saleTerm = null;

    /**
     * The purchase term.
     *
     * @var string|null
     */
    private string|null $purchaseTerm = null;

    /**
     * The admin IDs.
     *
     * @var array<int, string>|null
     */
    private array|null $adminIds = null;

    /**
     * The icon.
     *
     * @var \Illuminate\Http\UploadedFile|null
     */
    private UploadedFile|null $icon = null;

    /**
     * The country IDs.
     *
     * @var array<int, string>|null
     */
    private array|null $countryIds = null;

    /**
     * Get the name.
     *
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string|null $name The name.
     *
     * @return self
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the sale term.
     *
     * @return string|null
     */
    public function getSaleTerm(): string|null
    {
        return $this->saleTerm;
    }

    /**
     * Set the sale term.
     *
     * @param string|null $saleTerm The sale term.
     *
     * @return self
     */
    public function setSaleTerm($saleTerm): self
    {
        $this->saleTerm = $saleTerm;

        return $this;
    }

    /**
     * Get the icon.
     *
     * @return \Illuminate\Http\UploadedFile|null
     */
    public function getIcon(): \Illuminate\Http\UploadedFile|null
    {
        return $this->icon;
    }

    /**
     * Set the icon.
     *
     * @param \Illuminate\Http\UploadedFile|null $icon The icon.
     *
     * @return self
     */
    public function setIcon($icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get the country IDs.
     *
     * @return array<int, string>|null
     */
    public function getCountryIds(): array|null
    {
        return $this->countryIds;
    }

    /**
     * Set the country IDs.
     *
     * @param array<int, string>|null $countryIds The country IDs.
     *
     * @return self
     */
    public function setCountryIds($countryIds): self
    {
        $this->countryIds = $countryIds;

        return $this;
    }

    /**
     * Get the purchase term.
     *
     * @return string|null
     */
    public function getPurchaseTerm(): string|null
    {
        return $this->purchaseTerm;
    }

    /**
     * Set the purchase term.
     *
     * @param string|null $purchaseTerm The purchase term.
     *
     * @return self
     */
    public function setPurchaseTerm($purchaseTerm): self
    {
        $this->purchaseTerm = $purchaseTerm;

        return $this;
    }

    /**
     * Get the admin IDs.
     *
     * @return array<int, string>|null
     */
    public function getAdminIds(): array|null
    {
        return $this->adminIds;
    }

    /**
     * Set the admin IDs.
     *
     * @param array<int, string>|null $adminIds The admin IDs.
     *
     * @return self
     */
    public function setAdminIds($adminIds): self
    {
        $this->adminIds = $adminIds;

        return $this;
    }
}
