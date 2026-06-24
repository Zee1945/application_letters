<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Template Excel untuk pengisian rincian anggaran (draft cost).
 * Urutan kolom harus sama dengan yang dibaca DraftCostImport.
 */
class DraftCostTemplateExport implements FromView, WithEvents
{
    protected $draft_costs;

    public function __construct($draft_costs = [])
    {
        // Jika kosong, sediakan satu baris contoh agar user paham format pengisian.
        // Kolom mengikuti DraftCostImport: code, item, sub_item, volume, unit, cost_per_unit.
        $this->draft_costs = count($draft_costs) > 0 ? $draft_costs : [
            [
                'code' => '525112',
                'item' => 'Belanja Barang Operasional',
                'sub_item' => 'Honorarium Narasumber',
                'volume' => 4,
                'unit' => 'OJ',
                'cost_per_unit' => 1000000,
            ],
        ];
    }

    public function view(): View
    {
        return view('exports.draft-cost', [
            'draft_costs' => $this->draft_costs,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Auto size untuk semua kolom
                foreach (range('A', 'Z') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
