<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\On;
use Livewire\Component;

class TableDraftCost extends Component
{

    public $draft_costs = [];
    public $current_code = '';
    public function render()
    {
        return view('livewire.forms.table-draft-cost');
    }

    #[On('transfer-draft-costs')]
    public function receiveRundowns($draft_costs)
    {
        $this->draft_costs = $draft_costs;
        // $this->draft_costs = $this->getDistinctDataByCodeAndItem($draft_costs);
    }

    public function debugger(){
        $data = $this->getDistinctDataByCodeAndItem( $this->draft_costs);
        dd($data);
    }

    public function mapDataByCodeAndItem($data)
    {

    }
    public function getDistinctDataByCodeAndItem($data)
    {
        // $data = [
        //     ['code' => 'MAK', 'item' => '5123.BGCG.001.065.GS.525122'],
        //     ['code' => '525112', 'item' => 'Belanja Barang'],
        //     ['code' => '525113', 'item' => 'Belanja Jasa']
        // ];
        // Membuat kombinasi unik berdasarkan code dan item
        $uniqueData = array_values(
            array_unique(
                array_map(function ($value) {
                    return $value['code'] . '|' . $value['item']; // Menggabungkan code dan item untuk keunikan
                }, $data)
            )
        );

        $distinctData = array_map(function($item){
            return ['key'=>$item,'is_parent'=>true,'children_total'=>0,'children'=>[]];
        },$uniqueData);

      

        // Ambil data yang sesuai dengan kombinasi code dan item yang unik
        $distict_data = array_ma
        foreach ($distinctData as $ds) {

            list($code, $item) = explode('|', $ds['key']); // Pisahkan code dan item
            foreach ($data as $key => $dataItem) {
                if ($dataItem['code'] == $code && $dataItem['item'] == $item) {
                    $distinctData[$key]['children'][]= $dataItem;
                    $distinctData[$key]['children_total']++;
                }
            }
        }

        return $distinctData;
    }




}
