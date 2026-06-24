<?php

namespace App\Livewire\Forms;

use App\Models\ApplicationParticipant;
use App\Models\ParticipantType;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class TableRundown extends Component
{
    public $get_moderators = [];
    public $get_speakers = [];
    public $participants = [];

    public $handleDisable='';

    public $options = [];

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

    public function mount($rundowns,$participants=[],$handleDisable="",$activityDates="")
    {
        $this->handleDisable = $handleDisable;
        $this->dateOptions = $this->buildDateOptions($activityDates);

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

            // Konversi speakers dan moderators yang menggunakan format "nama-instansi" menjadi array
            if (!empty($row['speaker_text'])) {
                $this->options = [
                    $this->_buildOfficerOption('Narasumber', $row['speaker_text']),
                ];
            }

            if (!empty($row['moderator_text'])) {
                $row['moderator_text'] = explode(';', $row['moderator_text']);

                $moderators = $this->_buildOfficerOption('Narasumber', $row['speaker_text']);
                $this->options = [...$this->options,$moderators];
            }
        }

        return $data;
    }

    /**
     * Bangun satu entri opsi officer dari string mentah "nama-instansi" yang
     * dipisah ";" (mis. "Budi-UGM;Ani-ITB"), berdasarkan nama tipe peserta.
     *
     * Tiap officer diformat ke pola "nama###instansi###partypeid".
     *
     * @return array{slug:string,label:string,officers:array<int,string>}
     */
    private function _buildOfficerOption(string $participantTypeName, string $rawText): array
    {
        $partType = ParticipantType::whereName($participantTypeName)->first();
        $partTypeId = $partType->id ?? null;

        $officers = array_map(
            fn($item) => $this->_formatHashtagSeparator($item, $partTypeId),
            explode(';', $rawText)
        );

        return [
            'slug'     => $partType['slug'],
            'label'    => $partType['name'],
            'officers' => $officers,
        ];
    }

    /**
     * Ubah text berformat "nama - instansi" menjadi "nama###instansi###partypeid".
     *
     * Pemisah pertama "-" diperlakukan sebagai pembatas nama & instansi (spasi di
     * sekitarnya dirapikan). Jika instansi mengandung "-" lagi, sisanya tetap utuh.
     * partypeid ditempel di akhir hanya jika $parTypeId tidak null.
     *
     * Contoh: ("Budi - UGM", 2) => "Budi###UGM###2"
     */
    private function _formatHashtagSeparator($text, $parTypeId = null)
    {
        if (empty($text)) {
            return $text;
        }

        // Pisah pada "-" pertama saja agar instansi yang mengandung "-" tidak ikut terpecah.
        $parts = explode('-', $text, 2);
        $name = trim($parts[0]);
        $institution = isset($parts[1]) ? trim($parts[1]) : '';

        $result = $name . '###' . $institution;

        if ($parTypeId !== null) {
            $result .= '###' . $parTypeId;
        }

        return $result;
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
        }

        return $data;
    }

    public function debug(){
        // dd($this->normalizeData($this->rundown));
        $normalizeData = $this->normalizeData($this->rundown);
        $this->dispatch('transfer-rundowns',[...$normalizeData]);

    }
    public function syncRundown(){
        // dd($this->normalizeData($this->rundown));
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
        
    
        $only_opt_participants = array_filter($participant,function($pts){
                    return $pts['is_option_rundown'] === 1;
            });
        foreach ($only_opt_participants??[] as $key => $pt) {
                $part_type = ParticipantType::find($pt['participant_type_id'])->first();
                $spk_options = null;
                foreach ($this->options as $item) {
                    if ($item['slug'] === $part_type['slug']) {
                        $spk_options = $item;
                        break;
                    }
                }
                if ($spk_options) {
                    $dashed_pt =  $pt['name'].'-'.$pt['institution'];
                    $formatted_pt = $this->_formatHashtagSeparator($dashed_pt,$pt['participant_type_id']);
                    array_push($spk_options['officer'],$formatted_pt);
                }else{
                    $this->_buildOfficerOption()
                }
        }
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
