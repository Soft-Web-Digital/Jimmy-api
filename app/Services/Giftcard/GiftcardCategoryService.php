<?php

declare(strict_types=1);

namespace App\Services\Giftcard;

use App\Models\GiftcardCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\ExpectationFailedException;
use App\DataTransferObjects\Models\GiftcardCategoryModelData;

class GiftcardCategoryService
{
    /**
     * Create a giftcard category.
     *
     * @param \App\DataTransferObjects\Models\GiftcardCategoryModelData $giftcardCategoryModelData
     * @return \App\Models\GiftcardCategory
     */
    public function create(GiftcardCategoryModelData $giftcardCategoryModelData): GiftcardCategory
    {
        // Upload icon
        $icon = null;
        if ($giftcardCategoryModelData->getIcon() instanceof \Illuminate\Http\UploadedFile) {
            // $path = $giftcardCategoryModelData->getIcon()
            //     ->storeAs('icons', strtolower(
            //         'GCC' . str_replace(' ', '', $giftcardCategoryModelData->getName())
            //         . '.' . $giftcardCategoryModelData->getIcon()->extension()
            //     ));

            // throw_if($path === false, ExpectationFailedException::class, 'Icon could not be uploaded');

            // $icon = Storage::url($path);

            $icon = saveFileAndReturnPath($giftcardCategoryModelData->getIcon());
        }

        DB::beginTransaction();

        try {
            /** @var \App\Models\GiftcardCategory $giftcardCategory */
            $giftcardCategory = GiftcardCategory::query()->create([
                'name' => $giftcardCategoryModelData->getName(),
                'icon' => $icon,
                'sale_term' => $giftcardCategoryModelData->getSaleTerm(),
                'sale_activated_at' => now(),
            ]);

            if ($giftcardCategoryModelData->getCountryIds()) {
                $giftcardCategory->countries()->sync($giftcardCategoryModelData->getCountryIds());
            }

            if ($giftcardCategoryModelData->getAdminIds()) {
                $giftcardCategory->admins()->sync($giftcardCategoryModelData->getAdminIds());
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $giftcardCategory->refresh();
    }

    /**
     * Update the giftcard category.
     *
     * @param \App\Models\GiftcardCategory $giftcardCategory
     * @param \App\DataTransferObjects\Models\GiftcardCategoryModelData $giftcardCategoryModelData
     * @return \App\Models\GiftcardCategory
     */
    public function update(
        GiftcardCategory $giftcardCategory,
        GiftcardCategoryModelData $giftcardCategoryModelData
    ): GiftcardCategory {
        $icon = $giftcardCategory->icon;

        if ($giftcardCategoryModelData->getIcon() instanceof \Illuminate\Http\UploadedFile) {
            // $path = $giftcardCategoryModelData->getIcon()
            //     ->storeAs('icons', strtolower(
            //         'GCC' . str_replace(' ', '', $giftcardCategory->name)
            //         . '.' . $giftcardCategoryModelData->getIcon()->extension()
            //     ));

            // throw_if($path === false, ExpectationFailedException::class, 'Icon could not be uploaded');

            // $icon = Storage::url($path);

            $icon = saveFileAndReturnPath($giftcardCategoryModelData->getIcon());
        }

        DB::beginTransaction();

        try {
            $giftcardCategory->updateOrFail([
                'name' => (bool) $giftcardCategory->service_provider
                    ? $giftcardCategory->name
                    : ($giftcardCategoryModelData->getName() ?? $giftcardCategory->name),
                'icon' => $icon,
                'sale_term' => $giftcardCategoryModelData->getSaleTerm() ?? $giftcardCategory->sale_term,
                'purchase_term' => $giftcardCategoryModelData->getPurchaseTerm() ?? $giftcardCategory->purchase_term,
            ]);

            if ((bool) $giftcardCategory->service_provider === false && $giftcardCategoryModelData->getCountryIds()) {
                $giftcardCategory->countries()->toggle($giftcardCategoryModelData->getCountryIds());
            }

            if ($giftcardCategoryModelData->getAdminIds()) {
                $giftcardCategory->admins()->sync($giftcardCategoryModelData->getAdminIds());
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $giftcardCategory->refresh();
    }
}
