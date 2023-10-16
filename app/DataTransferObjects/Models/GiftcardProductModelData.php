<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

class GiftcardProductModelData
{
    /**
     * The giftcard category ID.
     *
     * @var string|null
     */
    private string|null $giftcardCategoryId = null;

    /**
     * The country ID.
     *
     * @var string|null
     */
    private string|null $countryId = null;

    /**
     * The currency ID.
     *
     * @var string|null
     */
    private string|null $currencyId = null;

    /**
     * The name.
     *
     * @var string|null
     */
    private string|null $name = null;

    /**
     * The sell rate.
     *
     * @var float|null
     */
    private float|null $sellRate = null;

    /**
     * The sell minimum amount.
     *
     * @var float|null
     */
    private float|null $sellMinAmount = null;

    /**
     * The sell maximum amount.
     *
     * @var float|null
     */
    private float|null $sellMaxAmount = null;

    /**
     * Get the giftcard category ID.
     *
     * @return string|null
     */
    public function getGiftcardCategoryId(): string|null
    {
        return $this->giftcardCategoryId;
    }

    /**
     * Set the giftcard category ID.
     *
     * @param string|null $giftcardCategoryId The giftcard category ID.
     *
     * @return self
     */
    public function setGiftcardCategoryId($giftcardCategoryId): self
    {
        $this->giftcardCategoryId = $giftcardCategoryId;

        return $this;
    }

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
    public function setCountryId($countryId): self
    {
        $this->countryId = $countryId;

        return $this;
    }

    /**
     * Get the currency ID.
     *
     * @return string|null
     */
    public function getCurrencyId(): string|null
    {
        return $this->currencyId;
    }

    /**
     * Set the currency ID.
     *
     * @param string|null $currencyId The currency ID.
     *
     * @return self
     */
    public function setCurrencyId($currencyId): self
    {
        $this->currencyId = $currencyId;

        return $this;
    }

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
     * Get the sell rate.
     *
     * @return float|null
     */
    public function getSellRate(): float|null
    {
        return $this->sellRate;
    }

    /**
     * Set the sell rate.
     *
     * @param float|null $sellRate The sell rate.
     *
     * @return self
     */
    public function setSellRate($sellRate): self
    {
        $this->sellRate = $sellRate;

        return $this;
    }

    /**
     * Get the sell minimum amount.
     *
     * @return float|null
     */
    public function getSellMinAmount(): float|null
    {
        return $this->sellMinAmount;
    }

    /**
     * Set the sell minimum amount.
     *
     * @param float|null $sellMinAmount The sell minimum amount.
     *
     * @return self
     */
    public function setSellMinAmount($sellMinAmount): self
    {
        $this->sellMinAmount = $sellMinAmount;

        return $this;
    }

    /**
     * Get the sell maximum amount.
     *
     * @return float|null
     */
    public function getSellMaxAmount(): float|null
    {
        return $this->sellMaxAmount;
    }

    /**
     * Set the sell maximum amount.
     *
     * @param float|null $sellMaxAmount The sell maximum amount.
     *
     * @return self
     */
    public function setSellMaxAmount($sellMaxAmount): self
    {
        $this->sellMaxAmount = $sellMaxAmount;

        return $this;
    }
}
