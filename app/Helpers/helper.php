<?php

use Illuminate\Http\UploadedFile;

if (!function_exists('saveFileAndReturnPath')) {
    function saveFileAndReturnPath(UploadedFile $file, string $filename = null, string $path = 'assets/img'): string
    {
        $filename = $filename ?? time() . rand(1111, 9999) .'.'. $file->getClientOriginalExtension();
        $file->move($path, $filename);

        return $path . '/' . $filename;
    }
}
