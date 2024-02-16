<?php

declare(strict_types=1);

namespace App\Services\Giftcard;

use App\DataTransferObjects\GiftcardBreakdownData;
use App\DataTransferObjects\Models\GiftcardModelData;
use App\Enums\GiftcardStatus;
use App\Enums\GiftcardTradeType;
use App\Enums\SystemDataCode;
use App\Events\Admin\AdminNotified;
use App\Events\GiftcardCreated;
use App\Exceptions\ExpectationFailedException;
use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\Giftcard;
use App\Models\GiftcardProduct;
use App\Models\SystemData;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Notifications\Admin\GiftcardUpdateNotification;
use App\Notifications\User\GiftcardApprovedNotification;
use App\Notifications\User\GiftcardDeclinedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GiftcardService
{
    /**
     * Get the breakdown for the giftcard.
     *
     * @param \App\DataTransferObjects\Models\GiftcardModelData $giftcardModelData
     * @param bool $completeApproval
     * @return \App\DataTransferObjects\GiftcardBreakdownData
     */
    public function breakdown(GiftcardModelData $giftcardModelData, bool $completeApproval = true): GiftcardBreakdownData
    {
        $serviceCharge = (float) SystemData::query()
            ->where(
                'code',
                $giftcardModelData->getTradeType() == GiftcardTradeType::SELL
                    ? SystemDataCode::GIFTCARD_SELL_SERVICE_CHARGE
                    : null
            )
            ->value('content');

        /** @var \App\Models\GiftcardProduct $giftcardProduct */
        $giftcardProduct = GiftcardProduct::query()->findOrFail($giftcardModelData->getGiftcardProductId());

        switch ($giftcardModelData->getTradeType()) {
            case GiftcardTradeType::SELL:
                $rate = $giftcardModelData->getRate() ?? $giftcardProduct->sell_rate;

                $payableAmount = $giftcardModelData->getAmount() * $rate;

                if ($completeApproval) {
                    $payableAmount = round($payableAmount + ($payableAmount * ($serviceCharge / 100)), 2);
                }

                break;

            default:
                throw new ExpectationFailedException('Giftcard trade type is unavailable at the moment');
        }

        return (new GiftcardBreakdownData())
            ->setRate($rate)
            ->setServiceCharge($serviceCharge)
            ->setPayableAmount($payableAmount);
    }

    /**
     * Create giftcards.
     *
     * @param \App\DataTransferObjects\Models\GiftcardModelData $giftcardModelData
     * @return object
     */
    public function create(GiftcardModelData $giftcardModelData): object
    {
        $breakdown = $this->breakdown($giftcardModelData);

        DB::beginTransaction();

        try {
            $files = $giftcardModelData->getCards();
            $currentFileIndex = 0;

            /** @var \App\Models\UserBankAccount $userBankAccount */
            $userBankAccount = UserBankAccount::query()->findOrFail($giftcardModelData->getUserBankAccountId());

            $groupTag = strtoupper($giftcardModelData->getGroupTag() ?: uniqid('GP'));
            $parentId = Giftcard::query()
                ->where('group_tag', $groupTag)
                ->where('user_id', $userBankAccount->user_id)
                ->oldest()
                ->value('id');

            foreach (range(1, $giftcardModelData->getQuantity()) as $index) {
                /** @var \App\Models\Giftcard $giftcard */
                $giftcard = Giftcard::query()->create([
                    'giftcard_product_id' => $giftcardModelData->getGiftcardProductId(),
                    'user_id' => $userBankAccount->user_id,
                    'parent_id' => $parentId,
                    'group_tag' => $groupTag,
                    'bank_id' => $userBankAccount->bank_id,
                    'account_name' => $userBankAccount->account_name,
                    'account_number' => $userBankAccount->account_number,
                    'reference' => strtoupper('GC' . bin2hex(random_bytes(5)) . time()),
                    'trade_type' => $giftcardModelData->getTradeType(),
                    'card_type' => $giftcardModelData->getCardType(),
                    'code' => $giftcardModelData->getUploadType() === 'code'
                        ? $giftcardModelData->getCodes()[$index - 1]
                        : null,
                    'pin' => $giftcardModelData->getUploadType() === 'code'
                        ? $giftcardModelData->getPins()[$index - 1]
                        : null,
                    'comment' => $giftcardModelData->getComment(),
                    'amount' => $giftcardModelData->getAmount(),
                    'service_charge' => $breakdown->getServiceCharge(),
                    'rate' => $breakdown->getRate(),
                    'payable_amount' => $breakdown->getPayableAmount(),
                ]);

                if ($index === 1 && $parentId === null) {
                    $parentId = $giftcard->id;
                }

                // Upload gift card files
                if ($giftcardModelData->getUploadType() === 'media') {
                    if (!isset($files) || count($files) < $giftcardModelData->getQuantity()) {
                        throw ValidationException::withMessages([
                            'cards' => [trans('validation.min.array', [
                                'attribute' => 'cards',
                                'min' => $giftcardModelData->getQuantity(),
                            ])],
                        ]);
                    }

                    $cardCount = (int) round(count($files) / $giftcardModelData->getQuantity());

                    while (isset($files[$currentFileIndex]) && $cardCount > 0) {
                        //                        /** @var \Illuminate\Http\UploadedFile $file */
                        $file = $files[$currentFileIndex];

                        //                        $giftcard->addMedia($file)->toMediaCollection();
                        $giftcard->addMediaFromUrl($file)->toMediaCollection();

                        $cardCount--;
                        $currentFileIndex++;
                    }
                }
            }

            /** @var Giftcard $giftcard */
            $giftcard = Giftcard::with([
                'children',
                'user:id,firstname,lastname,email',
                'giftcardProduct' => fn ($query) => $query->with([
                    'giftcardCategory' => fn ($query) => $query->select(['id', 'name', 'icon'])->with([
                        'admins' => fn ($query) => $query->select(['id', 'firstname', 'lastname'])
                    ]),
                    'country:id,name,flag_url',
                    'currency:id,name,code'
                ]),
            ])->find($parentId);

            if (count($giftcard->children) > 0) {
                $giftcard->amount += $giftcard->children->sum('amount');
                $giftcard->status = 'multiple'; // @phpstan-ignore-line
            }

            unset($giftcard->children);

            event(new GiftcardCreated($giftcard->toArray()));

            event(new AdminNotified(new GiftcardUpdateNotification(
                'New Giftcard Sale ' . Str::of('Transaction')->plural($giftcardModelData->getQuantity())->toString(),
                'Giftcard sale ' . Str::of('transaction')->plural($giftcardModelData->getQuantity())->toString()
                    . ' created. Kindly review them on your dashboard'
            ), $giftcard));

            DB::commit();

            return $giftcard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete the giftcard.
     *
     * @param \App\Models\Giftcard $giftcard
     * @return void
     */
    public function delete(Giftcard $giftcard): void
    {
        throw_if(
            $giftcard->status !== GiftcardStatus::PENDING,
            NotAllowedException::class,
            "Giftcard status is currently: {$giftcard->status->value}."
        );

        // Delete the giftcard secret credentials
        DB::beginTransaction();

        try {
            $giftcard->forceFill([
                'code' => null,
                'pin' => null,
                'deleted_at' => now(),
            ])->saveOrFail();

            $giftcard->media()->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Decline the giftcard transaction.
     *
     * @param \App\Models\Giftcard $giftcard
     * @param \App\Models\Admin $admin
     * @param string|null $reviewNote
     * @param array<int, string>|null $reviewProof
     * @return \App\Models\Giftcard
     */
    public function decline(
        Giftcard $giftcard,
        Admin $admin,
        string $reviewNote = null,
        ?array $reviewProof = null,
        bool $all = false
    ): Giftcard {
        throw_if(
            $giftcard->status !== GiftcardStatus::PENDING,
            NotAllowedException::class,
            "Giftcard status is currently: {$giftcard->status->value}."
        );

        DB::beginTransaction();

        try {
            if ($all) {
                foreach ($giftcard->children as $child) {
                    $child->updateOrFail([
                        'status' => GiftcardStatus::DECLINED,
                        'review_note' => $reviewNote,
                        'review_proof' => $reviewProof,
                        'reviewed_by' => $admin->id,
                        'reviewed_at' => now(),
                    ]);
                }
            }

            $giftcard->updateOrFail([
                'status' => GiftcardStatus::DECLINED,
                'review_note' => $reviewNote,
                'review_proof' => $reviewProof,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $giftcard->notifyUser(new GiftcardDeclinedNotification(
                $giftcard->trade_type,
                $giftcard->reference,
                $giftcard->review_note,
                $giftcard->review_proof
            ));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $giftcard->withoutRelations()->refresh();
    }

    /**
     * Approve the giftcard transaction.
     *
     * @param \App\Models\Giftcard $giftcard
     * @param \App\Models\Admin $admin
     * @param bool $completeApproval
     * @param float|null $reviewAmount
     * @param string|null $reviewNote
     * @param array<int, string>|null $reviewProof
     * @return \App\Models\Giftcard
     */
    public function approve(
        Giftcard $giftcard,
        Admin $admin,
        bool $completeApproval,
        float|null $reviewAmount = null,
        string $reviewNote = null,
        ?array $reviewProof = null,
        bool $all = false
    ): Giftcard {
        throw_if(
            !$all && $giftcard->status !== GiftcardStatus::PENDING,
            NotAllowedException::class,
            "Giftcard status is currently: {$giftcard->status->value}."
        );

        DB::beginTransaction();

        try {
            $newBreakdown = null;
            $status = GiftcardStatus::APPROVED;

            if (!$completeApproval) {
                //                $newBreakdown = $this->breakdown(
                //                    (new GiftcardModelData())
                //                        ->setGiftcardProductId($giftcard->giftcard_product_id)
                //                        ->setTradeType($giftcard->trade_type)
                //                        ->setAmount($giftcard->amount)
                //                        ->setReviewAmount($reviewAmount),
                //                    false
                //                );
                $status = GiftcardStatus::PARTIALLYAPPROVED;
            }

            if ($all) {
                foreach ($giftcard->children as $child) {
                    if ($child->status == GiftcardStatus::PENDING) {
                        $child->updateOrFail([
                            'status' => $status,
                            'review_note' => $reviewNote,
                            'review_proof' => $reviewProof,
//                            'review_rate' => $newBreakdown?->getRate() ?? $giftcard->rate,
                            'review_amount' => $reviewAmount,
//                            'payable_amount' => $newBreakdown?->getPayableAmount() ?? $giftcard->payable_amount,
                            'reviewed_by' => $admin->id,
                            'reviewed_at' => now(),
                        ]);
                    }
                }
            }

            // Update the giftcard
            $giftcard->updateOrFail([
                'status' => $giftcard->status == GiftcardStatus::PENDING ? $status : $giftcard->status,
                'review_note' => $reviewNote,
                'review_proof' => $reviewProof,
//                'review_rate' => $newBreakdown?->getRate() ?? $giftcard->rate,
                'review_amount' => $reviewAmount,
//                'payable_amount' => $newBreakdown?->getPayableAmount() ?? $giftcard->payable_amount,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $giftcard->notifyUser(new GiftcardApprovedNotification($giftcard));

            if ($completeApproval) {
                $giftcard->creditReferee();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $giftcard->withoutRelations()->refresh();
    }

    /**
     * Get the statistics for the user.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStats(User $user)
    {
        $stats = Giftcard::query()
            ->select([
                DB::raw('COUNT(*) as total_transactions_count'),
                DB::raw(
                    'CAST(SUM('
                    . 'CASE WHEN giftcards.status = \'' . GiftcardStatus::APPROVED->value . '\''
                    . ' OR giftcards.status = \'' . GiftcardStatus::PARTIALLYAPPROVED->value . '\''
                    . ' THEN giftcards.payable_amount ELSE 0 END'
                    . ') AS FLOAT) as total_transactions_amount'
                ),
            ]);

        foreach (GiftcardTradeType::values() as $tradeType) {
            $stats->addSelect(
                DB::raw(
                    "COUNT(CASE WHEN `trade_type` = '{$tradeType}' THEN 1 ELSE NULL END) as total_{$tradeType}_count"
                ),
                DB::raw(
                    "CAST(SUM(CASE WHEN `trade_type` = '{$tradeType}' THEN payable_amount ELSE 0 END) AS FLOAT)"
                    . " as total_{$tradeType}_amount"
                ),
            );
        }

        foreach (GiftcardStatus::values() as $status) {
            $stats->addSelect(
                DB::raw(
                    "COUNT(CASE WHEN `status` = '{$status}' THEN 1 ELSE NULL END) as total_{$status}_count"
                ),
                DB::raw(
                    "CAST(SUM(CASE WHEN `status` = '{$status}' THEN payable_amount ELSE 0 END) AS FLOAT)"
                    . " as total_{$status}_amount"
                ),
            );

            foreach (GiftcardTradeType::values() as $tradeType) {
                $stats
                    ->addSelect(
                        DB::raw(
                            "COUNT(CASE WHEN `trade_type` = '{$tradeType}' AND `status` = '{$status}' THEN 1"
                            . " ELSE NULL END) as total_{$status}_{$tradeType}_count"
                        ),
                        DB::raw(
                            "CAST(SUM(CASE WHEN `trade_type` = '{$tradeType}' AND `status` = '{$status}' THEN"
                            . " payable_amount ELSE 0 END) AS FLOAT) as total_{$status}_{$tradeType}_amount"
                        ),
                    )
                ;
            }
        }

        return $stats
            ->where('user_id', $user->id)
            ->get();
    }
}
