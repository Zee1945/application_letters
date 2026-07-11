<div>
    {{-- Toolbar: tombol pembuka modal import excel --}}
    @if ($this->handleDisable != 'disabled')
        <div class="d-flex flex-wrap gap-2 w-100 justify-content-end align-items-center mb-3" style="margin-top: -55px">
            <button type="button" wire:click="openImportModal" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-file-excel me-1 text-success"></i> Import Excel
            </button>
        </div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="width: 110px;">Kode</th>
                <th>Uraian</th>
                <th>Sub Uraian</th>
                <th style="width: 90px;">Volume</th>
                <th style="width: 110px;">Satuan</th>
                <th style="width: 150px;">Harga Satuan</th>
                <th style="width: 150px;">Total</th>
                @if ($this->handleDisable != 'disabled')
                    <th style="width: 50px;"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php $all_total = 0; @endphp
            @forelse ($this->draft_costs as $index => $row)
                @php $all_total += (float) ($row['total'] ?? 0); @endphp
                @if ($index === 0 && strtolower($row['code']) === 'mak')
                       <tr>
                    <td>{{ $row['code'] ?? '' }}</td>
                    <td colspan="{{ $this->handleDisable != 'disabled' ? 7 : 6 }}">
                        {{ ($row['item'] ?? '') . (!empty($row['sub_item']) ? '. ' . $row['sub_item'] : '') }}
                    </td>
                    
                   
                </tr>
                @else
                <tr>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               wire:model.blur="draft_costs.{{ $index }}.code" {!! $this->handleDisable !!}>
                    </td>
                    <td>
                        <textarea class="form-control form-control-sm" rows="1"
                                  wire:model.blur="draft_costs.{{ $index }}.item" {!! $this->handleDisable !!}></textarea>
                    </td>
                    <td>
                        <textarea class="form-control form-control-sm" rows="1"
                                  wire:model.blur="draft_costs.{{ $index }}.sub_item" {!! $this->handleDisable !!}></textarea>
                    </td>
                    <td>
                        <input type="number" min="0" class="form-control form-control-sm"
                               wire:model.blur="draft_costs.{{ $index }}.volume" {!! $this->handleDisable !!}>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm"
                               wire:model.blur="draft_costs.{{ $index }}.unit" {!! $this->handleDisable !!}>
                    </td>
                    <td>
                        <input type="number" min="0" class="form-control form-control-sm"
                               wire:model.blur="draft_costs.{{ $index }}.cost_per_unit" {!! $this->handleDisable !!}>
                        <small class="text-muted"> {{ !empty($row['cost_per_unit']) ? viewHelper::currencyFormat($row['cost_per_unit']) : '-' }}</small>

                    </td>
                    <td>
                        <span>{{ !empty($row['total']) ? viewHelper::currencyFormat($row['total']) : '-' }}</span>
                    </td>
                    @if ($this->handleDisable != 'disabled')
                        <td class="text-center">
                            <button type="button" wire:click="removeRow({{ $index }})" class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-trash me-0"></i>
                            </button>
                        </td>
                    @endif
                </tr>
                @endif

            @empty
                <tr>
                    <td colspan="{{ $this->handleDisable != 'disabled' ? 8 : 7 }}" class="text-center text-muted">
                        Belum ada data anggaran.
                    </td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6" class="fw-bold text-end">Total</td>
                <td class="fw-bold">{{ viewHelper::currencyFormat($all_total) }}</td>
                @if ($this->handleDisable != 'disabled')
                    <td></td>
                @endif
            </tr>
        </tbody>
    </table>

    @if ($this->handleDisable != 'disabled')
        <div class="row">
            <div class="col-12">
                <button type="button" wire:click="addRow" class="btn btn-primary mt-2 w-100">Tambah Baris</button>
            </div>
        </div>
    @endif

    {{-- ===================== Modal Import Excel Rincian Anggaran ===================== --}}
    <div class="modal fade @if($show_import_modal) show d-block @endif" tabindex="-1"
         style="@if($show_import_modal) background: rgba(0,0,0,.5); @endif"
         aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light border-0">
                    <h1 class="modal-title fs-5 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-excel me-1 text-success"></i> Import Rincian Anggaran
                    </h1>
                    <button type="button" class="btn-close" wire:click="closeImportModal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-4">
                    {{-- Langkah 1: unduh template --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1">1. Unduh Template</label>
                        <p class="text-muted small mb-2">Gunakan template berikut agar format kolom sesuai.</p>
                        <button type="button" wire:click="downloadTemplate" class="btn btn-outline-success btn-sm">
                            <i class="fa-solid fa-file-arrow-down me-1"></i> Download Template Excel
                        </button>
                    </div>

                    {{-- Langkah 2: unggah & import --}}
                    <form wire:submit.prevent="importExcel">
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1">2. Unggah File</label>
                            <input type="file" class="form-control" wire:model="excel_file" accept=".xlsx,.xls">
                            @error('excel_file')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div wire:loading wire:target="excel_file" class="text-muted small mt-1">
                                <i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...
                            </div>
                        </div>

                        <div class="alert alert-info small d-flex align-items-start" role="alert">
                            <i class="fa-solid fa-circle-info me-2 mt-1"></i>
                            <div>Data dari Excel akan <strong>digabungkan</strong> dengan rincian anggaran yang sudah ada (tidak menimpa).</div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary px-4" wire:click="closeImportModal">
                                <i class="fa-solid fa-times me-1"></i> Batal
                            </button>
                            <button type="submit" class="btn btn-primary px-4"
                                    wire:loading.attr="disabled" wire:target="importExcel,excel_file"
                                    @if(empty($excel_file)) disabled @endif>
                                <span wire:loading.remove wire:target="importExcel">
                                    <i class="fa-solid fa-file-import me-1"></i> Import
                                </span>
                                <span wire:loading wire:target="importExcel">
                                    <span class="spinner-border spinner-border-sm me-2"></span> Memproses...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- ===================== End Modal Import Excel ===================== --}}
</div>
