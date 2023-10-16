<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait ActivityLogTrait
{
    use LogsActivity;

    /**
     * Get the name of the log.
     *
     * @return string|null
     */
    public function getActivityLogTitle(): string|null
    {
        return $this->name ?? $this->reference ?? substr($this->id, 0, 8);
    }

    /**
     * Get activity log options.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName($this->getMorphClass())
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->dontSubmitEmptyLogs()
            ->logOnlyDirty();
    }

    /**
     * Customize the activity log before it is saved.
     *
     * @param \Spatie\Activitylog\Contracts\Activity $activity
     * @param string $eventName
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $logTitle = $this->getActivityLogTitle();

        $activity->description = Str::headline((new \ReflectionClass($this))->getShortName())
            . ($logTitle ? " ({$logTitle})" : '')
            . " was {$eventName}"
            . (
                $activity->causer
                    ? (" by {$activity->causer->full_name} [{$activity->causer_type}]")
                    : ''
            )
            . '.';

        if ($properties = $activity->properties) {
            if ($properties->has('attributes')) {
                $attributes = $properties->get('attributes');

                $hiddenAttributes = $this->getHidden();
                foreach ($hiddenAttributes as $hiddenAttribute) {
                    if (isset($attributes[$hiddenAttribute])) {
                        $attributes[$hiddenAttribute] = '########';
                    }
                }

                $properties->put('attributes', $attributes);
            }
            if ($properties->has('old')) {
                $old = $properties->get('old');

                $hiddenAttributes = $this->getHidden();
                foreach ($hiddenAttributes as $hiddenAttribute) {
                    if (isset($old[$hiddenAttribute])) {
                        $old[$hiddenAttribute] = '########';
                    }
                }

                $properties->put('old', $old);
            }
            $activity->properties = $properties;
        }
    }
}
