<?php

namespace App\Exports;

use App\Models\Application;
use App\Models\ApplicationParticipant;
use App\Models\ApplicationDraftCostBudget;
use App\Models\ApplicationRundown;
use App\Models\CommiteePosition;
use App\Models\ParticipantType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApplicationsExport implements FromArray, WithStyles
{
    protected $application_id;
    protected $participants;
    protected $draft_costs;


    protected $title_rows = [
        '', // Index 0
        'Pilih Narasumber dan Moderator', // Index 1
        '', // Index 2
        '', // Index 3
        '', // Index 4
        '', // Index 5
        '', // Index 6
        'Pilih Panitia', // Index 7
        '', // Index 8
        '', // Index 9
        '', // Index 10
        '', // Index 11
        'Pilih Peserta', // Index 12
        '', // Index 13
        '', // Index 14
        '', // Index 15
        '', // Index 16
        'Susun Rencana Anggaran Biaya', // Index 17
    ];

    protected $heading_rows = [
        'No', // Index 0
        'Nama Narasumber', // Index 1
        'Institusi Narasumber', // Index 2
        'Peran', // Index 3
        '', // Index 4
        '', // Index 5
        'No', // Index 6
        'Posisi Panitia', // Index 7
        'Nama Panitia', // Index 8
        '', // Index 9
        '', // Index 10
        'No', // Index 11
        'Nama Peserta', // Index 12
        'Institusi Peserta', // Index 13
        '', // Index 14
        '', // Index 15
        'No', // Index 16
        'Kode RAB', // Index 17
        'Item RAB', // Index 18
        'Sub Item RAB', // Index 19
        'Volume', // Index 20
        'Satuan', // Index 21
        'Harga per Unit', // Index 22
        'Total', // Index 23
    ];

    public function __construct($participants, $draft_costs)
    {
        $this->participants = $participants;
        $this->draft_costs = $draft_costs;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];
        $data[] = $this->title_rows;
        $data[] = $this->heading_rows;

        $speaker = $this->filterParticipantByName('Narasumber');
        $moderator = $this->filterParticipantByName('Moderator');
        $speaker_moderator = array_merge($speaker, $moderator);

        $commitee = $this->filterParticipantByName('Panitia');
        // dd($commitee);
        $participants = $this->filterParticipantByName('Peserta');
        $draft_costs = $this->draft_costs;

        $maxRows = max(
            count($speaker_moderator),
            count($commitee),
            count($participants),
            count($draft_costs),
            10 // minimal 10 baris
        );

        // Loop untuk setiap baris data
        for ($i = 0; $i < $maxRows; $i++) {
            $row = array_fill(0, 24, ''); // 24 kolom (index 0-23)
            $no = $i+1;

            // Menambahkan data untuk Narasumber dan Moderator
            if (array_key_exists($i, $speaker_moderator)) {
                $speaker = array_values($speaker_moderator)[$i];
                $row[0] = $no;
                $row[1] = $speaker['name'] ?? '';
                $row[2] = $speaker['institution'] ?? '';
                $row[3] = $speaker['participant_type_name'] ?? '';
            }

            // // Menambahkan data untuk Panitia
            if (array_key_exists($i, $commitee)) {
                $commitee_item = array_values($commitee)[$i];
                // dd($commitee_item['commitee_position_id']);
                $row[6] = $no;
                $row[7] = $commitee_item['commitee_position'] ?? '';
                $row[8] = $commitee_item['name'] ?? '';
            }

            // // Menambahkan data untuk Peserta
            // dd($commitee);
            // dd($participants);
            if (array_key_exists($i, $participants)) {
                $participant = array_values($participants)[$i];
                $row[11] = $no;
                $row[12] = $participant['name'] ?? '';
                $row[13] = $participant['institution'] ?? '';
            }

            // // Menambahkan data untuk RAB (Anggaran)
            if (array_key_exists($i, $draft_costs)) {
                $cost = array_values($draft_costs)[$i];
                $row[16] = $no;
                $row[17] = $cost['code'] ?? '';
                $row[18] = $cost['item'] ?? '';
                $row[19] = $cost['sub_item'] ?? '';
                $row[20] = $cost['volume'] ?? '';
                $row[21] = $cost['unit'] ?? '';
                $row[22] = $cost['price_per_unit'] ?? '';
                $row[23] = $cost['total'] ?? '';
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header styling
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                ]
            ],
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                ]
            ],
        ];
    }

    public function filterParticipantByName($name)
    {
        
        $participant_type = ParticipantType::whereName($name)->first();
        $filtererd = array_values(array_filter($this->participants, function ($item) use ($participant_type) {
            return $item['participant_type_id'] == $participant_type->id;
        }));
        $mapped = array_map(function($item) use ($name){
            $item['participant_type_name'] = $name;
            if ($name == 'Panitia') {
                $commitee_position_name = CommiteePosition::find($item['commitee_position_id'])->name;
                $item['commitee_position'] = $commitee_position_name;
            }
            return $item;
        },$filtererd);

        return $mapped;
    }
}
