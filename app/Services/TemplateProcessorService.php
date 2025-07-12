<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use App\Models\CommiteePosition;
use App\Models\FileType;
use App\Models\LogApproval;
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

    public static function generateApplicationDocument($application){
        $file_type = FileType::where('trans_type',1)->get();

        foreach ($file_type as $key => $type) {
            $res = self::generateDocumentToPDF($application,$type->code);
            if (!$res['status']) {
                dd($res['message']);
            }
        }

        return ['status'=>true,'message'=>'Berhasil generate dokumen pengajuan baru'];
    }

    public static function generateDocumentToPDF($application=null,$file_type='')
    {


        $templatePath = public_path('templates/'.$file_type.'.docx');
        $directory_temp = 'temp/templates/'.$file_type.'.docx';
        $write_output = public_path($directory_temp);


        $get_file_type = FileType::where('code',$file_type)->first();
        // dd($application->getAttributes(),$application->detail->getAttributes());

        switch ($get_file_type->code) {
            case 'tor':
                self::generateTor($application,$templatePath,$directory_temp,$file_type);
                break;
            case 'draft_tor':
                self::generateTor($application, $templatePath, $directory_temp, $file_type);
                break;
            default:
                # code...
                break;
        }

        Storage::disk('minio')->put($directory_temp, file_get_contents($write_output));
        $temp_fileurl = Storage::disk('minio')->temporaryUrl($directory_temp, now()->addHours(1), [
            'ResponseContentType' => 'application/octet-stream',
            'ResponseContentDisposition' => 'attachment; filename=generated2.docx',
            'filename' => 'generated_output.docx',
        ]);
        $content = FileManagementService::convertToPdf($temp_fileurl);
        if ($content) {
            $store_document = FileManagementService::storeFileApplication($content, $application, $get_file_type->trans_type==1?'letters':'report', $file_type);
            if ($store_document['status']) {
                unlink($write_output);
                Storage::disk('minio')->delete($directory_temp);
                return ['status' => true, 'message' => 'berhasil generate ke pdf'];
            }
        }

        return ['status' => false, 'message' => 'Gagal generate ke pdf '.$file_type];
    }

    public static function getSignerMetadata($application, $file_type)
    {
        $file_type = FileType::whereCode($file_type)->first();
        $log_approval = LogApproval::getSigner($file_type->signed_role_id, $application->department_id, $application->id)->first();
        $meta = [
            'Tgl_cetak'   => ViewHelper::humanReadableDate($log_approval->position->created_at),
            'Jabatan'     => $log_approval->position->name,
            'Nama'     => $log_approval->user->name,
            'NIP'     => 'soon to be update',
            // 'nip'     => $log_approval->user->nip??'soon to be update',
            // 'dicetak_oleh'     => $application->currentUserApproval->user->name,
            'status_surat'   => 'SIGNED',
            'url'  => route('applications.detail',['application_id'=>$application->id]),
        ];
        return $meta;
    }
    public static function generateTor($application, $templatePath, $directory_temp, $file_type)
    {
        $write_output = public_path($directory_temp);
        $get_commitees = self::filterParticipantByType('commitee',$application->participants);
        $get_speakers = self::filterParticipantByType('speaker',$application->participants);
        $get_moderator = self::filterParticipantByType('moderator',$application->participants);
        $get_participant = self::filterParticipantByType('participant',$application->participants);
        // dd($get_commitees);
            $commitee_participant = self::generateTableParticipant('commitee', $get_commitees);
            $speaker_participant = self::generateTableParticipant('speaker', $get_speakers);
            $moderator_participant = self::generateTableParticipant('moderator', $get_moderator);
            $participant_participant = self::generateTableParticipant('participant', $get_participant);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
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

            $templateProcessor->setValue('department_name', ucfirst($application->department->name));
            $templateProcessor->setValue('department_name_uppercase', strtoupper($value));
            $templateProcessor->setValue('current_year', date("Y"));

        // Inject variabel
            $templateProcessor->cloneRowAndSetValues('commitee_position_name', $commitee_participant);
            $templateProcessor->cloneRowAndSetValues('speaker_name', $speaker_participant);
            $templateProcessor->cloneRowAndSetValues('participant_name', $participant_participant);


        // set qr code ttd
        $templateProcessor->setImageValue('signed_barcode', [
            'path'   => $qrPath,
            'width'  => 100,
            'height' => 100,
            'ratio'  => true,
        ]);
            // end set qr code ttd

        $templateProcessor->saveAs($write_output);

        // return response()->download($converted_to_pdf);
        return true;
    }
    public static function generateWord($application=null)
    {
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
            $directory_temp = 'temp/docx/generated_output.docx';
            $write_output = public_path($directory_temp);
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
            $templateProcessor->cloneRowAndSetValues('commitee_', $commitee_participant);
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

        $templateProcessor->saveAs($write_output);

        // return response()->download($converted_to_pdf);



            return false;
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
                $participantType .'_position'   => $peran,
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
                $ids = $participant_type::where('name', 'narasumber')->get()->pluck('id')->toArray();
                break;
            case 'moderator':
                $ids = $participant_type::where('name', 'moderator')->get()->pluck('id')->toArray();
                break;
            case 'participant':
                $ids = $participant_type::where('name', 'peserta')->get()->pluck('id')->toArray();
                break;
            case 'commitee':
                $ids = $participant_type::where('name', 'panitia')->get()->pluck('id')->toArray();
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


    public static function generateQrCode($meta = null, $savePath = null) {
        // Lokasi file keluaran
        $savePath ??= storage_path('app/qrcodes/' . uniqid('qr_') . '.png');
        if (!is_dir(dirname($savePath))) mkdir(dirname($savePath), 0755, true);

        // Data di-encode (boleh "" untuk template kosong)
        $content_barcode = '';
        foreach ($meta as $key => $value) {
            $content_barcode .= '[' . $key . '=' . $value . ']';
        }

        /* ---------- 1. Buat objek QR ---------- */
        $qrCode = new QrCode(
            data: $content_barcode,
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

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
