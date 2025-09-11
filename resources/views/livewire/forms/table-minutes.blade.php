<div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr class="text-center">
                <th>No</th>
                <th>Topik Pembahasan</th>
                <th>Penjelasan</th>
                <th>Tindak Lanjut</th>
                <th>Batas Waktu</th>
                <th>PJ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($minutes as $index => $row)
                <tr>
                    <!-- Tanggal/Waktu -->
                    <td class="text-center">
                        {{$index+1}}
                    </td>
                    <td>
                           <textarea class="form-control"
                                  wire:change="syncMinutes" wire:model.live="minutes.{{ $index }}.topic" ></textarea>
                    </td>

                    <!-- Nama Acara -->
                    <td>
                        <textarea class="form-control"
                                  wire:change="syncMinutes" wire:model.live="minutes.{{ $index }}.explanation" ></textarea>
                    </td>
                    <td>
                        <textarea class="form-control"
                                  wire:change="syncMinutes" wire:model.live="minutes.{{ $index }}.follow_up" ></textarea>
                    </td>
                    <td>
                          <input type="date" class="form-control"
                               wire:change="syncMinutes" wire:model.live="minutes.{{ $index }}.deadline">
                    </td>
                    <td>
                        <textarea class="form-control"
                                  wire:change="syncMinutes" wire:model.live="minutes.{{ $index }}.assignee" ></textarea>
                    </td>
                    <!-- Aksi -->
                    <td>
                        <button type="button" wire:click="removeRow({{ $index }})" class="btn btn-danger btn-sm">
                            <i class="fa-solid fa-trash me-0"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="row">
        <div class="col-12">
            <button type="button" wire:click="debugger" class="btn btn-primary mt-3 w-100">Debug</button>
        </div>
        <div class="col-12">
            <button type="button" wire:click="addRow" class="btn btn-primary mt-3 w-100">Tambah Baris</button>
        </div>
    </div>
</div>
