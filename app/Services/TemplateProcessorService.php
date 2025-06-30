<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use App\Models\CommiteePosition;
use App\Models\ParticipantType;
use Illuminate\Support\ServiceProvider;
use PhpOffice\PhpWord\TemplateProcessor;



use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use PhpOffice\PhpWord\IOFactory;

class TemplateProcessorService
{
    /**
     * Register services.
     *
     * @return void
     */
    public static $application = null;

    public static function generateWord($application=null)
    {
        self::$application = $application;
        $get_commitees = self::filterParticipantByType('commitee',$application->participants);
        $get_speakers = self::filterParticipantByType('speaker',$application->participants);
        $get_participant = self::filterParticipantByType('participant',$application->participants);
        // dd($get_commitees);
            $commitee_participant = self::generateTableParticipant('commitee', $get_commitees);
            $speaker_participant = self::generateTableParticipant('speaker', $get_speakers);
            $participant_participant = self::generateTableParticipant('participant', $get_participant);


        $meta = [
            'status'   => 'Disetujui',
            'pada'   => ViewHelper::humanReadableDate($application->currentUserApproval->updated_at),
            'oleh'     => $application->currentUserApproval->user->position->name,
            'nama'     => $application->currentUserApproval->user->name,
            'dokumen'  => route('applications.create.draft',['application_id'=>$application->id]),
        ];
            $qrPath = self::generateQrCode($meta);




            $templatePath = public_path('referensi/dummy_inject_word.docx');
            $savePath = public_path('referensi/generated2.docx');
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);
            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                $templateProcessor->setValue($key, $value);
            }

            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);

            }

            // Inject variabel
            $templateProcessor->cloneRowAndSetValues('commitee_role', $commitee_participant);
            $templateProcessor->cloneRowAndSetValues('speaker_name', $speaker_participant);
            $templateProcessor->cloneRowAndSetValues('participant_name', $participant_participant);


        // set qr code ttd
        $templateProcessor->setImageValue('qr_code', [
            'path'   => $qrPath,
            'width'  => 150,
            'height' => 150,
            'ratio'  => true,
        ]);
            // end set qr code ttd

            $templateProcessor->saveAs($savePath);

            $storage_path = 'docx-generated/hasil_generate.docx';

            Storage::disk('minio')->put($storage_path, file_get_contents($savePath));
        $temp_fileurl = Storage::disk('minio')->temporaryUrl($storage_path, now()->addHours(1), [
            'ResponseContentType' => 'application/octet-stream',
            'ResponseContentDisposition' => 'attachment; filename=generated2.docx',
            'filename' => 'generated2.docx',
        ]);
            $converted_to_pdf = self::convertToPdf($temp_fileurl);
            // return response()->download($converted_to_pdf);

            return true;
    }

    public static function downloadDocxGenerated(){


        $path =  public_path('referensi/generated2.docx');


        // $temp_fileurl = Storage::disk('local')->temporaryUrl('docx-genearted/hasil_generate.docx', now()->addHours(1), [
        //     'ResponseContentType' => 'application/octet-stream',
        //     'ResponseContentDisposition' => 'attachment; filename=generated2.docx',
        //     'filename' => 'generated2.docx',
        // ]);

        return $path;
    }

    public static function generateTableParticipant($participantType,$data){
        $rows = [];
        foreach ($data as $index => $row) {
            $jabatan = ($participantType != 'commitee') ? $row['institution'] : '';

            if ($participantType != 'participant') {
                if ($participantType == 'commitee') {
                    $peran = ucfirst(self::findName('commitee', $row['commitee_position_id']));
                } else {
                    $peran = ucfirst(self::findName('participant', $row['participant_type_id']));
                }
            } else {
                $peran = '';
            }

            $rows[] = [
                $participantType.'_no'      => $index + 1,
                $participantType .'_name'    => $row['name'],
                $participantType .'_institution' => $jabatan,
                $participantType .'_role'   => $peran,
            ];
        }
        // dd($rows);
        return $rows;
    }
    public static function filterParticipantByType($type,$data)
    {
        $participant_type = new ParticipantType();
        $ids = [];
        switch ($type) {
            case 'speaker':
                $ids = $participant_type::whereIn('name', ['narasumber', 'moderator'])->get()->pluck('id')->toArray();
                break;
            case 'participant':
                $ids = $participant_type::whereIn('name', ['peserta'])->get()->pluck('id')->toArray();
                break;
            case 'commitee':
                $ids = $participant_type::whereIn('name', ['panitia'])->get()->pluck('id')->toArray();
                break;

            default:
                # code...
                break;
        }
        return $data->filter(function ($item) use ($ids) {
            return in_array($item['participant_type_id'], $ids);
        })->values();
    }


    public static function findName($type, $id)
    {
        switch ($type) {
            case 'commitee':
                return CommiteePosition::findOrFail($id)->name;
            case 'participant':
                return ParticipantType::findOrFail($id)->name;
            default:
                dd('none are match');
                break;
        }
    }


    public static function generateQrCode( $data = null, $savePath = null) {
        // Lokasi file keluaran
        $savePath ??= storage_path('app/qrcodes/' . uniqid('qr_') . '.png');
        if (!is_dir(dirname($savePath))) mkdir(dirname($savePath), 0755, true);

        // Data di-encode (boleh "" untuk template kosong)
        $payload = is_array($data)
            ? json_encode($data, JSON_UNESCAPED_UNICODE)
            : (string) $data;


        /* ---------- 1. Buat objek QR ---------- */
        $qrCode = new QrCode(
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 800,
            margin: 20,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        /* ---------- 2. Tambahkan logo (optional) ---------- */
        $logoPath = public_path('assets/images/logo-icon-bg-white.png');  // ganti sesuai path
        $logo     = file_exists($logoPath)
            ? new Logo(path: $logoPath, resizeToWidth: 180)       // otomatis di-tengah
            : null;

        /* ---------- 3. Render & simpan ---------- */
        $writer  = new PngWriter();
        $result  = $writer->write($qrCode, $logo);                // $logo boleh null
        $result->saveToFile($savePath);

        return $savePath;                                         // path PNG siap dipakai
    }




    public static function onlyOfficeConversion1($from, $to, $fileUrl, $key = null)
    {
        // $filePath = url('referensi/generated2.docx');
        $filePath = storage_path('app/public/docx-genearted/hasil_generate.docx');  // Akses file dari folder public Laravel
        if (config('onlyoffice.DOC_SERV_SITE_URL')) {
            $conversionUrl = config('onlyoffice.DOC_SERV_SITE_URL') . 'convert';
            // $conversionUrl = config('onlyoffice.DOC_SERV_SITE_URL') . 'ConvertService.ashx';
        // $onlyOfficeUrl = 'http://onlyoffice:80/convert';  // Ganti dengan URL OnlyOffice di Docker

        $response = Http::attach(
            'file',
            fopen($filePath, 'r'),
            'generated2.docx'
        )->post($conversionUrl);

        $saved_path = ' /converted-file.pdf';

        dd($response);
        if ($response->successful()) {
            // Simpan hasil konversi ke folder public
            file_put_contents(public_path('referensi/converted-file.pdf'), $response->body());

            return $saved_path;
        } else {
            // Tangani error
            return response()->json(['error' => 'File conversion failed'], 500);
        }
    }
    }
    public static function onlyOfficeConversion($from, $to, $fileUrl, $key = null)
    {
        $config = [
            'fileType' => $from,
            'outputtype' => $to,
            // 'url' => 'http://laravel-app/referensi/generated2.docx',
            'url' => $fileUrl,
            'key' => $key ?: (string)now()->getTimestampMs()
        ];

        // dd($config);

        // dd(config('onlyoffice.DOC_SERV_SITE_URL'));

        // Log::info('Isi log file_url '. $fileUrl);

        $content = "";

        if (config('onlyoffice.DOC_SERV_SITE_URL')) {
            $conversionUrl = config('onlyoffice.DOC_SERV_SITE_URL') . 'ConvertService.ashx';

            // dd($config);
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

    public static function convertToPdf($file_url = null, $is_replace = false)
    {

        Log::info('START CONVERT FILE TO PDF');




        // $temp_fileurl = public_path('storage/docx-genearte/hasil_generate.docx');


        // dd($temp_fileurl);
        // $file_url = Storage::disk('local')->get('referensi/genearted.docx');

        $path = parse_url($file_url, PHP_URL_PATH);
        // Mendapatkan ekstensi file
        $fileInfo = pathinfo($path);
        $extension = $fileInfo['extension'];
        $content = self::onlyOfficeConversion($extension, 'pdf', $file_url);
        $get_path = FileManagementService::getPathStorage(self::$application->id,'application');
        $get_filename = FileManagementService::generateFilename(self::$application->activity_name,self::$application->id,'TOR');
        $target_dir = $get_path.'/'.$get_filename;
        if ($content) {
            $res = Storage::disk('minio')->put($target_dir, $content);
            // if ($res) {
            //    FileManagementService::storeFile(self::$application,$get_path,explode($get_filename)[1]);
            // }
            Log::info('END CONVERT FILE TO PDF - SUCCESS');
            return $res;
        }
        Log::info('END CONVERT FILE TO PDF - FAILED');
        return $content;
    }








    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
