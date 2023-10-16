<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use App\Enums\AssetTransactionTradeType;

class AssetTransactionModelData
{
    /**
     * The network ID.
     *
     * @var string|null
     */
    private string|null $networkId = null;

    /**
     * The asset ID.
     *
     * @var string|null
     */
    private string|null $assetId = null;

    /**
     * The user bank account ID.
     *
     * @var string|null
     */
    private string|null $userBankAccountId = null;

    /**
     * The wallet address.
     *
     * @var string|null
     */
    private string|null $walletAddress = null;

    /**
     * The asset amount.
     *
     * @var float|null
     */
    private float|null $assetAmount = null;

    /**
     * The trade type.
     *
     * @var \App\Enums\AssetTransactionTradeType|null
     */
    private AssetTransactionTradeType|null $tradeType = null;

    /**
     * The comment.
     *
     * @var string|null
     */
    private string|null $comment = null;

    /**
     * The rate.
     *
     * @var float|null
     */
    private float|null $rate = null;

    /**
     * The review amount.
     *
     * @var float|null
     */
    private float|null $reviewAmount = null;

    /**
     * Get the network ID.
     *
     * @return string|null
     */
    public function getNetworkId(): string|null
    {
        return $this->networkId;
    }

    /**
     * Set the network ID.
     *
     * @param string|null $networkId The network ID.
     *
     * @return self
     */
    public function setNetworkId($networkId): self
    {
        $this->networkId = $networkId;

        return $this;
    }

    /**
     * Get the asset ID.
     *
     * @return string|null
     */
    public function getAssetId(): string|null
    {
        return $this->assetId;
    }

    /**
     * Set the asset ID.
     *
     * @param string|null $assetId The asset ID.
     *
     * @return self
     */
    public function setAssetId($assetId): self
    {
        $this->assetId = $assetId;

        return $this;
    }

    /**
     * Get the user bank account ID.
     *
     * @return string|null
     */
    public function getUserBankAccountId(): string|null
    {
        return $this->userBankAccountId;
    }

    /**
     * Set the user bank account ID.
     *
     * @param string|null $userBankAccountId The user bank account ID.
     *
     * @return self
     */
    public function setUserBankAccountId($userBankAccountId): self
    {
        $this->userBankAccountId = $userBankAccountId;

        return $this;
    }

    /**
     * Get the wallet address.
     *
     * @return string|null
     */
    public function getWalletAddress(): string|null
    {
        return $this->walletAddress;
    }

    /**
     * Set the wallet address.
     *
     * @param string|null $walletAddress The wallet address.
     *
     * @return self
     */
    public function setWalletAddress($walletAddress): self
    {
        $this->walletAddress = $walletAddress;

        return $this;
    }

    /**
     * Get the asset amount.
     *
     * @return float|null
     */
    public function getAssetAmount(): float|null
    {
        return $this->assetAmount;
    }

    /**
     * Set the asset amount.
     *
     * @param float|null $assetAmount The asset amount.
     *
     * @return self
     */
    public function setAssetAmount($assetAmount): self
    {
        $this->assetAmount = $assetAmount;

        return $this;
    }

    /**
     * Get the trade type.
     *
     * @return \App\Enums\AssetTransactionTradeType|null
     */
    public function getTradeType(): AssetTransactionTradeType|null
    {
        return $this->tradeType;
    }

    /**
     * Set the trade type.
     *
     * @param \App\Enums\AssetTransactionTradeType|string|null $tradeType The trade type.
     *
     * @return self
     */
    public function setTradeType($tradeType): self
    {
        if (!is_null($tradeType)) {
            $this->tradeType = $tradeType instanceof AssetTransactionTradeType
                ? $tradeType
                : AssetTransactionTradeType::from($tradeType);
        }

        return $this;
    }

    /**
     * Get the comment.
     *
     * @return string|null
     */
    public function getComment(): string|null
    {
        return $this->comment;
    }

    /**
     * Set the comment.
     *
     * @param string|null $comment The comment.
     *
     * @return self
     */
    public function setComment($comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get the rate.
     *
     * @return float|null
     */
    public function getRate(): float|null
    {
        return $this->rate;
    }

    /**
     * Set the rate.
     *
     * @param float|null $rate The rate.
     *
     * @return self
     */
    public function setRate($rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get the rate.
     *
     * @return float|null
     */
    public function getReviewAmount(): float|null
    {
        return $this->reviewAmount;
    }

    /**
     * Set the rate.
     *
     * @param float|null $amount
     * @return self
     */
    public function setReviewAmount(?float $amount): self
    {
        $this->reviewAmount = $amount;

        return $this;
    }
}
