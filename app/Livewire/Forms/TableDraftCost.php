<?php

namespace App\Livewire\Forms;

use App\Exports\DraftCostTemplateExport;
use App\Imports\DraftCostImport;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class TableDraftCost extends Component
{
    use WithFileUploads;

    /**
     * Struktur FLAT per baris: tiap baris satu rincian lengkap.
     * Disinkronkan dua arah dengan properti parent via wire:model (#[Modelable]),
     * sehingga tidak perlu dispatch event manual / saling lempar data.
     */
    #[Modelable]
    public $draft_costs = [];

    public $handleDisable = '';

    /** File excel untuk bulk insert */
    public $excel_file = null;

    /** Kontrol tampil/sembunyi modal import excel */
    public $show_import_modal = false;

    // Default 1 baris kosong
    public $blankRow = [
        'code' => '',
        'item' => '',
        'sub_item' => '',
        'volume' => '',
        'unit' => '',
        'cost_per_unit' => '',
        'total' => '',
    ];

    public function mount($handleDisable = '')
    {
        $this->handleDisable = $handleDisable;

        // Modelable mengisi $draft_costs dari parent secara otomatis.
        // Jika parent kosong, siapkan 4 baris kosong; baris pertama default code = "mak".
        if (count($this->draft_costs) < 1) {
            $firstRow = array_merge($this->blankRow, ['code' => 'MAK']);
            $this->draft_costs = [
                $firstRow,
                $this->blankRow,
                $this->blankRow,
                $this->blankRow,
            ];
        }
    }

    public function render()
    {
        return view('livewire.forms.table-draft-cost');
    }

    public function openImportModal()
    {
        $this->reset('excel_file');
        $this->resetErrorBag('excel_file');
        $this->show_import_modal = true;
    }

    public function closeImportModal()
    {
        $this->show_import_modal = false;
        $this->reset('excel_file');
        $this->resetErrorBag('excel_file');
    }

    public function addRow()
    {
        $this->draft_costs[] = $this->blankRow;
    }

    public function removeRow($index)
    {
        if (count($this->draft_costs) > 1) {
            array_splice($this->draft_costs, $index, 1);
        }
    }

    /**
     * Auto-hitung total tiap kali volume / cost_per_unit berubah.
     * Perubahan $draft_costs otomatis ter-sync ke parent via #[Modelable].
     */
    public function updatedDraftCosts($value, $key)
    {
        // $key contoh: "0.volume" atau "2.cost_per_unit"
        [$index, $field] = array_pad(explode('.', $key), 2, null);
        if (in_array($field, ['volume', 'cost_per_unit'])) {
            $volume = (float) ($this->draft_costs[$index]['volume'] ?? 0);
            $cost   = (float) ($this->draft_costs[$index]['cost_per_unit'] ?? 0);
            $this->draft_costs[$index]['total'] = $volume * $cost;
        }
    }

    /**
     * Bulk insert dari file Excel. Hasil parse DIGABUNG (merge) dengan baris yang
     * sudah ada — tidak menimpa. Baris kosong (mis. baris default) dibuang dulu
     * agar tidak menyisakan baris hampa di atas hasil import.
     * Perubahan $draft_costs otomatis ter-sync ke parent via #[Modelable].
     */
    public function importExcel()
    {
        $this->validate([
            'excel_file' => 'required|mimes:xlsx,xls',
        ], [
            'excel_file.required' => 'Silakan pilih file Excel terlebih dahulu.',
            'excel_file.mimes' => 'File harus berformat .xlsx atau .xls.',
        ]);

        $importer = new DraftCostImport();
        Excel::import($importer, $this->excel_file);

        $rows = $importer->rows;

        if (count($rows) > 0) {
            // Buang baris yang benar-benar kosong (mis. baris default) sebelum merge.
            $existing = array_filter($this->draft_costs, function ($row) {
                return !empty($row['item']) || !empty($row['sub_item']) || !empty($row['code']);
            });

            // Gabungkan: pertahankan data lama yang terisi, tambahkan hasil import.
            $this->draft_costs = array_values(array_merge($existing, $rows));
        }

        $this->reset('excel_file');
        $this->show_import_modal = false;
    }

    /**
     * Unduh template Excel kosong untuk diisi user.
     */
    public function downloadTemplate()
    {
        return Excel::download(new DraftCostTemplateExport(), 'template-rincian-anggaran.xlsx');
    }
}
