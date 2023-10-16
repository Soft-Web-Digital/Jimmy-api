<?php

namespace App\Models;

use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Spatie\Activitylog\Models\Activity as ModelsActivity;

class ActivityLog extends ModelsActivity
{
    use UUID;
    use MorphMapTrait;
}
