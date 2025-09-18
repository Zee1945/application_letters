<?php

namespace App\Exports;

use App\Models\ParticipantType;
use App\Models\CommiteePosition;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApplicationsExport implements FromView,WithEvents
{
    protected $participants;
    protected $draft_costs;

    public function __construct($participants, $draft_costs)
    {
        $this->participants = $participants;
        $this->draft_costs = $draft_costs;
    }

    /**
     * Return the view for the export.
     *
     * @return View
     */
    public function view(): View
    {
        $speaker = $this->filterParticipantByName('Narasumber');
        $moderator = $this->filterParticipantByName('Moderator');
        $speaker_moderator = array_merge($speaker, $moderator);

        $commitee = $this->filterParticipantByName('Panitia');
        $participants = $this->filterParticipantByName('Peserta');
        $draft_costs = $this->draft_costs;

        return view('exports.application', [
            'speaker_moderator' => $speaker_moderator,
            'commitee' => $commitee,
            'participants' => $participants,
            'draft_costs' => $draft_costs,
        ]);
    }

    /**
     * Apply styles to the worksheet.
     *
     * @param Worksheet $sheet
     * @return array
     */

    public function filterParticipantByName($name)
    {
        $participant_type = ParticipantType::whereName($name)->first();

        if (!$participant_type) {
            return [];
        }

        $filtered = array_filter($this->participants, function ($item) use ($participant_type) {
            return $item['participant_type_id'] == $participant_type->id;
        });

        $mapped = array_map(function ($item) use ($name) {
            $item['participant_type_name'] = $name;
            // if ($name == 'Panitia') {
            //     $commitee_position_name = CommiteePosition::find($item['commitee_position_id'])->name ?? '';
            //     $item['commitee_position'] = $commitee_position_name;
            // }
            return $item;
        }, array_values($filtered));

        return $mapped;
    }

     public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Auto size for all columns
                foreach (range('A', 'Z') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}