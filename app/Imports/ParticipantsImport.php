<?php

namespace App\Imports;

use App\Models\ParticipantType;
use App\Services\AuthService;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ParticipantsImport implements ToCollection
{
    private $speakers = [];
    private $commitees = [];
    private $participants = [];
    private $application_id = null;
    private $default_participant_fields = [];

    public $finest_participant_data = [];

    public $index_participant = 0;

    public function __construct($application_id)
    {
        $this->application_id = $application_id;
        $this->default_participant_fields = [
            'name' => null,
            'institution' => null,
            'nip' => null,
            'rank' => null,
            'functional_position' => null,
            'commitee_position' => null,
            'participant_type_id' => null,
            'is_signer_commitee' => 0,
            'application_id' => $this->application_id,
            'is_option_rundown' => 0,
            'department_id' => AuthService::currentAccess()['department_id']
        ];
    }

    public function collection(Collection $rows)
    {
        $this->collectParticipantData($rows);

        return ['participants' => $this->finest_participant_data];
    }

    public function collectParticipantData($rows){
        $this->selectrowColumn($rows, 2, 1, 6, 'speakers');
        $this->selectrowColumn($rows, 2, 10, 14, 'commitees');
        $this->selectrowColumn($rows, 2, 18, 22, 'participants');
    }

    public function cleanRawData($data,$type){
        switch ($type) {
            case 'speakers':
                $this->classifySpeakers($data);
                break;
            case 'commitees':
                $this->classifyCommitees($data);
                break;
            case 'participants':
                $this->classifyParticipants($data);
                break;
            default:
                dd('Nothing Match');
                break;
        }
    }

    private function selectrowColumn($rows,$start_row,$start_column,$end_column,$type){
        $data = [];
        for ($r = $start_row; $r < count($rows); $r++) {
            for ($c = $start_column; $c <= $end_column; $c++) {
                if (!empty($rows[$r][$c])) {
                    if ($rows[$r][$c] !== null || $rows[$r][$c] !== '') {
                        $data[$r][$c] = $rows[$r][$c];
                    }else{
                        $data[$r][$c] = null;
                    }
                }
            }
        }
        $this->cleanRawData($data,$type);
    }

    private function classifySpeakers($data){
        foreach ($data as $key => $value) {
            if (!empty($value[1]) && !empty($value[6]) ) {
                $this->finest_participant_data[$this->index_participant] = $this->default_participant_fields;
                $this->finest_participant_data[$this->index_participant]['name'] = $value[1] ?? null;
                $this->finest_participant_data[$this->index_participant]['nip'] = $value[2] ?? null;
                $this->finest_participant_data[$this->index_participant]['rank'] = $value[3] ?? null;
                $this->finest_participant_data[$this->index_participant]['functional_position'] = $value[4] ?? null;
                $this->finest_participant_data[$this->index_participant]['institution'] = $value[5] ?? null;
                $this->finest_participant_data[$this->index_participant]['is_option_rundown'] = 1;
                $this->finest_participant_data[$this->index_participant]['participant_type_id'] = ParticipantType::whereName(strtolower($value[6]))->first()?->id;
                $this->index_participant++;
            }
        }
    }

    private function classifyParticipants($data){
        foreach ($data as $key => $value) {
            if (!empty($value[18])) {
                $this->finest_participant_data[$this->index_participant] = $this->default_participant_fields;
                $this->finest_participant_data[$this->index_participant]['name'] = $value[18] ?? null;
                $this->finest_participant_data[$this->index_participant]['nip'] = $value[19] ?? null;
                $this->finest_participant_data[$this->index_participant]['rank'] = $value[20] ?? null;
                $this->finest_participant_data[$this->index_participant]['functional_position'] = $value[21] ?? null;
                $this->finest_participant_data[$this->index_participant]['institution'] = $value[22] ?? null;
                $this->finest_participant_data[$this->index_participant]['participant_type_id'] = ParticipantType::whereName('peserta')->first()?->id;
                $this->index_participant++;
            }
        }
    }

    private function classifyCommitees($data){
        foreach ($data as $key => $value) {
            if (!empty($value[10]) && !empty($value[11])) {
                $this->finest_participant_data[$this->index_participant] = $this->default_participant_fields;
                $this->finest_participant_data[$this->index_participant]['commitee_position'] = strtolower($value[10]);
                $this->finest_participant_data[$this->index_participant]['name'] = $value[11] ?? null;
                $this->finest_participant_data[$this->index_participant]['nip'] = $value[12] ?? null;
                $this->finest_participant_data[$this->index_participant]['rank'] = $value[13] ?? null;
                $this->finest_participant_data[$this->index_participant]['functional_position'] = $value[14] ?? null;
                $this->finest_participant_data[$this->index_participant]['is_signer_commitee'] = strtolower($value[10]) === 'ketua' ? 1 : 0;
                $this->finest_participant_data[$this->index_participant]['participant_type_id'] = ParticipantType::whereName('panitia')->first()?->id;
                $this->index_participant++;
            }
        }
    }
}
