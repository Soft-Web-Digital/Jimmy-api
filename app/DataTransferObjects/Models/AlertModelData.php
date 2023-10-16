<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Models;

use App\Enums\AlertTargetUser;
use Carbon\Carbon;

class AlertModelData
{
    /**
     * The title.
     *
     * @var string|null
     */
    private string|null $title = null;

    /**
     * The body.
     *
     * @var string|null
     */
    private string|null $body = null;

    /**
     * The target user.
     *
     * @var AlertTargetUser|null
     */
    private AlertTargetUser|null $targetUser = null;

    /**
     * The dispatch datetime.
     *
     * @var Carbon|null
     */
    private Carbon|null $dispatchDatetime = null;

    /**
     * The channels.
     *
     * @var array<int, \App\Enums\AlertChannel>|null
     */
    private array|null $channels = null;

    /**
     * The user IDs.
     *
     * @var array<int, string>|null
     */
    private array|null $users = null;

    /**
     * Get the title.
     *
     * @return string|null
     */
    public function getTitle(): string|null
    {
        return $this->title;
    }

    /**
     * Set the title.
     *
     * @param string|null $title The title.
     *
     * @return self
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the body.
     *
     * @return string|null
     */
    public function getBody(): string|null
    {
        return $this->body;
    }

    /**
     * Set the body.
     *
     * @param string|null $body The body.
     *
     * @return self
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get the target user.
     *
     * @return AlertTargetUser|null
     */
    public function getTargetUser(): AlertTargetUser|null
    {
        return $this->targetUser;
    }

    /**
     * Set the target user.
     *
     * @param AlertTargetUser|string|null $targetUser The target user.
     *
     * @return self
     */
    public function setTargetUser($targetUser): self
    {
        $this->targetUser = isset($targetUser)
            ? (
                $targetUser instanceof AlertTargetUser
                    ? $targetUser
                    : AlertTargetUser::from($targetUser)
            )
            : $targetUser;

        return $this;
    }

    /**
     * Get the dispatch datetime.
     *
     * @return Carbon|null
     */
    public function getDispatchDatetime(): Carbon|null
    {
        return $this->dispatchDatetime;
    }

    /**
     * Set the dispatch datetime.
     *
     * @param Carbon|string|null $dispatchDatetime The dispatch datetime.
     *
     * @return self
     */
    public function setDispatchDatetime($dispatchDatetime): self
    {
        $this->dispatchDatetime = isset($dispatchDatetime)
            ? (
                $dispatchDatetime instanceof Carbon
                    ? $dispatchDatetime
                    : Carbon::parse($dispatchDatetime)
            )
            : $dispatchDatetime;

        return $this;
    }

    /**
     * Get the user IDs.
     *
     * @return array<int, string>|null
     */
    public function getUsers(): array|null
    {
        return $this->users;
    }

    /**
     * Set the user IDs.
     *
     * @param array<int, string>|null $users The user IDs.
     *
     * @return self
     */
    public function setUsers($users): self
    {
        $this->users = $users;

        return $this;
    }

    /**
     * Get the channels.
     *
     * @return array<int, \App\Enums\AlertChannel>|null
     */
    public function getChannels(): array|null
    {
        return $this->channels;
    }

    /**
     * Set the channels.
     *
     * @param array<int, \App\Enums\AlertChannel>|string|null $channels The channels.
     *
     * @return self
     */
    public function setChannels($channels): self
    {
        $this->channels = isset($channels)
            ? (is_string($channels) ? [$channels] : $channels)
            : null;

        return $this;
    }
}
