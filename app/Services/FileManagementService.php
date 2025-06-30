<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class FileManagementService
{
    /**
     * Register services.
     *
     * @return void
     */

     public static function setPathStorage($application_id,$type='application'){
        $current = Carbon::now();
        return $current->year.'-'.$current->month.'/'.$application_id.'/'.$type;
     }

     public static function getPathStorage($application_id, $type = 'application'){
        return self::setPathStorage($application_id, $type);
     }



     public static function encryptedFileName(){

     }

     public static function generateFilename($filename, $application_id, $fileType='',$mimeType = 'pdf'){
        $carbon = Carbon::now();
        $filename = explode('.',$filename)[0];
        $date = $carbon->format('Ymd');
        $time = $carbon->format('His');
        $timestamp = $carbon->timestamp;
        $new_filename = $fileType.'-'.$filename.'-'.$application_id.'-'.$date.'-'.$time.$timestamp.'.'.$mimeType;
        return $new_filename;
     }





















    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}
}
