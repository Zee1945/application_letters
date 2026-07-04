<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use App\Models\Application;
use App\Models\Files;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Sqids\Sqids;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class FileManagementService
{
    /**
     * Register services.
     *
     * @return void
     */

     public static function setPathStorage($application,$type='letter'){
        $created = Carbon::parse($application->created_at);
        return $created->year.'-'.$created->month.'/'.$application->id.'/'.$type;
     }

     public static function getPathStorage($application, $type = 'letter'){
        return self::setPathStorage($application, $type);
     }



     public static function encrypted($str){


     }





     public static function storeFiles($metafile = [],$application,$trans_type,$temp_dir=''){
         if (count($metafile)) {
            $moveFileStorage = Storage::disk('minio')->put($metafile['path'],$metafile['content']);

            if ($moveFileStorage) {
                $data = [
                    'filename' => $metafile['fileName'],
                    'encrypted_filename' => Crypt::encryptString($metafile['fileName']),
                    'mimetype' => $metafile['mimeType'],
                    'belongs_to' => $trans_type,
                    'path' => $metafile['path'],
                    'storage_type' => 'minio',
                    'filesize' => $metafile['size'],
                    'application_id' => $application->id,
                    'department_id' => $application->department_id,
                    'created_by' => $application->created_by,
                    'updated_by' => $application->created_by
                ];
                $res = Files::create($data);
                if (!$res) {
                    return ['status'=>false,'message'=>'failed to store new file data','data'=>[]];
                }
                if ($temp_dir) {
                    Storage::disk('minio')->delete($temp_dir);
                }
             return ['status'=>true,'message'=>'success store new data','data'=>$res];
            }
        }
        return ['status' => false, 'message' => 'failed to store new file data, metadata is empty', 'data' => []];
    }


     public static function find($id){
        return Files::find($id);
     }
     public static function storeFileApplication($content,$application,$trans_type,$file_code=null,$app_file=null,$mime_type='pdf',$target_column='file_id',$status_ready=3){

        $get_path = FileManagementService::getPathStorage($application, $trans_type);
        $clean_activity_name = preg_replace('/[\/\\\\\?\%\*\:\|\"<>\.]/', '-', $application->activity_name);
        list($filename,$ext) =  explode('.',FileManagementService::generateFilename($clean_activity_name,$application, $file_code,$app_file,$mime_type));
        $target_dir = $get_path . '/' . $filename.'.'.$ext;
        if ($content) {
            $res = Storage::disk('minio')->put($target_dir, $content);
            // dd($res);
            if ($res) {
                $mime_type = Storage::disk('minio')->mimeType($target_dir);
                $fileSize = Storage::disk('minio')->size($target_dir);
                try {
                    DB::beginTransaction();
                    $data = [
                        'filename'=> $filename.'.'.$ext,
                        'encrypted_filename'=> Crypt::encryptString($filename),
                        'mimetype'=> $mime_type,
                        'belongs_to'=> $trans_type,
                        'path'=> $get_path,
                        'storage_type'=>'minio',
                        'filesize'=>$fileSize,
                        'application_id'=>$application->id,
                        'department_id'=>$application->department_id,
                        'created_by'=>$application->created_by,
                        'updated_by'=>$application->created_by
                    ];
                    $res = Files::create($data);



                    if (!$res) {
                        DB::rollBack();
                        return ['status' => false, 'message' => 'Gagal Simpan File','data'=>null];
                    }
                    $update_file_type = $application->applicationFiles()
                        ->withFileCodeAndParent($file_code);
                    if ($app_file->participant_id) {
                        $update_file_type= $update_file_type->where('participant_id',$app_file->participant_id);
                    }    
                    $update_file_type = $update_file_type->first();

                    // Simpan id file lama SEBELUM ditimpa, untuk dihapus nanti
                    // (hanya setelah seluruh proses simpan file baru sukses & commit).
                    $old_file_id = $update_file_type->{$target_column};

                    $update_file_type->{$target_column} = $res->id;
                    $update_file_type->status_ready = $status_ready;
                    $update_file_type->save();



                    DB::commit();

                    // Semua proses berhasil → baru bersihkan dokumen lama.
                    // Dilakukan setelah commit agar bila penyimpanan file baru gagal,
                    // file lama tetap utuh (tidak ada kehilangan data).
                    self::deleteOldFileIfUnused($old_file_id, $res->id);

                    return ['status'=>true,'message'=> 'Berhasil Simpan File','data' => $res];
                } catch (\Throwable $th) {
                    //throw $th;
                    DB::rollBack();
                    Log::error('Gagal menyimpan file: ' . $th->getMessage(), [
                        'application_id' => $application->id ?? null,
                        'file_code'      => $file_code,
                    ]);
                    return ['status' => false, 'message' => 'Gagal menyimpan file', 'data' => null];
                }
                // Files::create($application, $get_path, explode($get_filename)[1]);
            }
            Log::info('END CONVERT FILE TO PDF - SUCCESS');
            return ['status'=>false,'message'=>'Gagal menyimpan file ke storage'];
        }
     }

     /**
      * Hapus file lama (fisik di MinIO + record di tabel files) dengan aman.
      *
      * Hanya menghapus jika:
      *  - $old_file_id valid dan berbeda dari file baru ($new_file_id)
      *  - file lama TIDAK lagi direferensikan kolom/record lain manapun
      *
      * Kegagalan di sini tidak dilempar (hanya di-log), karena penyimpanan file
      * baru sudah sukses & ter-commit — cleanup yang gagal tidak boleh
      * membatalkan operasi yang sudah berhasil.
      */
     protected static function deleteOldFileIfUnused($old_file_id, $new_file_id): void
     {
        try {
            if (empty($old_file_id) || $old_file_id == $new_file_id) {
                return;
            }

            if (self::isFileStillReferenced($old_file_id)) {
                Log::info('Lewati hapus file lama: masih direferensikan record lain.', ['file_id' => $old_file_id]);
                return;
            }

            $old_file = Files::find($old_file_id);
            if (!$old_file) {
                return;
            }

            // path tersimpan sebagai folder + filename terpisah
            $full_path = rtrim($old_file->path, '/') . '/' . $old_file->filename;

            if (Storage::disk('minio')->exists($full_path)) {
                Storage::disk('minio')->delete($full_path);
            }
 
            $old_file->delete();

            Log::info('File lama berhasil dihapus saat regenerate.', ['file_id' => $old_file_id, 'path' => $full_path]);
        } catch (\Throwable $th) {
            Log::warning('Gagal menghapus file lama (diabaikan, file baru sudah tersimpan): ' . $th->getMessage(), [
                'old_file_id' => $old_file_id,
            ]);
        }
     }


     

     /**
      * Cek apakah sebuah file masih direferensikan di kolom/tabel manapun.
      */
     protected static function isFileStillReferenced($file_id): bool
     {
        // application_files: file_id atau merged_file_id
        $inApplicationFiles = \App\Models\ApplicationFile::where('file_id', $file_id)
            ->orWhere('merged_file_id', $file_id)
            ->exists();
        if ($inApplicationFiles) {
            return true;
        }

        // report_attachments
        if (\App\Models\ReportAttachment::where('file_id', $file_id)->exists()) {
            return true;
        }

        // application_participants: cv/idcard/npwp/material
        $inParticipants = \App\Models\ApplicationParticipant::where('cv_file_id', $file_id)
            ->orWhere('idcard_file_id', $file_id)
            ->orWhere('npwp_file_id', $file_id)
            ->orWhere('material_file_id', $file_id)
            ->exists();
        if ($inParticipants) {
            return true;
        }

        // draft_cost_budget_files (pivot kuitansi/SPBY)
        if (DB::table('draft_cost_budget_files')->where('file_id', $file_id)->exists()) {
            return true;
        }

        return false;
     }

     /**
      * Bersihkan file yatim (orphan): record `files` yang sudah tidak
      * direferensikan kolom/tabel manapun (lihat isFileStillReferenced).
      *
      * Untuk tiap orphan: hapus file fisik di MinIO lalu hard-delete recordnya.
      * Dirancang untuk data yang sudah menumpuk (mis. 7000+ record) — diproses
      * per-batch dengan chunkById agar hemat memori dan tidak mengunci tabel.
      *
      * Aman dijalankan berulang (idempoten) & mendukung mode dry-run untuk audit
      * tanpa menghapus apa pun.
      *
      * @param bool $dryRun   true = hanya hitung/laporkan, tidak menghapus.
      * @param int  $chunkSize Jumlah record per batch yang di-scan.
      * @return array{scanned:int, orphans:int, deleted:int, physical_deleted:int, failed:int}
      */
     public static function purgeOrphanFiles(bool $dryRun = true, int $chunkSize = 500): array
     {
        $stats = [
            'scanned'          => 0,
            'orphans'          => 0,
            'deleted'          => 0, // record dihapus dari DB
            'physical_deleted' => 0, // file fisik dihapus dari MinIO
            'failed'           => 0,
        ];

        // Bypass DepartmentScope agar seluruh record ikut ter-scan, tidak
        // bergantung pada user yang sedang login (mis. saat dijalankan via CLI).
        Files::withoutGlobalScope(\App\Models\Scopes\DepartmentScope::class)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($files) use (&$stats, $dryRun) {
                foreach ($files as $file) {
                    $stats['scanned']++;

                    // Masih dipakai record lain → lewati.
                    if (self::isFileStillReferenced($file->id)) {
                        continue;
                    }

                    $stats['orphans']++;

                    if ($dryRun) {
                        continue;
                    }

                    try {
                        // path tersimpan sebagai folder + filename terpisah.
                        $full_path = rtrim($file->path, '/') . '/' . $file->filename;

                        if (!empty($file->filename)
                            && Storage::disk('minio')->exists($full_path)) {
                            Storage::disk('minio')->delete($full_path);
                            $stats['physical_deleted']++;
                        }

                        // Hard delete: file fisik sudah tiada, record tidak perlu
                        // disimpan sebagai soft-deleted (menghindari dangling record).
                        $file->forceDelete();
                        $stats['deleted']++;
                    } catch (\Throwable $th) {
                        $stats['failed']++;
                        Log::warning('Gagal menghapus orphan file: ' . $th->getMessage(), [
                            'file_id' => $file->id,
                        ]);
                    }
                }
            });

        Log::info('purgeOrphanFiles selesai.', array_merge($stats, ['dry_run' => $dryRun]));

        return $stats;
     }

     public static function generateFilename($filename, $application, $fileCode='',$app_file=null,$mimeType = 'pdf'){
         $file_type_name = $application->applicationFiles()->withFileCodeAndParent($fileCode)->first()->fileType->name;
        $carbon = Carbon::now();
        $filename = explode('.',$filename)[0];
        $date = $carbon->format('Ymd');
        $time = $carbon->format('His');
        $timestamp = $carbon->timestamp;
        $new_filename = $file_type_name.'-'.$filename.'-'.$application->id.($app_file->participant_id?'-part-id-'.$app_file->participant_id:null).'-'.$date.'-'.$time.$timestamp.'.'.$mimeType;
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

   public static function getFileStorage($path,$application,$extend_dir=null,$type ='', $disk = 'minio') {
    if (Storage::disk($disk)->exists($path)) {
        $set_path_directory = FileManagementService::setPathStorage($application,$type).($extend_dir?'/'.$extend_dir:'');
        if (Storage::disk($disk)->mimeType($path) !== false) {
            return [
                'fileName'=>basename($path),
                'size'=>Storage::disk($disk)->size($path),
                'mimeType'=>Storage::disk($disk)->mimeType($path),
                'path'=>$set_path_directory,
                'content'=>Storage::disk($disk)->get($path)
            ];
        }
        return [];
    }else{

      return [];
    }

}

public static function getFileStorageById($file_id,$with_content=false,$disk='minio'){
    $file = Files::find($file_id)??null;

    if (!empty($file)) {
        // if ($file_id === 188) dd(Storage::disk($disk)->exists($file->path));
        // if (Storage::disk($disk)->exists($file->path)) {
            $data = [
                'file_id'=>$file_id,
                'fileName'=>basename($file->path),
                'size'=>Storage::disk($disk)->size($file->path),
                'mimeType'=>Storage::disk($disk)->mimeType($file->path),
                'path'=>$file->path,
            ];
            if ($with_content) {
                $data = [...$data,
                    'content'=>Storage::disk($disk)->get($file->path)
                ];
            }
            return $data;
        // }
    }
     return [];
 
}

public static function onlyOfficeConversion($from, $to, $fileUrl, $key = null)
{
    $jwtSecret = config('onlyoffice.DOC_SERV_JWT_SECRET');
    $key = $key ?: (string)now()->getTimestampMs();


       // Validasi format file yang didukung
    $supportedFormats = [
        'filetype' => ['docx', 'doc', 'odt', 'rtf', 'txt', 'html', 'epub', 'djvu', 'xlsx', 'xls', 'ods', 'csv', 'pptx', 'ppt', 'odp'],
        'outputtype' => ['pdf', 'docx', 'xlsx', 'pptx']
    ];

    $from = strtolower($from);
    $to = strtolower($to);

    if (!in_array($from, $supportedFormats['filetype'])) {
        Log::error("Unsupported input file format: {$from}");
        return false;
    }

    if (!in_array($to, $supportedFormats['outputtype'])) {
        Log::error("Unsupported output file format: {$to}");
        return false;
    }
    
    $config = [
        "async"=> false,
        'filetype' => $from,
        'outputtype' => $to,
        'url' => $fileUrl,
        'key' => $key
    ];
    Log::info('OnlyOffice Conversion Config:', $config);

    // Generate JWT token jika JWT secret tersedia
    $token = null;
    if ($jwtSecret) {
        $token = self::generateJwtToken($config, $jwtSecret);
    }

    $content = "";
    if (config('onlyoffice.DOC_SERV_SITE_URL')) {
        $conversionUrl = config('onlyoffice.DOC_SERV_SITE_URL') . 'ConvertService.ashx';
        
        // Setup headers
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'Thunder Client (https://www.thunderclient.com)',
            'Content-Type' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
        ];

        // Tambahkan Authorization header jika token tersedia
        if ($token) {
            // $headers['Authorization'] = 'Bearer ' . $token;
            $config['token'] =$token;
        }
        // $requestBody = json_encode($config, JSON_UNESCAPED_SLASHES);
// dd('OnlyOffice Request Body (Raw JSON): ', [
//     'body' => $requestBody,
//     'body_decoded' => json_decode($requestBody, true)  // Untuk readability
// ]);

        $response = Http::timeout(90)
            ->withBody(json_encode($config, JSON_UNESCAPED_SLASHES), 'application/json')
            ->withHeaders($headers)
            ->withoutVerifying()
            ->post($conversionUrl);
        

        Log::info('OnlyOffice Response: ', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);  
        $json = $response->json();

        if ($response->status() == 200 && $json && isset($json['fileUrl'])) {
            $content = file_get_contents($json['fileUrl']);
        }
    }
    return $content;
}

/**
 * Generate JWT token untuk OnlyOffice
 */
private static function generateJwtToken($payload, $secret)
{
    try {
        $token = JWT::encode($payload, $secret, 'HS256');
        return $token;
    } catch (\Exception $e) {
        Log::error('Error generating JWT token: ' . $e->getMessage());
        return null;
    }
}






















    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {}
}
