<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     public static function storeFile($content ){

        $get_path = FileManagementService::getPathStorage(self::$application->id, 'application');
        $get_filename = FileManagementService::generateFilename(self::$application->activity_name, self::$application->id, 'TOR');
        $target_dir = $get_path . '/' . $get_filename;
        if ($content) {
            $res = Storage::disk('minio')->put($target_dir, $content);
            if ($res) {
                FileManagementService::storeFile(self::$application, $get_path, explode($get_filename)[1]);
            }
            Log::info('END CONVERT FILE TO PDF - SUCCESS');
            return $res;
        }
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

    public static function convertToPdf($file_url = null, $is_replace = false)
    {
        Log::info('START CONVERT FILE TO PDF');

        $path = parse_url($file_url, PHP_URL_PATH);
        $fileInfo = pathinfo($path);
        $extension = $fileInfo['extension'];
        $content = self::onlyOfficeConversion($extension, 'pdf', $file_url);
        if ($content) {
            Log::info('END CONVERT FILE TO PDF - SUCCESS');
            return $content;
        }
        Log::info('END CONVERT FILE TO PDF - FAILED');
        return $content;
    }

    public static function onlyOfficeConversion($from, $to, $fileUrl, $key = null)
    {
        $config = [
            'fileType' => $from,
            'outputtype' => $to,
            'url' => $fileUrl,
            'key' => $key ?: (string)now()->getTimestampMs()
        ];

        $content = "";
        if (config('onlyoffice.DOC_SERV_SITE_URL')) {
            $conversionUrl = config('onlyoffice.DOC_SERV_SITE_URL') . 'ConvertService.ashx';
            $response = Http::timeout(90)->withBody(json_encode($config, JSON_UNESCAPED_SLASHES), 'application/json')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Thunder Client (https://www.thunderclient.com)',
                    'Content-Type' => 'application/json',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection' => 'keep-alive',
                ])->withoutVerifying()->post($conversionUrl);
            $json = $response->json();

            if ($response->status() == 200 && $json && isset($json['fileUrl'])) {
                $content = file_get_contents($json['fileUrl']);
            }
        }
        return $content;
    }





















    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}
}
