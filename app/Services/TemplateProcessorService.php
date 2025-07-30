<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use App\Models\CommiteePosition;
use App\Models\FileType;
use App\Models\LogApproval;
use App\Models\ParticipantType;
use Carbon\Carbon;
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
    public static $dump = [];

    public static function generateApplicationDocument($application){
        // $file_type = FileType::where('trans_type',1)->get();
        $application_files = $application->applicationFiles()->whereHas('fileType',function($q){
            return $q->where('trans_type',1);
        })->get();
        // dd($application_files);
        foreach ($application_files as $key => $app_file) {
            $code = $app_file->fileType->code;
            $res = self::generateDocumentToPDF($application,$code,$app_file);

            if (!$res['status']) {
                dd($res['message']);
            }
        }
        // dd(self::$dump);

        return ['status'=>true,'message'=>'Berhasil generate dokumen pengajuan baru'];
    }

    public static function generateDocumentToPDF($application=null,$file_type='',$app_file)
    {

        
        $templatePath = public_path('templates/'.$file_type.'.docx');
        $directory_temp = 'temp/templates/'.$file_type.($app_file->participant_id?'-'.$app_file->participant_id:null).'.docx';
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
            case 'sk':
                self::generateSK($application, $templatePath, $directory_temp, $file_type);
                break;
            case 'laporan_kegiatan':
                self::generateReport($application, $templatePath, $directory_temp, $file_type);
                break;
            case 'jadwal_kegiatan':
                self::generateRundown($application, $templatePath, $directory_temp, $file_type);
                break;
            case 'surat_tugas_moderator':
                self::generateSuratTugas($application, $templatePath, $directory_temp, $file_type,'moderator');
                break; 
            case 'surat_tugas_narasumber':
                self::generateSuratTugas($application, $templatePath, $directory_temp, $file_type,'speaker');
                break;
            case 'surat_undangan_peserta':
                self::generateSuratUndangan($application, $templatePath, $directory_temp, $file_type);
                break;
            case 'surat_permohonan_narasumber':
                self::generateSuratPermohonan($application, $templatePath, $directory_temp, $file_type,$app_file);
                break;
            case 'surat_permohonan_moderator':
                self::generateSuratPermohonan($application, $templatePath, $directory_temp, $file_type,$app_file);
                break;
            default:
                # code...
                break;
        }
        self::$dump[]=$directory_temp;
        Storage::disk('minio')->put($directory_temp, file_get_contents($write_output));
        $temp_fileurl = Storage::disk('minio')->temporaryUrl($directory_temp, now()->addHours(1), [
            'ResponseContentType' => 'application/octet-stream',
            'ResponseContentDisposition' => 'attachment; filename=generated2.docx',
            'filename' => 'generated_output.docx',
        ]);
        $content = FileManagementService::convertToPdf($temp_fileurl);
        if ($content) {
            $store_document = FileManagementService::storeFileApplication($content, $application, $get_file_type->trans_type==1?'letters':'report', $file_type,$app_file);
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
        $log_approval = LogApproval::getSigner($file_type->signed_role_id, $application->department_id,$file_type->trans_type, $application->id)->first();
        // dd($log_approval);
        $meta = [
            'Tgl_cetak'   => ViewHelper::humanReadableDate($log_approval->updated_at),
            'Jabatan'     => $log_approval->position->name,
            'Lokasi'     => $log_approval->location_city,
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

            $get_rundowns = self::generateTableRundown($application->schedules);

            $get_draft_cost = self::generateTableDraftCost($application->draftCostBudgets);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

            $temp=[];
            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                if ($key == 'activity_name') {
                    $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                }
                $temp[$key] = $value;

                $templateProcessor->setValue($key, $value);
            }


            $temp_detail = [];
            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
                $temp_detail[$key]=$value;

            }

        // Inject variabel
        $templateProcessor->setValue('department_name', ucfirst($application->department->name));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('total_all', $get_draft_cost['total_all']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));

        // tables
            $templateProcessor->cloneRowAndSetValues('commitee_position', $commitee_participant);
            $templateProcessor->cloneRowAndSetValues('speaker_name', $speaker_participant);
            $templateProcessor->cloneRowAndSetValues('moderator_name', $moderator_participant);
            $templateProcessor->cloneRowAndSetValues('participant_name', $participant_participant);

            // dd($get_draft_cost);

            $templateProcessor->cloneRowAndSetValues('rd_start_date', $get_rundowns);
            $templateProcessor->cloneRowAndSetValues('dc_code', $get_draft_cost['data']);


        // // Ambil konten HTML dari database atau variabel lain
        // $htmlContent = $yourHtmlContentFromDatabase;

        // // Buat section baru di template (tanpa menambah halaman baru)
        // $section = $templateProcessor->getSection(0);  // Mengambil section pertama template

        // // Konversi HTML ke dalam format yang dikenali oleh PHPWord
        // Html::addHtml($section, $htmlContent);


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
    public static function generateSuratUndangan($application, $templatePath, $directory_temp, $file_type)
    {
        $write_output = public_path($directory_temp);
        $get_commitees = self::filterParticipantByType('commitee',$application->participants);
        $get_participant = self::filterParticipantByType('participant',$application->participants);
        // dd($get_commitees);
            $commitee_participant = self::generateTableParticipant('commitee', $get_commitees);
            $participant_participant = self::generateTableParticipant('participant', $get_participant);

            $get_rundowns = self::generateTableRundown($application->schedules);

            $get_draft_cost = self::generateTableDraftCost($application->draftCostBudgets);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

            $temp=[];
            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                if ($key == 'activity_name') {
                    $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                }
                $temp[$key] = $value;

                $templateProcessor->setValue($key, $value);
            }


            $temp_detail = [];
            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
                $temp_detail[$key]=$value;
                        if ($key == 'activity_dates') {
                // Pisahkan tanggal berdasarkan koma
                $dates = explode(',', $value);

                // Variabel untuk menyimpan hasil parsing
                $formatted_dates = [];
                $days = [];
                 Carbon::setLocale('id');
                foreach ($dates as $date) {
                    // Format tanggal menjadi "20 Mei 2025"
                    $formatted_dates[] = Carbon::parse($date)->translatedFormat('d F Y');

                    // Ambil hari dari tanggal
                    $days[] = Carbon::parse($date)->translatedFormat('l');
                }

                // Gabungkan hasil menjadi string
                $templateProcessor->setValue($key.'_formatted', implode(',', $formatted_dates));
                $templateProcessor->setValue($key.'_days', implode(',', $days));
            }

            }
        $get_nomor_surat = $application->letterNumbers()->where('letter_name','nomor_surat_undangan_peserta')->first();


        // Inject variabel
        $templateProcessor->setValue('nomor_surat_undangan', ucfirst($get_nomor_surat->letter_number));
        $templateProcessor->setValue('nomor_surat_undangan_formatted_date', ucfirst(Carbon::parse($get_nomor_surat->letter_date)->format('d M Y')));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('total_all', $get_draft_cost['total_all']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));

        // tables
            $templateProcessor->cloneRowAndSetValues('commitee_position', $commitee_participant);
            $templateProcessor->cloneRowAndSetValues('participant_name', $participant_participant);

            // dd($get_draft_cost);


        // // Ambil konten HTML dari database atau variabel lain
        // $htmlContent = $yourHtmlContentFromDatabase;

        // // Buat section baru di template (tanpa menambah halaman baru)
        // $section = $templateProcessor->getSection(0);  // Mengambil section pertama template

        // // Konversi HTML ke dalam format yang dikenali oleh PHPWord
        // Html::addHtml($section, $htmlContent);


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
    public static function generateSuratPermohonan($application, $templatePath, $directory_temp, $file_type,$app_file)
    {
        $write_output = public_path($directory_temp);
        $get_commitees = self::filterParticipantByType('commitee',$application->participants);
        $get_participant = self::filterParticipantByType('participant',$application->participants);
        // dd($get_commitees);
            $commitee_participant = self::generateTableParticipant('commitee', $get_commitees);
            $participant_participant = self::generateTableParticipant('participant', $get_participant);

            $get_rundowns = self::generateTableRundown($application->schedules);

            $get_draft_cost = self::generateTableDraftCost($application->draftCostBudgets);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

            $temp=[];
            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                if ($key == 'activity_name') {
                    $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                }
                $temp[$key] = $value;

                $templateProcessor->setValue($key, $value);
            }


            $temp_detail = [];
            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
                $temp_detail[$key]=$value;
                        if ($key == 'activity_dates') {
                // Pisahkan tanggal berdasarkan koma
                $dates = explode(',', $value);

                // Variabel untuk menyimpan hasil parsing
                $formatted_dates = [];
                $days = [];
                 Carbon::setLocale('id');
                foreach ($dates as $date) {
                    // Format tanggal menjadi "20 Mei 2025"
                    $formatted_dates[] = Carbon::parse($date)->translatedFormat('d F Y');

                    // Ambil hari dari tanggal
                    $days[] = Carbon::parse($date)->translatedFormat('l');
                }

                // Gabungkan hasil menjadi string
                $templateProcessor->setValue($key.'_formatted', implode(',', $formatted_dates));
                $templateProcessor->setValue($key.'_days', implode(',', $days));
            }

            }
        $get_nomor_surat = $application->letterNumbers()->where('letter_name','nomor_surat_permohonan')->first();
        $get_recipient = $application->participants()->where('id',$app_file->participant_id)->first();
        // Inject variabel
        $templateProcessor->setValue('recipient_name', ucfirst($get_recipient->name));
        $templateProcessor->setValue('recipient_institution', ucfirst($get_recipient->institution));
        $templateProcessor->setValue('nomor_surat_permohonan', ucfirst($get_nomor_surat->letter_number));
        $templateProcessor->setValue('nomor_surat_permohonan_formatted_date', ucfirst(Carbon::parse($get_nomor_surat->letter_date)->format('d M Y')));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('total_all', $get_draft_cost['total_all']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));

        // tables


            // dd($get_draft_cost);


        // // Ambil konten HTML dari database atau variabel lain
        // $htmlContent = $yourHtmlContentFromDatabase;

        // // Buat section baru di template (tanpa menambah halaman baru)
        // $section = $templateProcessor->getSection(0);  // Mengambil section pertama template

        // // Konversi HTML ke dalam format yang dikenali oleh PHPWord
        // Html::addHtml($section, $htmlContent);


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
    public static function generateSuratTugas($application, $templatePath, $directory_temp, $file_type,$participant_type)
    {
        $write_output = public_path($directory_temp);
        $get_speakers = self::filterParticipantByType($participant_type,$application->participants);
        // $get_moderator = self::filterParticipantByType('moderator',$application->participants);
        // dd($get_commitees);
            $speaker_participant = self::generateTableParticipant($participant_type, $get_speakers);
            // $moderator_participant = self::generateTableParticipant('moderator', $get_moderator);

            // $get_rundowns = self::generateTableRundown($application->schedules);


            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

            // $temp=[];
            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                if ($key == 'activity_name') {
                    $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                }
                // $temp[$key] = $value;

                $templateProcessor->setValue($key, $value);
            }


            $temp_detail = [];
            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
                $temp_detail[$key]=$value;
                     if ($key == 'activity_dates') {
                // Pisahkan tanggal berdasarkan koma
                $dates = explode(',', $value);

                // Variabel untuk menyimpan hasil parsing
                $formatted_dates = [];
                $days = [];
                 Carbon::setLocale('id');
                foreach ($dates as $date) {
                    // Format tanggal menjadi "20 Mei 2025"
                    $formatted_dates[] = Carbon::parse($date)->translatedFormat('d F Y');

                    // Ambil hari dari tanggal
                    $days[] = Carbon::parse($date)->translatedFormat('l');
                }

                // Gabungkan hasil menjadi string
                $templateProcessor->setValue($key.'_formatted', implode(',', $formatted_dates));
                $templateProcessor->setValue($key.'_days', implode(',', $days));
            }

            }
        $get_nomor_surat_tugas = $application->letterNumbers()->where('letter_name','nomor_surat_tugas')->first();
        
        // Inject variabel
        $templateProcessor->setValue('nomor_surat_tugas', ucfirst($get_nomor_surat_tugas->letter_number));
        $templateProcessor->setValue('participant_type', ucfirst($participant_type == 'speaker'?'narasumber':'moderator'));
        $templateProcessor->setValue('department_name', ucfirst($application->department->name));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));

        // tables

$mapped_data = array_map(function($item) use($participant_type){
    $new_name = $item[$participant_type.'_no'].'. '.$item[$participant_type.'_name'];
    $item[$participant_type.'_name'] = $new_name;
    $item['nip'] = '   NIP. '.$item[$participant_type.'_no'];
    $item['echelon'] = '   Echelon. '.$item[$participant_type.'_no'];
    $item['rank'] = '   Rank. '.$item[$participant_type.'_no'];
    unset($item[$participant_type.'_no']);
    unset($item[$participant_type.'_institution']);
    unset($item[$participant_type.'_position']);
    return $item;

},$speaker_participant);
// dd($mapped_data);


$new_data = [];
foreach ($mapped_data as $key => $value) {
    # code...
    $get_values = array_values($value);
    // dd($get_values);
    $new_data=[...$new_data,...$get_values];
}
// dd($new_data);

$templateProcessor->cloneRow('speaker_data', count($new_data));
// $templateProcessor->cloneRow('nip', $totalRows);
// $templateProcessor->cloneRow('echelon', $totalRows);
// $templateProcessor->cloneRow('rank', $totalRows);


// Variabel untuk melacak baris saat ini

// Isi data untuk setiap pembicara
foreach ($new_data as $index => $item) {
    $current_row= $index+1;
    if ($current_row ==1 || $current_row % 5 == 0) {
        $templateProcessor->setValue('speaker_data#' . ($index+1), $item);
    }else{
        $templateProcessor->setValue('speaker_data#' . ($index+1), '.'.$item);
    }
}


        // set qr code ttd
        $templateProcessor->setImageValue('signed_barcode', [
            'path'   => $qrPath,
            'width'  => 75,
            'height' => 75,
            'ratio'  => true,
        ]);
            // end set qr code ttd

        $templateProcessor->saveAs($write_output);

        // return response()->download($converted_to_pdf);
        return true;
    }
    public static function generateRundown($application, $templatePath, $directory_temp, $file_type)
    {
        $write_output = public_path($directory_temp);
        // dd($get_commitees);

            $get_rundowns = self::generateTableRundown($application->schedules);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

            $temp=[];
            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                if ($key == 'activity_name') {
                    $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                }
                $temp[$key] = $value;

                $templateProcessor->setValue($key, $value);
            }


            $temp_detail = [];
            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
                $temp_detail[$key]=$value;

            }

        // Inject variabel
        $templateProcessor->setValue('department_name', ucfirst($application->department->name));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));



            // dd($get_draft_cost);

            $templateProcessor->cloneRowAndSetValues('rd_start_date', $get_rundowns);


        // // Ambil konten HTML dari database atau variabel lain
        // $htmlContent = $yourHtmlContentFromDatabase;

        // // Buat section baru di template (tanpa menambah halaman baru)
        // $section = $templateProcessor->getSection(0);  // Mengambil section pertama template

        // // Konversi HTML ke dalam format yang dikenali oleh PHPWord
        // Html::addHtml($section, $htmlContent);


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

    public static function generateSK($application, $templatePath, $directory_temp, $file_type)
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

            $get_draft_cost = self::generateTableDraftCost($application->draftCostBudgets);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

            $temp=[];
            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                if ($key == 'activity_name') {
                    $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                }
                $templateProcessor->setValue($key, $value);
            }


            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }

        // Inject variabel
        $templateProcessor->setValue('department_name', ucfirst($application->department->name));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signer_position_uppercase', strtoupper($metadata_signer['Jabatan']));
        $templateProcessor->setValue('signer_name_uppercase', strtoupper($metadata_signer['Nama']));
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('total_all', $get_draft_cost['total_all']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));

        $templateProcessor->setValue('nomor_mak', strtoupper($application->letterNumbers()->where('letter_name','mak')->first()->letter_number));
        $templateProcessor->setValue('nomor_sk_uppercase', strtoupper($application->letterNumbers()->where('letter_name','nomor_sk')->first()->letter_number));
        $templateProcessor->setValue('tanggal_sk', strtoupper(ViewHelper::humanReadableDate($application->letterNumbers()->where('letter_name','nomor_sk')->first()->letter_date)));
        $templateProcessor->setValue('tanggal_berlaku_sk', strtoupper($application->letterNumbers()->where('letter_name','tanggal_berlaku_sk')->first()->letter_number));



        // tables
            $templateProcessor->cloneRowAndSetValues('commitee_position', $commitee_participant);
            $templateProcessor->cloneRowAndSetValues('speaker_name', $speaker_participant);
            $templateProcessor->cloneRowAndSetValues('moderator_name', $moderator_participant);
            $templateProcessor->cloneRowAndSetValues('participant_name', $participant_participant);

            // dd($get_draft_cost);

            $templateProcessor->cloneRowAndSetValues('dc_code', $get_draft_cost['data']);


        // // Ambil konten HTML dari database atau variabel lain
        // $htmlContent = $yourHtmlContentFromDatabase;

        // // Buat section baru di template (tanpa menambah halaman baru)
        // $section = $templateProcessor->getSection(0);  // Mengambil section pertama template

        // // Konversi HTML ke dalam format yang dikenali oleh PHPWord
        // Html::addHtml($section, $htmlContent);


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
    public static function generateReport($application, $templatePath, $directory_temp, $file_type)
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


            $get_draft_cost = self::generateTableDraftCost($application->draftCostBudgets);
            $get_realization = self::generateTableRealization($application->draftCostBudgets);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

            foreach ($application->getAttributes() as $key => $value) {
                if ($key == 'funding_source') {
                    $value = $value==1? 'BLU':'BOPTN';
                }
                if ($key == 'activity_name') {
                    $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                }
                $templateProcessor->setValue($key, $value);
            }
            $temp=[];
            foreach ($application->report->getAttributes() as $key => $value) {
                $temp[$key]=$value;
                $templateProcessor->setValue($key, $value);
            }

            // dd($temp);



            foreach ($application->detail->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);

            }

        // Inject variabel
        $templateProcessor->setValue('department_name', ucfirst($application->department->name));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('total_all', $get_draft_cost['total_all']);
        $templateProcessor->setValue('rs_total_all', $get_realization['rs_total_all']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));

        // tables
            $templateProcessor->cloneRowAndSetValues('commitee_position', $commitee_participant);
            $templateProcessor->cloneRowAndSetValues('speaker_name', $speaker_participant);
            $templateProcessor->cloneRowAndSetValues('moderator_name', $moderator_participant);
            $templateProcessor->cloneRowAndSetValues('participant_name', $participant_participant);

            // dd($get_rundowns);

            // $templateProcessor->cloneRowAndSetValues('rd_start_date', $get_rundowns);
            $templateProcessor->cloneRowAndSetValues('rs_code', $get_realization['data']);
            $templateProcessor->cloneRowAndSetValues('dc_code', $get_draft_cost['data']);


        // // Ambil konten HTML dari database atau variabel lain
        // $htmlContent = $yourHtmlContentFromDatabase;

        // // Buat section baru di template (tanpa menambah halaman baru)
        // $section = $templateProcessor->getSection(0);  // Mengambil section pertama template

        // // Konversi HTML ke dalam format yang dikenali oleh PHPWord
        // Html::addHtml($section, $htmlContent);


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


    public static function getRundownTimeRanges($rundowns){
        $range_time_date = [];
        foreach ($rundowns as $key => $value) {
            $range_time_date[$value->date][] = ViewHelper::formatDateToHumanReadable($value->start_date,'H:i').' - '.ViewHelper::formatDateToHumanReadable($value->end_date,'H:i');
        }

        $join_string = '';
        foreach ($range_time_date as $key => $value) {
            list($s_start,$e_start)= explode('-',$value[0]);
            list($s_end,$e_end)= explode('-',end($value));
            $join_string.=$s_start.'-'.$e_end;

            if ($range_time_date > 1 && $key != array_key_last($range_time_date)) {
                $join_string.=', ';
            }
        }
        return $join_string;
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
        $number = 1;
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
                $participantType.'_no'      => $number,
                $participantType .'_name'    => $row['name'],
                $participantType .'_institution' => $jabatan,
                $participantType .'_position'   => $peran,
            ];
            $number++;
        }
        // dd($rows);
        return $rows;
    }
    public static function generateTableRundown($rundowns){
        $rows = [];
        $number = 1;
        foreach ($rundowns as $index => $row) {
            $rows[] = [
                'rd_no'      => $number,
                'rd_start_date'    => ViewHelper::humanReadableDate($row->date),
                'rd_start_end_time' => ViewHelper::formatDateToHumanReadable($row->start_date,'H:i').' - '. ViewHelper::formatDateToHumanReadable($row->end_date, 'H:i'),
                'rd_name'   => $row->name,
                'rd_moderator_label'=> (!empty($row->moderator_text) ? 'Moderator' : ''),
                'rd_speaker_label'=> (!empty($row->speaker_text) ? 'Narasumber' : ''),
                'rd_speaker_list'   => self::speakerListToUnorderedString($row->speaker_text),
                'rd_moderator_list'   => self::speakerListToUnorderedString($row->moderator_text),
            ];
            $number++;
        }
        return $rows;
    }
    public static function generateTableDraftCost($draft_costs){
        $rows = [];
        $number = 1;
        $total_all = 0;
        foreach ($draft_costs as $index => $row) {
            $rows['data'][] = [
                'dc_code'      => $row->code,
                'dc_item'    => $row->item,
                'dc_sub_item'    => $row->sub_item,
                'dc_unit'   => $row->unit,
                'dc_cost_per_unit' => ViewHelper::currencyFormat($row->cost_per_unit),
                'dc_volume'   => $row->volume,
                'dc_total'   => ViewHelper::currencyFormat($row->total)
            ];
            $total_all+=$row->total;
            $number++;
        }
        $rows['total_all']=ViewHelper::currencyFormat($total_all);
        return $rows;
    }
    public static function generateTableRealization($draft_costs){
        $rows = [];
        $number = 1;
        $total_all = 0;
        foreach ($draft_costs as $index => $row) {
            $rows['data'][] = [
                'rs_code'      => $row->code,
                'rs_item'    => $row->item,
                'rs_sub_item'    => $row->sub_item,
                'rs_unit'   => $row->unit,
                'rs_cost_per_unit' => ViewHelper::currencyFormat($row->unit_cost_realization),
                'rs_volume'   => $row->volume_realization,
                'rs_total'   => ViewHelper::currencyFormat($row->realization)
            ];
            $total_all+=$row->realization;
            $number++;
        }
        $rows['rs_total_all']=ViewHelper::currencyFormat($total_all);
        return $rows;
    }


    public static function generateSpeakerList($speaker_text)
    {
        $speakers = $speaker_text? explode(';',$speaker_text):[];
        return $speakers;
    }

    public static function speakerListToUnorderedString($speaker_text)
    {
    $speakers = self::generateSpeakerList($speaker_text);
        if (empty($speakers)) return '';
        
        $result = '<ol>';
        foreach ($speakers as $speaker) {
            if (trim($speaker) !== '') {
                $result .= '<li style="margin-bottom:5px;">' . trim($speaker) . '</li>';
            }
        }
        $result .= '</ol>';
        // dd($result);
        return $result;
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
