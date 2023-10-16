<?php

declare(strict_types=1);

namespace App\Services\Alert;

use App\DataTransferObjects\Models\AlertModelData;
use App\Enums\AlertStatus;
use App\Enums\AlertTargetUser;
use App\Exceptions\ExpectationFailedException;
use App\Exceptions\NotAllowedException;
use App\Models\Admin;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AlertService
{
    /**
     * Create a new alert.
     *
     * @param \App\DataTransferObjects\Models\AlertModelData $alertModelData
     * @return \App\Models\Alert
     */
    public function create(AlertModelData $alertModelData): Alert
    {
        throw_if(
            !(auth('api_admin')->user() instanceof Admin),
            NotAllowedException::class,
            'Only admins can create alerts'
        );

        DB::beginTransaction();

        try {
            /** @var \App\Models\Alert $alert */
            $alert = Alert::query()->create([
                'title' => $alertModelData->getTitle(),
                'body' => $alertModelData->getBody(),
                'target_user' => $alertModelData->getTargetUser(),
                'dispatched_at' => $alertModelData->getDispatchDatetime(),
                'channels' => $alertModelData->getChannels(),
                'creator_id' => auth('api_admin')->user()->id,
            ]);

            /** @var \App\Enums\AlertTargetUser $targetUser */
            $targetUser = $alert->target_user;

            switch ($targetUser) {
                case AlertTargetUser::SPECIFIC:
                    throw_if(
                        is_null($alertModelData->getUsers()) || count($alertModelData->getUsers()) < 1,
                        ExpectationFailedException::class,
                        'Provide at least one user to alert'
                    );

                    $alert->users()->sync(
                        User::select('id')->where($targetUser->query($alertModelData->getUsers()))->get()
                    );
                    break;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $alert->withoutRelations()->refresh();
    }

    /**
     * Update alert.
     *
     * @param \App\Models\Alert $alert
     * @param \App\DataTransferObjects\Models\AlertModelData $alertModelData
     * @return \App\Models\Alert
     */
    public function update(Alert $alert, AlertModelData $alertModelData): Alert
    {
        throw_if(
            $alert->status !== AlertStatus::PENDING,
            NotAllowedException::class,
            "Alert's current status is {$alert->status->value}, therefore, cannot be updated."
        );

        DB::beginTransaction();

        try {
            $alert->updateOrFail([
                'title' => $alertModelData->getTitle() ?? $alert->title,
                'body' => $alertModelData->getBody() ?? $alert->body,
                'target_user' => $alertModelData->getTargetUser() ?? $alert->target_user,
                'dispatched_at' => $alertModelData->getDispatchDatetime() ?? $alert->dispatched_at,
                'channels' => $alertModelData->getChannels() ?? $alert->channels,
            ]);

            if ((!is_null($alertModelData->getUsers()))) {
                /** @var \App\Enums\AlertTargetUser $targetUser */
                $targetUser = $alert->target_user;

                switch ($targetUser) {
                    case AlertTargetUser::SPECIFIC:
                        $alert->users()->sync(
                            User::select('id')->where($targetUser->query($alertModelData->getUsers()))->get()
                        );
                        break;
                }
            }

            if ($alert->target_user !== AlertTargetUser::SPECIFIC) {
                $alert->users()->detach();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $alert->withoutRelations()->refresh();
    }
}
