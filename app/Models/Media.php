<?php

namespace App\Models;

use App\Traits\MorphMapTrait;
use App\Traits\UUID;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseModel;

class Media extends BaseModel
{
    use UUID;
    use MorphMapTrait;
}
