<?php

namespace App\Imports;

use App\Models\Application;
use App\Models\CommiteePosition;
use App\Models\ParticipantType;
use App\Services\AuthService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;

class ApplicationsImport implements ToCollection
{

    private $speakers = [];
    private $commitees = [];
    private $participants = [];
    private $application_id = null;
    private $default_participant_fields = [];
    private $default_rundown_fields = [];
    private $default_draft_cost_fields = [];

    public $finest_participant_data = [];
    public $finest_draft_cost_data = [];
    public $finest_rundown_data = [];

    public $index_participant = 0;
    public $index_rundown = 0;
    public $index_draft_cost = 0;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function __construct($application_id)
    {
        $this->application_id = $application_id;
        $this->default_participant_fields = [
            'name' => null,
            'institution' => null,
            'commitee_position_id' => null,
            'participant_type_id' => null,
            'application_id' => $this->application_id,
            'department_id' => AuthService::currentAccess()['department_id']

        ];
        $this->default_draft_cost_fields = [
            'code' => null,
            'item' => null,
            'sub_item' => null,
            'volume' => null,
            'unit' => null,
            'cost_per_unit' => null,
            'total' => null,
            'application_id' => $this->application_id,
            'department_id' => AuthService::currentAccess()['department_id']

        ];
        $this->default_rundown_fields = [
            'name' => null,
            'date' => null,
            'start_date' => null,
            'end_date' => null,
            'moderator_text' => null,
            'speaker_text' => null,
            'application_id' => $this->application_id,
            'department_id' => AuthService::currentAccess()['department_id']

        ];
    }

    public function collection(Collection $rows)
    {
        $this->collectParticipantData($rows);
        $this->collectDraftCostData($rows);
        $this->collectRundownData($rows);

        return ['participants'=>$this->finest_participant_data,'draft_cost'=>$this->finest_draft_cost_data,'rundown'=>$this->finest_rundown_data];
    }

    public function collectParticipantData($rows){
        $this->selectrowColumn($rows, 2, 1, 3, 'speakers');
        $this->selectrowColumn($rows, 2, 7, 8, 'commitees');
        $this->selectrowColumn($rows, 2, 12, 13, 'participants');
    }

    public function collectDraftCostData($rows){
        $this->selectrowColumn($rows, 2, 26, 32, 'draft_costs');
    }
    public function collectRundownData($rows){
        $this->selectrowColumn($rows, 2, 17, 22, 'rundowns');
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
            case 'draft_costs':
                $this->classifyDraftCosts($data);
                break;
            case 'rundowns':
                $this->classifyRundowns($data);
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
                $this->finest_participant_data[$this->index_participant] = $this->default_participant_fields;
                $this->finest_participant_data[$this->index_participant]['name'] = $value[1] ?? null;
                $this->finest_participant_data[$this->index_participant]['institution'] = $value[2] ?? null;
                $this->finest_participant_data[$this->index_participant]['participant_type_id'] = ParticipantType::whereName(strtolower($value[3]))->first()?->id;
                // $this->finest_participant_data[$this->index_participant]['type'] = 'Speakers';
                $this->index_participant++;
            }
        }
    }
    private function classifyParticipants($data){
        foreach ($data as $key => $value) {
            if (!empty($value[12])) {
            $this->finest_participant_data[$this->index_participant] = $this->default_participant_fields;
            $this->finest_participant_data[$this->index_participant]['name'] = $value[12] ?? null;
            $this->finest_participant_data[$this->index_participant]['institution'] = $value[13] ?? null;
            $this->finest_participant_data[$this->index_participant]['participant_type_id'] = ParticipantType::whereName('peserta')->first()?->id;
            // $this->finest_participant_data[$this->index_participant]['type'] = 'Participants';
            $this->index_participant++;
            }
        }
    }
    private function classifyCommitees($data){
        foreach ($data as $key => $value) {
            if (!empty($value[8])) {
                $this->finest_participant_data[$this->index_participant] = $this->default_participant_fields;
                $this->finest_participant_data[$this->index_participant]['name'] = $value[8] ?? null;
                $this->finest_participant_data[$this->index_participant]['commitee_position_id'] = CommiteePosition::whereName(strtolower($value[7]))->first()?->id;
                $this->finest_participant_data[$this->index_participant]['participant_type_id'] = ParticipantType::whereName('panitia')->first()?->id;
                $this->index_participant++;
            }

        }
    }

    private function classifyDraftCosts($data){
        foreach ($data as $key => $value) {
            if (!empty($value[26]) && !empty($value[27])) {

                $total =  !empty($value[29]) && !empty($value[31]) && is_numeric($value[29]) && is_numeric($value[31])  ? $value[29]*$value[31]  : null;

                $this->finest_draft_cost_data[$this->index_draft_cost] = $this->default_draft_cost_fields;
                $this->finest_draft_cost_data[$this->index_draft_cost]['code'] = $value[26] ?? null;
                $this->finest_draft_cost_data[$this->index_draft_cost]['item'] = $value[27] ?? null;
                $this->finest_draft_cost_data[$this->index_draft_cost]['sub_item'] = $value[28] ?? null;
                $this->finest_draft_cost_data[$this->index_draft_cost]['volume'] = $value[29] ?? null;
                $this->finest_draft_cost_data[$this->index_draft_cost]['unit'] = $value[30] ?? null;
                $this->finest_draft_cost_data[$this->index_draft_cost]['cost_per_unit'] = $value[31] ?? null;
                $this->finest_draft_cost_data[$this->index_draft_cost]['total'] = $total;
                // $this->finest_draft_cost_data[$this->index_draft_cost]['type'] = 'darft_cost';
                $this->index_draft_cost++;
            }



        }
    }

    private function classifyRundowns($data){
        foreach ($data as $key => $value) {
            // $this->default_rundown_fields = [
            //     'name' => null,
            //     'date' => null,
            //     'start_date' => null,
            //     'end_date' => null,
            //     'moderator_text' => null,
            //     'speaker_text' => null,
            //     'application_id' => $this->application_id,
            //     'department_id' => AuthService::currentAccess()['department_id']
            //
            // ];
            if (!empty($value[17])) {

                $date = !empty($value[17]) ? Carbon::createFromFormat('d/m/Y', $value[17]): null;
                $this->finest_rundown_data[$this->index_rundown] = $this->default_rundown_fields;
                $this->finest_rundown_data[$this->index_rundown]['date'] = $date;
                $this->finest_rundown_data[$this->index_rundown]['name'] = $value[20] ?? null;
                $this->finest_rundown_data[$this->index_rundown]['start_date'] = $this->joinDateTime($value[17],$value[18]);
                $this->finest_rundown_data[$this->index_rundown]['end_date'] = $this->joinDateTime($value[17], $value[19]);
                $this->finest_rundown_data[$this->index_rundown]['speaker_text'] = $value[21] ?? null;
                $this->finest_rundown_data[$this->index_rundown]['moderator_text'] = $value[22] ?? null;
                // $this->finest_rundown_data[$this->index_rundown]['type'] = 'rundown';
                $this->index_rundown++;
            }

        }
    }


    public function joinDateTime($date,$decimal_time){

        $date = Carbon::createFromFormat('d/m/Y', $date);
        // Mendapatkan bagian jam dan menit
        $hours = $decimal_time * 24; // 14.0 jam
        $hour = floor($hours); // 14 jam
        $minute = round(($hours - $hour) * 60); // 0 menit

        $date_time = $date->setTime($hour, $minute);
        $date_time = $date_time;

        return $date_time;
    }
}
