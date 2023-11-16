<?php

namespace App\Http\Controllers\Generic;

use ImageKit\ImageKit;
use App\Http\Controllers\Controller;

class UploadSignature extends Controller
{
    public function generateSignature()
    {

        $imageKit = new ImageKit(
            env('IMAGEKIT_PUBLIC_KEY'),
            env('IMAGEKIT_PRIVATE_KEY'),
            env('IMAGEKIT_URL_ENDPOINT')
        );

        $signature = $imageKit->getAuthenticationParameters();

        return response()->json($signature);
    }
}
