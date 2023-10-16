<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class GiftcardBreakdownData
{
    /**
     * The service charge.
     *
     * @var float
     */
    private float $serviceCharge;

    /**
     * Get the rate.
     *
     * @var float
     */
    private float $rate;

    /**
     * The payable amount.
     *
     * @var float
     */
    private float $payableAmount;

    /**
     * Get the service charge.
     *
     * @return float
     */
    public function getServiceCharge(): float
    {
        return $this->serviceCharge;
    }

    /**
     * Set the service charge.
     *
     * @param float $serviceCharge The service charge.
     *
     * @return self
     */
    public function setServiceCharge(float $serviceCharge): self
    {
        $this->serviceCharge = $serviceCharge;

        return $this;
    }

    /**
     * Get the payable amount.
     *
     * @return float
     */
    public function getPayableAmount(): float
    {
        return $this->payableAmount;
    }

    /**
     * Set the payable amount.
     *
     * @param float $payableAmount The payable amount.
     *
     * @return self
     */
    public function setPayableAmount(float $payableAmount): self
    {
        $this->payableAmount = $payableAmount;

        return $this;
    }

    /**
     * Get get the rate.
     *
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }

    /**
     * Set get the rate.
     *
     * @param float $rate Get the rate.
     *
     * @return self
     */
    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }
}
