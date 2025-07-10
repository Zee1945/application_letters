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
                <th>Nama</th>
                <th>Jabatan - Lembaga</th>
                <th>Input CV</th>
                <th>Input KTP</th>
                <th>Input NPWP</th>
            </tr>
        </thead>
        <tbody id="table-body-speaker">

            @foreach ($speakers as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->institution }}</td>

                    <td>
                        <input type="file"
                               wire:model="rows.{{ $new_index }}.cv_file_id"
                               id="cv_file_id_{{ $index }}"
                               class="form-control"
                               accept=".pdf">
                    </td>

                    <td>
                        <input type="file"
                               wire:model="rows.{{ $new_index }}.idcard_file_id"
                               id="file_ktp_{{ $index }}"
                               class="form-control"
                               accept=".jpg,.jpeg,.png,.pdf">
                    </td>

                    <td>
                        <input type="file"
                               wire:model="rows.{{ $new_index }}.npwp_file_id"
                               id="file_npwp_{{ $index }}"
                               class="form-control"
                               accept=".jpg,.jpeg,.png,.pdf">
                    </td>
                </tr>
                @php
                    $new_index++;
                @endphp
            @endforeach
        </tbody>
    </table>

</div>
