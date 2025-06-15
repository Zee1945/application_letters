<?php

namespace App\Imports;

use App\Models\Application;
use App\Models\CommiteePosition;
use App\Models\ParticipantType;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;

class ApplicationsImport implements ToCollection
{

    private $speakers = [];
    private $commitees = [];
    private $participants = [];
    private $application_id = null;
    private $default_fields = [];

    public $finest_data = [];

    public $index = 0;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function __construct($application_id)
    {
        $this->application_id = $application_id;
        $this->default_fields = [
            'name' => null,
            'institution' => null,
            'commitee_position_id' => null,
            'participant_type_id' => null,
            'application_id' => $this->application_id,
            'type' => null,
        ];;
    }

    public function collection(Collection $rows)
    {
        $this->selectrowColumn($rows,2,1,3,'speakers');
        $this->selectrowColumn($rows,2,6,7, 'commitees');
        $this->selectrowColumn($rows,2,10,11,'participants');

        return $this->finest_data;
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
            if (!empty($value[1]) && !empty($value[3]) ) {
                $this->finest_data[$this->index] = $this->default_fields;
                $this->finest_data[$this->index]['name'] = $value[1] ?? null;
                $this->finest_data[$this->index]['institution'] = $value[2] ?? null;
                $this->finest_data[$this->index]['participant_type_id'] = ParticipantType::whereName(strtolower($value[3]))->first()?->id;
                $this->finest_data[$this->index]['type'] = 'Speakers';
                $this->index++;
            }
        }
    }
    private function classifyParticipants($data){
        foreach ($data as $key => $value) {
            if (!empty($value[10])) {
            $this->finest_data[$this->index] = $this->default_fields;
            $this->finest_data[$this->index]['name'] = $value[10] ?? null;
            $this->finest_data[$this->index]['institution'] = $value[11] ?? null;
            $this->finest_data[$this->index]['participant_type_id'] = ParticipantType::whereName('peserta')->first()?->id;
            $this->finest_data[$this->index]['type'] = 'Participants';
            $this->index++;
            }
        }
    }
    private function classifyCommitees($data){
        foreach ($data as $key => $value) {
            if (!empty($value[7])) {
                $this->finest_data[$this->index] = $this->default_fields;
                $this->finest_data[$this->index]['name'] = $value[7] ?? null;
                $this->finest_data[$this->index]['commitee_position_id'] = CommiteePosition::whereName(strtolower($value[6]))->first()?->id;
                $this->finest_data[$this->index]['participant_type_id'] = ParticipantType::whereName('panitia')->first()?->id;
                $this->finest_data[$this->index]['type'] = 'Commitees';
                $this->index++;
            }

        }
    }
}
