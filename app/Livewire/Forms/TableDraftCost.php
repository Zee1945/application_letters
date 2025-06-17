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
        // $data = $this->getDistinctDataByCodeAndItem();
        // dd($data);
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

        // Ambil data yang sesuai dengan kombinasi code dan item yang unik
        $distinctData = [];
        foreach ($uniqueData as $uniqueItem) {
            list($code, $item) = explode('|', $uniqueItem); // Pisahkan code dan item
            foreach ($data as $dataItem) {
                if ($dataItem['code'] == $code && $dataItem['item'] == $item) {
                    $distinctData[] = $dataItem;
                    break;  // Hentikan setelah menemukan data pertama yang cocok
                }
            }
        }

        return $distinctData;
    }




}
