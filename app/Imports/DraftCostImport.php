<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Importer sederhana untuk rincian anggaran (draft cost).
 *
 * Format kolom yang diharapkan (baris pertama = header, dilewati):
 *   A: Kode | B: Uraian | C: Sub Uraian | D: Volume | E: Satuan | F: Harga Satuan
 */
class DraftCostImport implements ToCollection
{
    /** @var array hasil parse, struktur flat per baris */
    public $rows = [];

    public function collection(Collection $collection)
    {
        foreach ($collection as $i => $row) {
            // Lewati baris header
            if ($i === 0) {
                continue;
            }

            $code         = trim((string) ($row[0] ?? ''));
            $item         = trim((string) ($row[1] ?? ''));
            $sub_item     = trim((string) ($row[2] ?? ''));
            $volume       = $row[3] ?? null;
            $unit         = trim((string) ($row[4] ?? ''));
            $cost_per_unit = $row[5] ?? null;

            // Lewati baris kosong (minimal harus ada uraian & sub uraian)
            if ($item === '' && $sub_item === '') {
                continue;
            }

            $volume        = is_numeric($volume) ? (float) $volume : null;
            $cost_per_unit = is_numeric($cost_per_unit) ? (float) $cost_per_unit : null;
            $total = ($volume !== null && $cost_per_unit !== null) ? $volume * $cost_per_unit : null;

            $this->rows[] = [
                'code'          => $code !== '' ? $code : null,
                'item'          => $item !== '' ? $item : null,
                'sub_item'      => $sub_item !== '' ? $sub_item : null,
                'volume'        => $volume,
                'unit'          => $unit !== '' ? $unit : null,
                'cost_per_unit' => $cost_per_unit,
                'total'         => $total,
            ];
        }
    }
}
