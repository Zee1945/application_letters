<?php

namespace App\Livewire\Forms;

use App\Models\ParticipantType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Exists;
use Livewire\Attributes\On;
use Livewire\Component;

class TableRundown extends Component
{
    public $application_id;
    public $get_moderators = [];
    public $get_speakers = [];
    public $participants = [];

    public $handleDisable='';

    /**
     * Pesan error format jam per-baris rundown.
     * Bentuk: [index => ['start' => '...', 'end' => '...']]
     */
    public $timeErrors = [];

    public $options = ['opt_moderators' => [], 'opt_speakers' => []];
    public $options_2 = [];
    public $participant_types = [];

    /**
     * Opsi tanggal untuk dropdown, diambil dari activity_dates (application_details).
     * Bentuk: [['value' => 'Y-m-d', 'label' => 'Rabu, 17 Jun 2026'], ...]
     */
    public $dateOptions = [];

    // Default 4 rows
    public $rundown = [
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => [],'officer_text'=>[]],
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => [],'officer_text'=>[]],
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => [],'officer_text'=>[]],
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => [],'officer_text'=>[]],
    ];

    public function mount($rundowns,$participants=[],$handleDisable="",$activityDates="",$applicationId=null)
    {
        $this->handleDisable = $handleDisable;
        $this->application_id = $applicationId;
        $this->dateOptions = $this->buildDateOptions($activityDates);
        $this->participant_types = ParticipantType::get()->toArray();

        $this->rundown = count($rundowns) > 0
            ? $this->denormalizeData($rundowns)
            : $this->applyDefaultDate($this->rundown);
       if (count($participants)>0) {
            $this->receiveParticipant($participants);
       }
       
    }

    /**
     * Pecah string activity_dates (format "d-m-Y" dipisah koma) menjadi opsi dropdown.
     */
    private function buildDateOptions($activityDates): array
    {
        if (empty($activityDates)) {
            return [];
        }

        Carbon::setLocale('id');
        $options = [];
        foreach (explode(',', $activityDates) as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }
            try {
                $date = Carbon::createFromFormat('d-m-Y', $raw);
                $options[] = [
                    'value' => $date->format('Y-m-d'),
                    'label' => $date->translatedFormat('l, d M Y'), // mis. "Rabu, 17 Jun 2026"
                ];
            } catch (\Throwable $e) {
                // lewati tanggal yang formatnya tidak valid
            }
        }
        usort($options, function($a, $b) {
            return $a['value'] <=> $b['value'];
        });

        return $options;
    }

    /**
     * Set tanggal default tiap baris ke opsi pertama (jika tersedia).
     */
    private function applyDefaultDate($rows): array
    {
        $default = $this->dateOptions[0]['value'] ?? '';
        foreach ($rows as &$row) {
            if (empty($row['date'])) {
                $row['date'] = $default;
            }
        }
        return $rows;
    }

    /**
     * Opsi dropdown untuk satu baris tertentu.
     *
     * Mengembalikan dateOptions standar. Jika nilai $date baris ini tidak ada
     * di antara opsi standar (data lawas yang di luar activity_dates), maka
     * opsi legacy ditambahkan agar nilai lama tetap tampil & terpilih — tidak
     * tertimpa diam-diam oleh opsi pertama saat disimpan.
     *
     * Baris baru (addRow) tidak terpengaruh karena defaultnya memakai opsi valid.
     */
    public function dateOptionsFor($date): array
    {
        $options = $this->dateOptions;

        if (empty($date)) {
            return $options;
        }

        // Cek apakah $date sudah ada di opsi standar.
        foreach ($options as $opt) {
            if ($opt['value'] === $date) {
                return $options;
            }
        }

        // Nilai legacy di luar jadwal: tampilkan apa adanya dengan penanda.
        Carbon::setLocale('id');
        $label = $date;
        try {
            $label = Carbon::parse($date)->translatedFormat('l, d M Y');
        } catch (\Throwable $e) {
            // biarkan label = nilai mentah jika gagal di-parse
        }

        array_unshift($options, [
            'value' => $date,
            'label' => '⚠ (di luar jadwal) ' . $label ,
        ]);

        return $options;
    }
    public function render()
    {   
        $this->participant_types = ParticipantType::get()->toArray(); 
        return view('livewire.forms.table-rundown');
    }
    public function filterUserByType($participant_type_name)
    {
        $ids = ParticipantType::whereIn('name', [$participant_type_name])->get()->pluck('id')->toArray();
        return array_filter($this->participants, function ($item) use ($ids) {
            if (in_array($item['participant_type_id'], $ids)) {
                return $item;
            }
        });
    }

    public function addRow()
    {
        $this->rundown[] = [
            'date' => $this->dateOptions[0]['value'] ?? '',
            'start_date' => '',
            'end_date' => '',
            'name' => '',
            'speaker_text' => [],
            'moderator_text' => [],
            'officer_text' => [],
        ];
    }

    public function denormalizeData($rundowns)
    {
        $data = $rundowns;

        foreach ($data as &$row) {
            // Normalisasi date ke format Y-m-d agar cocok dengan value opsi dropdown.
            if (!empty($row['date'])) {
                $row['date'] = date('Y-m-d', strtotime($row['date']));
            }

            // Ambil jam dari start_date dan end_date
            if (!empty($row['start_date'])) {
                $row['start_date'] = date('H:i', strtotime($row['start_date']));  // Ambil hanya jam dan menit
            }
            if (!empty($row['end_date'])) {
                $row['end_date'] = date('H:i', strtotime($row['end_date']));  // Ambil hanya jam dan menit
            }
            $join_all_officers = [];

            // Konversi speakers dan moderators yang menggunakan format "nama-instansi" menjadi array
            if (!empty($row['speaker_text'])) {

                $row['speaker_text'] = explode(';', $row['speaker_text']);
                     $matched = array_filter($this->participant_types, function($pt){
                            return $pt['slug'] === 'narasumber';
                    });
                    $prt_type_spk = reset($matched);
                $format_hashtag_spk = array_map(function($spk) use ($prt_type_spk){
                    list($name,$institution) =  explode('-',$spk);
                    $institution = empty($institution) ? 'null' : $institution;
                    return $name.'###'.$institution.'###'.$prt_type_spk['id'];
                },$row['speaker_text']);
                // array_push($join_all_officers,$format_hashtag_spk);
                $join_all_officers = array_merge($join_all_officers,$format_hashtag_spk);

            }

            if (!empty($row['moderator_text'])) {
                $row['moderator_text'] = explode(';', $row['moderator_text']);
                $matched_mod = array_filter($this->participant_types, function($pt){
                            return $pt['slug'] === 'moderator';
                    });
                    $prt_type_spk = reset($matched_mod);
                $format_hashtag_mod = array_map(function($spk) use ($prt_type_spk){
                    list($name,$institution) =  explode('-',$spk);
                    $institution = empty($institution) ? 'null' : $institution;
                    return $name.'###'.$institution.'###'.$prt_type_spk['id'];
                },$row['moderator_text']);
                $join_all_officers = array_merge($join_all_officers,$format_hashtag_mod);

            }

            // officer_text tersimpan sebagai string "a###b###c;..." dipisah ";".
            // Pecah jadi array agar checkbox options_2 bisa tahu mana yang tercentang.
            if (!empty($row['officer_text'])) {
                // $row['officer_text'] = explode(';', $row['officer_text']);
                $join_all_officers = array_merge($join_all_officers,explode(';', $row['officer_text']));
            }
            $row['officer_text'] = [...$join_all_officers];

        }

        return $data;
    }

    /**
     * Validasi ketat format jam "H:i" (24 jam, 00:00–23:59).
     * Menerima kosong (dianggap valid; ditangani oleh guard !empty di tempat lain).
     */
    private function isValidTime($value): bool
    {
        if (empty($value)) {
            return true;
        }
        try {
            $parsed = Carbon::createFromFormat('H:i', $value);
            // createFromFormat bisa "memaafkan" sebagian input; pastikan hasil format ulang sama persis.
            return $parsed !== false && $parsed->format('H:i') === $value;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Periksa format jam tiap baris rundown, isi $this->timeErrors.
     * Mengembalikan true bila semua valid.
     */
    public function validateTimes(): bool
    {
        $this->timeErrors = [];
        $valid = true;

        foreach ($this->rundown as $index => $row) {
            $rowErrors = [];

            if (!empty($row['start_date']) && !$this->isValidTime($row['start_date'])) {
                $rowErrors['start'] = 'Format jam mulai harus JJ:MM (contoh 08:00).';
            }
            if (!empty($row['end_date']) && !$this->isValidTime($row['end_date'])) {
                $rowErrors['end'] = 'Format jam selesai harus JJ:MM (contoh 10:00).';
            }

            if (!empty($rowErrors)) {
                $this->timeErrors[$index] = $rowErrors;
                $valid = false;
            }
        }

        return $valid;
    }

    public function normalizeData($rundowns)
    {
        $fulfil_data = array_filter($rundowns,function($item){
            return !empty($item['date']) && !empty($item['start_date']) && !empty($item['start_date']) && !empty($item['name']) ;
        });
        $data = $fulfil_data;

        foreach ($data as &$row) {
            // Gabungkan date dengan start_date dan end_date untuk membentuk datetime
            if (!empty($row['date']) && !empty($row['start_date'])) {
                $row['start_date'] = $row['date'] . ' ' . $row['start_date'];  // Gabungkan tanggal dengan waktu mulai
            }

            if (!empty($row['date']) && !empty($row['end_date'])) {
                $row['end_date'] = $row['date'] . ' ' . $row['end_date'];  // Gabungkan tanggal dengan waktu selesai
            }

            // Konversi array speakers dan moderators kembali menjadi string dengan pemisah ";"
            if (!empty($row['speaker_text'])) {
                $row['speaker_text'] = implode(';', $row['speaker_text']);  // Gabungkan array menjadi string dengan pemisah ;
            } else {
                $row['speaker_text'] = null;
            }

            if (!empty($row['moderator_text'])) {
                $row['moderator_text'] = implode(';', $row['moderator_text']);  // Gabungkan array menjadi string dengan pemisah ;
            }else{
                $row['moderator_text'] = null;
            }
            if (!empty($row['officer_text'])) {
                $row['officer_text'] = implode(';', $row['officer_text']);  // Gabungkan array menjadi string dengan pemisah ;
            }else{
                $row['officer_text'] = null;
            }
        }

        return $data;
    }

    public function debug(){
        $normalizeData = $this->normalizeData($this->rundown);
        $this->dispatch('transfer-rundowns',[...$normalizeData]);

    }
    public function syncRundown(){
        // Jangan teruskan data bila ada format jam yang tidak valid,
        // agar nilai rusak tidak sampai tersimpan ke DB (draft maupun submit).
        if (!$this->validateTimes()) {
            return;
        }
        $normalizeData = $this->normalizeData($this->rundown);
        $this->dispatch('transfer-rundowns',[...$normalizeData]);
    }


    public function removeRow($index)
    {
        if (count($this->rundown) > 1) {
            array_splice($this->rundown, $index, 1);
        }
            $normalizeData = $this->normalizeData($this->rundown);
            $this->dispatch('transfer-rundowns', rundowns: $normalizeData);
    }

    public function addSpeaker($index)
    {
        $this->rundown[$index]['speaker_text'][] = '';  // Menambah input narasumber baru
        $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
        $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
    }

    public function removeSpeaker($index, $subIndex)
    {
        if (count($this->rundown[$index]['speaker_text']) > 0) {
            array_splice($this->rundown[$index]['speaker_text'], $subIndex, 1); // Menghapus narasumber
            $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
            $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
        }
    }

    public function addModerator($index)
    {
        $this->rundown[$index]['moderator_text'][] = '';  // Menambah input narasumber baru
        $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
        $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
    }

    public function removeModerator($index, $subIndex)
    {
        if (count($this->rundown[$index]['moderator_text']) > 0) {
            array_splice($this->rundown[$index]['moderator_text'], $subIndex, 1); // Menghapus narasumber
            $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
            $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
        }
    }

    #[On('transfer-participant-to-rundown')]
    public function receiveParticipant($participant)
    {
       $this->participant_types = ParticipantType::get()->toArray();
        $moderator_id = ParticipantType::where('name','Moderator')->first()->id;
        $speaker_id = ParticipantType::where('name','Narasumber')->first()->id;
        $option_participants = array_filter($participant,function($par){
            return $par['is_option_rundown'] === 1;
        });
        foreach ($option_participants??[] as $key => $pt) {
            //    if ($pt['participant_type_id'] == $moderator_id) {
            //     $this->options['opt_moderators'][]=['text'=>$pt['name'].'-' .$pt['institution']];
            //    }
            //    if ($pt['participant_type_id'] == $speaker_id) {
            //     $this->options['opt_speakers'][]=['text'=>$pt['name'].'-' .$pt['institution']];
            //    }  
               
               $this->buildOptions($pt);

        }
      
    }

    public function buildOptions($prt){
        // dd($prt);
        // array_filter mengembalikan array of array; ambil elemen pertama yang cocok.
        $matched = array_filter($this->participant_types, function($pt) use($prt){
                return $prt['participant_type_id'] === $pt['id'];
        });
        $prt_type = reset($matched);

        // Tidak ada tipe peserta yang cocok — tidak ada yang bisa dibangun.
        if (empty($prt_type)) {
            return;
        }
        
        // $formatted_ofc = $this->_formatHashtagSeparator($prt['name'], $prt['institution'], $prt['participant_type_id']);
        $institution = empty($prt['institution']) ? 'null' : $prt['institution'];
        $formatted_ofc = $prt['name'].'###'.$institution.'###'.$prt_type['id'] ;

        // Cari index entri opsi yang slug-nya sama. Simpan index (bukan salinan)
        // agar bisa memodifikasi $this->options_2 secara langsung.
        $foundIndex = null;
        foreach ($this->options_2 as $key => $item) {
            if ($item['slug'] === $prt_type['slug']) {
                $foundIndex = $key;
                break;
            }
        }

        if ($foundIndex === null) {
            // Belum ada: buat entri baru.
            $this->options_2[] = [
                'slug'     => $prt_type['slug'],
                'label'    => $prt_type['name'],
                'officers' => [$formatted_ofc],
             ];
        } else {
            // Sudah ada: tambahkan officer baru (hindari duplikat).
            if (!in_array($formatted_ofc, $this->options_2[$foundIndex]['officers'], true)) {
                $this->options_2[$foundIndex]['officers'][] = $formatted_ofc;
            }
        }
    }

    private function _formatHashtagSeparator(string $name,string $institution,$parTypeId = null)
    {
        if (empty($name)) {
            return $name;
        }
        $result = $name . '###' . $institution;

        if ($parTypeId !== null) {
            $result .= '###' . $parTypeId;
        }

        return $result;
    }

    public function _formatStripe($user_text)
    {
        list($name,$institution) = explode('###',$user_text);

        if (empty($institution) || $institution === 'null') {
            return $name;
        }

        return $name.' - '.$institution;
    }

    public function toggleSpeaker($rowIndex, $speakerText)
    {
        if (!isset($this->rundown[$rowIndex]['speaker_text'])) {
            $this->rundown[$rowIndex]['speaker_text'] = [];
        }
        
        $currentSpeakers = $this->rundown[$rowIndex]['speaker_text'];        
        if (in_array($speakerText, $currentSpeakers)) {
            $this->rundown[$rowIndex]['speaker_text'] = array_values(
                array_diff($currentSpeakers, [$speakerText])
            );
        } else {
            $this->rundown[$rowIndex]['speaker_text'][] = $speakerText;
        }
        
        // Dispatch update
        $normalizeData = $this->normalizeData($this->rundown);
        $this->dispatch('transfer-rundowns', rundowns: $normalizeData);
    }
    public function toggleOfficer($rowIndex, $officerText)
    {
        
        if (!isset($this->rundown[$rowIndex]['officer_text'])) {
            $this->rundown[$rowIndex]['officer_text'] = [];
        }
        
        $currentOfficers = $this->rundown[$rowIndex]['officer_text'];
        
        if (in_array($officerText, $currentOfficers)) {
            $this->rundown[$rowIndex]['officer_text'] = array_values(
                array_diff($currentOfficers, [$officerText])
            );
        } else {
            $this->rundown[$rowIndex]['officer_text'][] = $officerText;
        }
        // Dispatch update
        $normalizeData = $this->normalizeData($this->rundown);
        $this->dispatch('transfer-rundowns', rundowns: $normalizeData);
    }
    public function toggleModerator($rowIndex, $moderatorText)
    {
        if (!isset($this->rundown[$rowIndex]['moderator_text'])) {
            $this->rundown[$rowIndex]['moderator_text'] = [];
        }
        
        $currentModerators = $this->rundown[$rowIndex]['moderator_text'];
        
        if (in_array($moderatorText, $currentModerators)) {
            $this->rundown[$rowIndex]['moderator_text'] = array_values(
                array_diff($currentModerators, [$moderatorText])
            );
        } else {
            $this->rundown[$rowIndex]['moderator_text'][] = $moderatorText;
        }
        
        // Dispatch update
        $normalizeData = $this->normalizeData($this->rundown);
        $this->dispatch('transfer-rundowns', rundowns: $normalizeData);
    }

    #[On('resetRundown')]
    public function resetRundown(){
        $backup_rundown = [
            ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
            ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
            ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
            ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
        ];
        $this->rundowns = $backup_rundown;
    }
}
