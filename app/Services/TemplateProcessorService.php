<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use App\Models\ApplicationParticipant;
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
use Spatie\Permission\Models\Role;

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
        $application_files = $application->applicationFiles()->whereHas('fileType',function($q){
            return $q->where('trans_type',1);
        })->get();
        foreach ($application_files as $key => $app_file) {
            $code = $app_file->fileType->code;
            $is_exist_participant = true;
            $speaker = $application->participants()->where('participant_type_id',2)->get();
            $moderator = $application->participants()->where('participant_type_id',4)->get();
            $commitee = $application->participants()->where('participant_type_id',1)->get();
            $participant = $application->participants()->where('participant_type_id',3)->get();

            switch ($code) {
    case 'surat_tugas_narasumber':
        $is_exist_participant = $speaker->count() > 0;
        break;
    case 'surat_tugas_moderator':
        $is_exist_participant = $moderator->count() > 0;
        break;
    case 'surat_tugas_peserta':
        $is_exist_participant = $participant->count() > 0;
        break;
    case 'surat_tugas_panitia':
        $is_exist_participant = $commitee->count() > 0;
        break;
    case 'surat_permohonan_narasumber':
    // Cek jika ada peserta dengan tipe narasumber dan participant_id pada $app_file
    if (empty($app_file->participant_id)) {
        $is_exist_participant = false;
        break;
    }
    $is_exist_participant = $speaker->where('id', $app_file->participant_id)->count() > 0;
    break;
case 'surat_permohonan_moderator':
    // Cek jika ada peserta dengan tipe moderator dan participant_id pada $app_file
    if (empty($app_file->participant_id)) {
        $is_exist_participant = false;
        break;
    }
    $is_exist_participant = $moderator->where('id', $app_file->participant_id)->count() > 0;
    break;
    default:
        $is_exist_participant = true;
        break;
}
            if ($is_exist_participant) {
                # code...
                $app_file->status_ready=2;
                $app_file->save();
                $res = self::generateDocumentToPDF($application,$code,$app_file);
            }else{
                $res=['status'=>false,'message'=>'Peserta Tidak ditemukan pada '.$app_file->display_name];
            }
            

            if (!$res['status']) {
                 $app_file->status_ready=4;
                 $app_file->save();
                 Log::error($res['message']);
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
            case 'surat_tugas_panitia':
                self::generateSuratTugas($application, $templatePath, $directory_temp, $file_type,'commitee');
                break;
            case 'surat_tugas_peserta':
                self::generateSuratTugas($application, $templatePath, $directory_temp, $file_type,'participant');
                break;
            case 'surat_undangan_peserta_dan_panitia':
                self::generateSuratUndangan($application, $templatePath, $directory_temp, $file_type);
                break;
            case 'surat_permohonan_narasumber':
                self::generateSuratPermohonan($application, $templatePath, $directory_temp, $file_type,$app_file);
                break;
            case 'surat_permohonan_moderator':
                self::generateSuratPermohonan($application, $templatePath, $directory_temp, $file_type,$app_file);
                break;
            case 'daftar_kehadiran_panitia':
                self::generateDaftarKehadiran($application, $templatePath, $directory_temp, $file_type,'commitee');
                break;
            case 'daftar_kehadiran_peserta':
                self::generateDaftarKehadiran($application, $templatePath, $directory_temp, $file_type,'participant');
                break;
            case 'daftar_kehadiran_moderator':
                self::generateDaftarKehadiran($application, $templatePath, $directory_temp, $file_type,'moderator');
                break;
            case 'daftar_kehadiran_narasumber':
                self::generateDaftarKehadiran($application, $templatePath, $directory_temp, $file_type,'speaker');
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
        $role = Role::find($file_type->signed_role_id);
        if ($file_type->signed_role_id == 7) {
            $log_approval = LogApproval::where('user_id',$application->created_by)->where('application_id',$application->id)->where('department_id',$application->department_id)->first();
            // $log_approval = LogApproval::where('')
            // dd($log_approval);
        }else{
            $log_approval = LogApproval::getSigner($file_type->signed_role_id, $application->department_id,$file_type->trans_type, $application->id)->first();
        }
        
        $signer_position = $log_approval->position->name;
        $signer_name = $log_approval->user->name;
        if ($role->name !== 'dekan') {
            $get_chief_commitee = $application->participants()->where('is_signer_commitee',1)->first();
            $signer_name = $get_chief_commitee->name;
            $signer_position = 'Ketua Panitia';
            // $signer_position = $get_chief_commitee->commitee_position;
        }
        $meta = [
            'Tgl_cetak'   => ViewHelper::humanReadableDate($log_approval->updated_at,false),
            'Jabatan'     => ucwords($signer_position),
            'Lokasi'     => $log_approval->location_city,
            'Nama'     => ucwords($signer_name),
            'NIP'     => isset($log_approval->user->nip)?$log_approval->user->nip:null,
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
                switch ($key) {
                    case 'activity_name':
                        # code...
                        $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                        $templateProcessor->setValue($key, ucwords($value));
                        break;
                    case 'funding_source':
                        $value = $value==1? 'BLU':'BOPTN';
                        $templateProcessor->setValue($key, $value);
                        break;

                    default:
                        $templateProcessor->setValue($key, $value);
                        break;
                }
            }


            foreach ($application->detail->getAttributes() as $key => $value) {
                    switch ($key) {
                        case 'activity_dates':
                            # code...
                            $split_dates = explode(',',$value);
                            $human_readable_dates = array_map(function($date){
                                // tambahkan trim untuk hilangkan spasi
                                // $date = trim($date); // HILANGKAN SPASI DI SINI
                                $converted = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
                                return ViewHelper::humanReadableDate($converted);
                            },$split_dates);

                            $templateProcessor->setValue($key, implode($human_readable_dates));
                            break;
                        default:
                            $templateProcessor->setValue($key, $value);
                            break;
                    }

            }

        // Inject variabel
        $templateProcessor->setValue('department_name', $application->department->approvalDepartment()->first()?->name);
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
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
        $templateProcessor->setValue('nomor_surat_undangan', ucwords($get_nomor_surat->letter_number));
        $templateProcessor->setValue('nomor_surat_undangan_formatted_date', ucwords(Carbon::parse($get_nomor_surat->letter_date)->format('d M Y')));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
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
    public static function generateDaftarKehadiran($application, $templatePath, $directory_temp, $file_type,$participant_type)
    {
        $write_output = public_path($directory_temp);
        $get_participant = self::filterParticipantByType($participant_type,$application->participants);
        // dd($get_commitees);
            $table_participants = self::generateTableParticipant($participant_type, $get_participant);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

     foreach ($application->getAttributes() as $key => $value) {
                switch ($key) {
                    case 'activity_name':
                        # code...
                        $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                        $templateProcessor->setValue($key, ucwords($value));
                        break;
                    case 'funding_source':
                        $value = $value==1? 'BLU':'BOPTN';
                        $templateProcessor->setValue($key, $value);
                        break;
                    default:
                        $templateProcessor->setValue($key, $value);
                        break;
                }
            }


            foreach ($application->detail->getAttributes() as $key => $value) {
                    switch ($key) {
                        case 'activity_location':
                            $templateProcessor->setValue($key, ucwords($value));
                        break;
                        case 'activity_dates':
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
                            break;
                        default:
                            $templateProcessor->setValue($key, $value);
                            break;
                    }

            }

        // Inject variabel
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
        $templateProcessor->setValue('department_name', $application->department->approvalDepartment()->first()?->name);
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        // tables
            $templateProcessor->cloneRowAndSetValues($participant_type.'_name', $table_participants);



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
        // dd($get_commitees);


            $get_draft_cost = self::generateTableDraftCost($application->draftCostBudgets);

            $metadata_signer = self::getSignerMetadata($application,$file_type);
            $qrPath = self::generateQrCode($metadata_signer);
            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

                foreach ($application->getAttributes() as $key => $value) {
                switch ($key) {
                    case 'activity_name':
                        # code...
                        $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                        $templateProcessor->setValue($key, ucwords($value));
                        break;
                    case 'funding_source':
                        $value = $value==1? 'BLU':'BOPTN';
                        $templateProcessor->setValue($key, $value);
                        break;
                    default:
                        $templateProcessor->setValue($key, $value);
                        break;
                }
            }


            foreach ($application->detail->getAttributes() as $key => $value) {
                    switch ($key) {
                        case 'activity_location':
                            $templateProcessor->setValue($key, ucwords($value));
                        break;
                        case 'activity_dates':
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
                            break;
                        default:
                            $templateProcessor->setValue($key, $value);
                            break;
                    }

            }

        // Get All schedule speaker/moderator
        // End Get All schedule speaker/moderator


        $get_nomor_surat = $application->letterNumbers()->where('letter_name','nomor_surat_permohonan')->first();
        $get_recipient = $application->participants()->where('id',$app_file->participant_id)->first();
        $participant_type_name = $get_recipient->participantType->name ?? '';
        $get_session_schedule = collect();

        if (!empty($get_recipient->name) && !empty($get_recipient->institution)) {
            $field = $participant_type_name == 'Narasumber' ? 'speaker_text' : ($participant_type_name == 'Moderator' ? 'moderator_text' : null);
            if ($field) {
                $search = $get_recipient->name . '-' . $get_recipient->institution;
                $get_session_schedule = $application->schedules()->where($field, 'LIKE', "%$search%")->get();
            }
        }

        $array_date_session = [];
        foreach ($get_session_schedule as $value) {
            $array_date_session[$value->date][] = Carbon::parse($value->start_date)->format('H:i');
            $array_date_session[$value->date][] = Carbon::parse($value->end_date)->format('H:i');
        }

        $converted_session = ['time' => '', 'date' => ''];
        foreach ($array_date_session as $date => $times) {
            $converted_session['time'] .= ($converted_session['time'] ? ', ' : '') . $times[0] . ' - ' . end($times);
            $converted_session['date'] .= ($converted_session['date'] ? ', ' : '') . ViewHelper::humanReadableDate($date);
        }

        // Inject variabel
        $templateProcessor->setValue('session_lenght_hours', $converted_session['time']);
        $templateProcessor->setValue('session_lenght_dates', $converted_session['date']);
        $templateProcessor->setValue('recipient_name', ucwords($get_recipient->name));
        $templateProcessor->setValue('recipient_institution', ucwords($get_recipient->institution));
        $templateProcessor->setValue('nomor_surat_permohonan', ucwords($get_nomor_surat->letter_number));
        $templateProcessor->setValue('nomor_surat_permohonan_formatted_date', ucwords(Carbon::parse($get_nomor_surat->letter_date)->format('d M Y')));
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
        $templateProcessor->setValue('department_name', $application->department->approvalDepartment()->first()?->name);
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
        $templateProcessor->setValue('nomor_surat_tugas_uppercase', strtoupper($get_nomor_surat_tugas->letter_number));
        $templateProcessor->setValue('nomor_surat_tugas', ucwords($get_nomor_surat_tugas->letter_number));
        $templateProcessor->setValue('participant_type', ucwords($participant_type == 'speaker'?'narasumber':'moderator'));
        $templateProcessor->setValue('department_name', $application->department->approvalDepartment()->first()?->name);
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
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
    $item['nip'] = '   NIP. '.$item[$participant_type.'_nip'];
    $item['rank'] = $item[$participant_type.'_rank'];
    $item['functional_position'] = $item[$participant_type.'_functional_position'];
    $item['space'] = '.';
    unset($item[$participant_type.'_no']);
    unset($item[$participant_type.'_institution']);
    unset($item[$participant_type.'_position']);
    unset($item[$participant_type.'_nip']);
    unset($item[$participant_type.'_rank']);
    unset($item[$participant_type.'_functional_position']);
    return $item;

},$speaker_participant);


$new_data = [];
// dd($mapped_data);
foreach ($mapped_data as $key => $value) {
    # code...
    $get_values = array_values($value);
    $new_data = [...$new_data, ...$get_values];
}

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
        $templateProcessor->setValue('department_name', $application->department->approvalDepartment()->first()?->name);
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
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
    public static function generateNotulensi($application, $templatePath, $directory_temp, $file_type)
    {
        $write_output = public_path($directory_temp);
        // dd($get_commitees);

            $get_minutes = self::generateTableMinutes($application->minutes);

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

            foreach ($application->detail->getAttributes() as $key => $value) {
                    switch ($key) {
                        case 'activity_location':
                            $templateProcessor->setValue($key, ucwords($value));
                        break;
                        case 'activity_dates':
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
                            break;
                        default:
                            $templateProcessor->setValue($key, $value);
                            break;
                    }

            }
             foreach ($application->report->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }


        $get_nomor_surat = $application->letterNumbers()->where('letter_name','nomor_surat_undangan_peserta')->first();

        // Inject variabel
        $templateProcessor->setValue('nomor_surat_undangan', ucwords($get_nomor_surat->letter_number));
        // Inject variabel
        $templateProcessor->setValue('department_name', $application->department->approvalDepartment()->first()?->name);
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signed_status', $metadata_signer['status_surat']);
        $templateProcessor->setValue('activity_lenght_hours', self::getRundownTimeRanges($application->schedules));



            // dd($get_draft_cost);

            $templateProcessor->cloneRowAndSetValues('mn_topic', $get_minutes);


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

            foreach ($application->getAttributes() as $key => $value) {
                switch ($key) {
                    case 'activity_name':
                        # code...
                        $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                        $templateProcessor->setValue($key, ucwords($value));
                        break;
                    case 'funding_source':
                        $value = $value==1? 'BLU':'BOPTN';
                        $templateProcessor->setValue($key, $value);
                        break;
                    default:
                        $templateProcessor->setValue($key, $value);
                        break;
                }
            }


            foreach ($application->detail->getAttributes() as $key => $value) {
                    switch ($key) {
                        case 'activity_dates':
                            # code...
                            $split_dates = explode(',',$value);
                            $human_readable_dates = array_map(function($date){
                                $converted = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
                                return ViewHelper::humanReadableDate($converted);
                            },$split_dates);

                            $templateProcessor->setValue($key, implode($human_readable_dates));
                            break;
                        default:
                            $templateProcessor->setValue($key, $value);
                            break;
                    }

            }

        // Inject variabel

        $department_to_show = ViewHelper::departmentToShow($application->department);
        $templateProcessor->setValue('department_name', $department_to_show->name);
        $templateProcessor->setValue('department_name_uppercase', strtoupper($department_to_show->name));
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
        $templateProcessor->setValue('keterangan_mak', ucwords($application->letterNumbers()->where('letter_name','keterangan_mak')->first()->letter_number));
        $templateProcessor->setValue('nomor_sk_uppercase', strtoupper($application->letterNumbers()->where('letter_name','nomor_sk')->first()->letter_number));
        $templateProcessor->setValue('tanggal_sk', strtoupper(ViewHelper::humanReadableDate($application->letterNumbers()->where('letter_name','nomor_sk')->first()->letter_date,false)));
        $templateProcessor->setValue('tanggal_berlaku_sk', ucwords(ViewHelper::humanReadableDate($application->letterNumbers()->where('letter_name','tanggal_berlaku_sk')->first()->letter_number,false)));



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
            $getSigner = LogApproval::getSigner($file_type->signed_role_id, $application->department_id,$file_type->trans_type, $application->id)->first();

            // dd($application->getAttributes(),$application->detail->getAttributes());
            $templateProcessor = new TemplateProcessor($templatePath);

                        foreach ($application->getAttributes() as $key => $value) {
                switch ($key) {
                    case 'activity_name':
                        # code...
                        $templateProcessor->setValue($key.'_uppercase', strtoupper($value));
                        $templateProcessor->setValue($key, ucwords($value));
                        break;
                    case 'funding_source':
                        $value = $value==1? 'BLU':'BOPTN';
                        $templateProcessor->setValue($key, $value);
                        break;

                    default:
                        $templateProcessor->setValue($key, $value);
                        break;
                }
            }


            foreach ($application->detail->getAttributes() as $key => $value) {
                    switch ($key) {
                        case 'activity_dates':
                            # code...
                            $split_dates = explode(',',$value);
                            $human_readable_dates = array_map(function($date){
                                $converted = Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
                                return ViewHelper::humanReadableDate($converted);
                            },$split_dates);

                            $templateProcessor->setValue($key, implode($human_readable_dates));
                            break;
                        default:
                            $templateProcessor->setValue($key, $value);
                            break;
                    }

            }
            foreach ($application->report->getAttributes() as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }

            // $documentation_photos = $application->report->attachments;
            $need_to_delete_dir = [];
            $max_photos = 5; // jumlah maksimal placeholder di template

            $documentation_photos = $application->report->attachments()->where('type','document-photos')->get();
            foreach ($documentation_photos as $key => $photo) {
                $localPath = public_path('temp/documentation-photo/'.$application->id.'/photo_' . ($key+1) . '.' . pathinfo($photo->file->path, PATHINFO_EXTENSION));
                if (!file_exists(dirname($localPath))) {
                    mkdir(dirname($localPath), 0755, true);
                }
                $need_to_delete_dir[]=$localPath;
                file_put_contents($localPath, Storage::disk('minio')->get($photo->file->path));
                $templateProcessor->setImageValue('documentation_photo_' . ($key+1), [
                    'path' => $localPath,
                    'width' => 300,
                    'height' => 200,
                    'ratio' => true,
                ]);
            }
            // Isi placeholder kosong untuk sisa yang tidak ada file
            $temp=[];
            for ($i = count($documentation_photos) + 1; $i <= $max_photos; $i++) {
                $temp[]=$i;
                $templateProcessor->setValue('documentation_photo_' . $i, ' ');
            }


        // Inject variabel
        $templateProcessor->setValue('department_name', $application->department->approvalDepartment()->first()?->name);
        $templateProcessor->setValue('department_name_uppercase', strtoupper($application->department->approvalDepartment()->first()?->name));
        $templateProcessor->setValue('current_year', date("Y"));

        $templateProcessor->setValue('signed_location', $metadata_signer['Lokasi']);
        $templateProcessor->setValue('signed_date', $metadata_signer['Tgl_cetak']);
        $templateProcessor->setValue('signer_position', $metadata_signer['Jabatan']);
        $templateProcessor->setValue('signer_name', $metadata_signer['Nama']);
        $templateProcessor->setValue('signer_name_without_degree', strtoupper($getSigner->user->name_without_degree));
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

        foreach ($need_to_delete_dir as $key => $dir) {
             if (file_exists($dir)) {
                unlink($dir);
            }
        }
        // Hapus folder setelah file dihapus
        $folderPath = public_path('temp/documentation-photo/' . $application->id);
        if (is_dir($folderPath)) {
            // Hapus semua isi folder (jaga-jaga jika ada file lain)
            $files = glob($folderPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            // Hapus foldernya
            rmdir($folderPath);
        }
       


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
                    $peran = ucwords( $row['commitee_position']);
                } else {
                    $peran = ucwords(self::findName('participant', $row['participant_type_id']));
                }
            } else {
                $peran = '';
            }

            $rows[] = [
                $participantType.'_no'      => $number,
                $participantType .'_name'    => $row['name'],
                $participantType .'_institution' => $jabatan,
                $participantType .'_nip' => $row['nip'],
                $participantType .'_rank' => $row['rank'],
                $participantType .'_functional_position' => $row['functional_position'],
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
                'rd_name'   => ucwords($row->name),
                'rd_moderator_label'=> (!empty($row->moderator_text) ? 'Moderator' : ''),
                'rd_speaker_label'=> (!empty($row->speaker_text) ? 'Narasumber' : ''),
                'rd_speaker_list'   => self::speakerListToUnorderedString($row->speaker_text),
                'rd_moderator_list'   => self::speakerListToUnorderedString($row->moderator_text),
            ];
            $number++;
        }
        return $rows;
    }

    public static function generateTableMinutes($minutes){
        $rows = [];
        $number = 1;
        foreach ($minutes as $index => $row) {
            $rows[] = [
                'mn_no'      => $number,
                'mn_topic'    => $row->topic,
                'mn_explanation'    => $row->explanation,
                'mn_deadline'    => ViewHelper::humanReadableDate($row->deadline),
                'mn_follow_up'=> $row->follow_up,
                'mn_assignee'=> $row->assignee,
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
        
        $result = '';
        foreach ($speakers as $speaker) {
            if (trim($speaker) !== '') {
                list($name,$institution) = explode('-',$speaker);
                // $result .= '• '. trim($speaker).'<w:br/>';
                $result .= '• '. trim($name).'<w:br/> ('.trim($institution).')<w:br/>';
            }
        }
        // $result = '<ol>';
        // foreach ($speakers as $speaker) {
        //     if (trim($speaker) !== '') {
        //         $result .= '<li style="margin-bottom:5px;">' . trim($speaker) . '</li>';
        //     }
        // }
        // $result .= '</ol>';
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
            margin: 5,
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
