<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use App\Enums\GiftcardCardType;
use App\Enums\GiftcardTradeType;

class GiftcardModelData
{
    /**
     * The giftcard product ID.
     *
     * @var string
     */
    private string $giftcardProductId;

    /**
     * The user bank account ID.
     *
     * @var string
     */
    private string $userBankAccountId;

    /**
     * The giftcard trade type.
     *
     * @var \App\Enums\GiftcardTradeType
     */
    private GiftcardTradeType $tradeType;

    /**
     * The giftcard card type.
     *
     * @var \App\Enums\GiftcardCardType
     */
    private GiftcardCardType $cardType;

    /**
     * The amount.
     *
     * @var float
     */
    private float $amount;

    /**
     * The quantity.
     *
     * @var int
     */
    private int $quantity = 1;

    /**
     * The comment.
     *
     * @var string|null
     */
    private string|null $comment = null;

    /**
     * The codes.
     *
     * @var array<int, string>|null
     */
    private array|null $codes = null;

    /**
     * The PINs.
     *
     * @var array<int, string>|null
     */
    private array|null $pins = null;

    /**
     * The cards.
     *
     * @var array<int, string>|null
//     * @var array<int, \Illuminate\Http\UploadedFile>|null
     */
    private array|null $cards = null;

    /**
     * The rate.
     *
     * @var float|null
     */
    private float|null $rate = null;

    /**
     * The upload type.
     *
     * @var string|null
     */
    private string|null $uploadType = null;

    /**
     * The group tag.
     *
     * @var string|null
     */
    private string|null $groupTag = null;

    /**
     * The review amount.
     *
     * @var float|null
     */
    private float|null $reviewAmount = null;

    /**
     * Get the giftcard product ID.
     *
     * @return string
     */
    public function getGiftcardProductId(): string
    {
        return $this->giftcardProductId;
    }

    /**
     * Set the giftcard product ID.
     *
     * @param string $giftcardProductId The giftcard product ID.
     *
     * @return self
     */
    public function setGiftcardProductId(string $giftcardProductId): self
    {
        $this->giftcardProductId = $giftcardProductId;

        return $this;
    }

    /**
     * Get the user bank account ID.
     *
     * @return string
     */
    public function getUserBankAccountId(): string
    {
        return $this->userBankAccountId;
    }

    /**
     * Set the user bank account ID.
     *
     * @param string $userBankAccountId The user bank account ID.
     *
     * @return self
     */
    public function setUserBankAccountId(string $userBankAccountId): self
    {
        $this->userBankAccountId = $userBankAccountId;

        return $this;
    }

    /**
     * Get the giftcard trade type.
     *
     * @return \App\Enums\GiftcardTradeType
     */
    public function getTradeType(): \App\Enums\GiftcardTradeType
    {
        return $this->tradeType;
    }

    /**
     * Set the giftcard trade type.
     *
     * @param \App\Enums\GiftcardTradeType|string $tradeType The giftcard trade type.
     *
     * @return self
     */
    public function setTradeType(GiftcardTradeType|string $tradeType): self
    {
        $this->tradeType = $tradeType instanceof GiftcardTradeType
            ? $tradeType
            : GiftcardTradeType::from($tradeType);

        return $this;
    }

    /**
     * Get the giftcard card type.
     *
     * @return \App\Enums\GiftcardCardType
     */
    public function getCardType(): \App\Enums\GiftcardCardType
    {
        return $this->cardType;
    }

    /**
     * Set the giftcard card type.
     *
     * @param \App\Enums\GiftcardCardType|string $cardType The giftcard card type.
     *
     * @return self
     */
    public function setCardType(GiftcardCardType|string $cardType): self
    {
        $this->cardType = $cardType instanceof GiftcardCardType
            ? $cardType
            : GiftcardCardType::from($cardType);

        return $this;
    }

    /**
     * Get the amount.
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Set the amount.
     *
     * @param float $amount The amount.
     *
     * @return self
     */
    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

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
     * Get the quantity.
     *
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set the quantity.
     *
     * @param int $quantity The quantity.
     *
     * @return self
     */
    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity < 1 ? 1 : $quantity;

        return $this;
    }

    /**
     * Get the codes.
     *
     * @return array<int, string>|null
     */
    public function getCodes(): array|null
    {
        return $this->codes;
    }

    /**
     * Set the codes.
     *
     * @param array<int, string>|null $codes The codes.
     *
     * @return self
     */
    public function setCodes($codes): self
    {
        $this->codes = $codes;

        return $this;
    }

    /**
     * Get the PINs.
     *
     * @return array<int, string>|null
     */
    public function getPins(): array|null
    {
        return $this->pins;
    }

    /**
     * Set the PINs.
     *
     * @param array<int, string>|null $pins The PINs.
     *
     * @return self
     */
    public function setPins($pins): self
    {
        $this->pins = $pins;

        return $this;
    }

    /**
     * Get the cards.
     *
     * @return array<int, string>|null
//     * @return array<int, \Illuminate\Http\UploadedFile>|null
     */
    public function getCards(): array|null
    {
        return $this->cards;
    }

    /**
     * Set the cards.
     *
     * @param array<int, string>|null $cards The cards.
//     * @param array<int, \Illuminate\Http\UploadedFile>|null $cards The cards.
     *
     * @return self
     */
    public function setCards($cards): self
    {
        $this->cards = $cards;

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
     * Get the upload type.
     *
     * @return string|null
     */
    public function getUploadType(): string|null
    {
        return $this->uploadType;
    }

    /**
     * Set the upload type.
     *
     * @param string|null $uploadType The upload type.
     *
     * @return self
     */
    public function setUploadType($uploadType): self
    {
        $this->uploadType = $uploadType;

        return $this;
    }

    /**
     * Get the group tag.
     *
     * @return string|null
     */
    public function getGroupTag(): string|null
    {
        return $this->groupTag;
    }

    /**
     * Set the group tag.
     *
     * @param string|null $groupTag The group tag.
     *
     * @return self
     */
    public function setGroupTag($groupTag): self
    {
        $this->groupTag = $groupTag;

        return $this;
    }

    /**
     * Get the review amount.
     *
     * @return float|null
     */
    public function getReviewAmount(): float|null
    {
        return $this->reviewAmount;
    }

    /**
     * Set the group tag.
     *
     * @param float|null $reviewAmount The review amount.
     *
     * @return self
     */
    public function setReviewAmount(?float $reviewAmount): self
    {
        $this->reviewAmount = $reviewAmount;

        return $this;
    }
}
