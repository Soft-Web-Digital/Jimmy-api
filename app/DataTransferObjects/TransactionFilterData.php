<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class TransactionFilterData
{
    /**
     * The user ID.
     *
     * @var string|null
     */
    private string|null $userId = null;

    /**
     * Records per page.
     *
     * @var int|null
     */
    private int|null $perPage = null;

    /**
     * The creation date(s).
     *
     * @var string|null
     */
    private string|null $creationDate = null;

    /**
     * The payable amount(s).
     *
     * @var string|null
     */
    private string|null $payableAmount = null;

    /**
     * The status(es).
     *
     * @var string|null
     */
    private string|null $status = null;

    /**
     * Get the user ID.
     *
     * @return string|null
     */
    public function getUserId(): string|null
    {
        return $this->userId;
    }

    /**
     * Set the user ID.
     *
     * @param string|null $userId The user.
     *
     * @return self
     */
    public function setUserId($userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get records per page.
     *
     * @return int|null
     */
    public function getPerPage(): int|null
    {
        return $this->perPage;
    }

    /**
     * Set records per page.
     *
     * @param int|null $perPage Records per page.
     *
     * @return self
     */
    public function setPerPage($perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Get the creation date(s).
     *
     * @return string|null
     */
    public function getCreationDate(): string|null
    {
        return $this->creationDate;
    }

    /**
     * Set the creation date(s).
     *
     * @param string|null $creationDate The creation date(s).
     *
     * @return self
     */
    public function setCreationDate($creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get the payable amount(s).
     *
     * @return string|null
     */
    public function getPayableAmount(): string|null
    {
        return $this->payableAmount;
    }

    /**
     * Set the payable amount(s).
     *
     * @param string|null $payableAmount The payable amount(s).
     *
     * @return self
     */
    public function setPayableAmount($payableAmount): self
    {
        $this->payableAmount = $payableAmount;

        return $this;
    }

    /**
     * Get the status.
     *
     * @return string|null
     */
    public function getStatus(): string|null
    {
        return $this->status;
    }

    /**
     * Set the status(es).
     *
     * @param string|null $status The status(es).
     *
     * @return self
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }
}
