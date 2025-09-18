<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

<table class="table table-striped mb-0">
    <thead class="table-light">
        <tr>
            <th>No</th>
            <th>Nama (Jabatan-Lembaga)</th>
            <th>Formulir</th>
        </tr>
    </thead>
    <tbody id="table-body-speaker">
        @foreach ($speakers as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                   <span class="fw-bold"> {{ $row->name }}</span> <br> <small class="fw-bold text-secondary"> ({{ $row->institution }})</small></td>
                <td>
                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">CV</label>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            @if (!empty($this->rows[$new_index]['cv_file_id']))
                                <button class="btn btn-xs btn-outline-success" wire:click="openModalPreview({{$this->rows[$new_index]['cv_file_id']}})">Lihat</button>
                            @else
                                <small><i>File tidak diupload</i></small>
                            @endif
                        @else
                            <input type="file"
                                wire:model="rows.{{ $new_index }}.cv_file_id"
                                id="cv_file_id_{{ $index }}"
                                class="form-control"
                                accept=".pdf">
                        @endif
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">KTP</label>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            @if (!empty($this->rows[$new_index]['idcard_file_id']))
                                <button class="btn btn-xs btn-outline-success" wire:click="openModalPreview({{$this->rows[$new_index]['idcard_file_id']}})">Lihat</button>
                            @else
                                <small><i>File tidak diupload</i></small>
                            @endif
                        @else
                            <input type="file"
                                wire:model="rows.{{ $new_index }}.idcard_file_id"
                                id="file_ktp_{{ $index }}"
                                class="form-control"
                                accept=".jpg,.jpeg,.png">
                        @endif
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">NPWP</label>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            @if (!empty($this->rows[$new_index]['npwp_file_id']))
                                <button class="btn btn-xs btn-outline-success" wire:click="openModalPreview({{$this->rows[$new_index]['npwp_file_id']}})">Lihat</button>
                            @else
                                <small><i>File tidak diupload</i></small>
                            @endif
                        @else
                            <input type="file"
                                wire:model="rows.{{ $new_index }}.npwp_file_id"
                                id="file_npwp_{{ $index }}"
                                class="form-control"
                                accept=".jpg,.jpeg,.png">
                        @endif
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold mb-1">Materi</label>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            @if (!empty($this->rows[$new_index]['material_file_id']))
                                <button class="btn btn-xs btn-outline-success" wire:click="openModalPreview({{$this->rows[$new_index]['material_file_id']}})">Lihat</button>
                            @else
                                <small><i>File tidak diupload</i></small>
                            @endif
                        @else
                            <input type="file"
                                wire:model="rows.{{ $new_index }}.material_file_id"
                                id="file_material_{{ $index }}"
                                class="form-control"
                                accept=".pdf">
                        @endif
                    </div>
                </td>
           
            </tr>
            @php
                $new_index++;
            @endphp
        @endforeach
    </tbody>
</table>

</div>
