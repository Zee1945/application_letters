<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\On;
use Livewire\Component;

class TableDraftCost extends Component
{

    public $draft_costs = [];
    public $current_code = '';
    public $sample =[];
    public function mount($draftCosts=[])
    {
        if (count($draftCosts) > 0) {
            $this->receiveDraftCost($draftCosts);
        }
    }
    public function render()
    {
        return view('livewire.forms.table-draft-cost');
    }

    #[On('transfer-draft-costs')]
    public function receiveDraftCost($draft_costs)
    {
        $this->sample = $draft_costs;
        // $this->draft_costs = $draft_costs;
        $this->draft_costs = $this->getDistinctDataByCodeAndItem($draft_costs);
    }

    public function debugger(){
        $data = $this->getDistinctDataByCodeAndItem( $this->sample);
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

        $raw_data = $data;
        // Membuat kombinasi unik berdasarkan code dan item
        $uniqueData = array_values(
            array_unique(
                array_map(function ($value) {
                    return $value['code'] . '|' . $value['item']; // Menggabungkan code dan item untuk keunikan
                }, $data)
            )
        );

        $distinctData = array_map(function($code_item) use ($raw_data){
            list($code, $item) = explode('|', $code_item);
            $new_item = ['key' => $item,'code'=>$code,'item'=>$item, 'is_parent' => true, 'children_total' => 0, 'children' => []];
            $new_item['children'] = array_filter($raw_data, function ($child) use ($code, $item, &$new_item) {
                if ($child['code'] == $code && $child['item'] == $item && !empty($child['sub_item'])) {
                    $new_item['children_total']++;
                    return true; // Pastikan elemen dimasukkan ke dalam array
                }
            });
            return $new_item;
        },$uniqueData);


        // Ambil data yang sesuai dengan kombinasi code dan item yang unik

        return $distinctData;
    }




}
