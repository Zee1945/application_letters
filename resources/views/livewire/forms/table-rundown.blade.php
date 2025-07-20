<div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tanggal/Waktu</th>
                <th>Acara</th>
                <th>Narasumber & Moderator</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rundown as $index => $row)
                <tr>
                    <!-- Tanggal/Waktu -->
                    <td style="width: 200px">
                        <input type="date" class="form-control"
                               wire:change="syncRundown('rundown.{{ $index }}.date', $event.target.value)">
                        <div class="d-flex mt-3">
                            <input type="time" class="form-control"
                                   wire:change="syncRundown('rundown.{{ $index }}.start_date', $event.target.value)">
                            <h5 class="mx-2">-</h5>
                            <input type="time" class="form-control"
                                   wire:change="syncRundown('rundown.{{ $index }}.end_date', $event.target.value)">
                        </div>
                    </td>

                    <!-- Nama Acara -->
                    <td>
                        <textarea class="form-control"
                                  wire:change="syncRundown('rundown.{{ $index }}.name', $event.target.value)">
                        </textarea>
                    </td>

                    <!-- Narasumber & Moderator -->
                    <td>
                        <div>
                            <strong>Narasumber:</strong>
                            @foreach ($this->options['opt_speakers'] as $key => $speaker)
                                <div class="d-flex align-items-center mb-2">
                                    <input type="checkbox" id="speaker_{{ $index }}_{{ $key }}"
                                           class="form-check-input"
                                           value="{{ $speaker['text'] }}"
                                           wire:change="syncRundown('rundown.{{ $index }}.speaker_text', $event.target.value)"
                                           {{ in_array($speaker['text'], $row['speaker_text']) ? 'checked' : '' }}>
                                    <label for="speaker_{{ $index }}_{{ $key }}" class="form-check-label ms-2">{{ $speaker['text'] }}</label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-2">
                            <strong>Moderator:</strong>
                            @foreach ($this->options['opt_moderators'] as $key => $moderator)
                                <div class="d-flex align-items-center mb-2">
                                    <input id="moderator_{{ $index }}_{{ $key }}" type="checkbox"
                                           class="form-check-input"
                                           value="{{ $moderator['text'] }}"
                                           wire:change="syncRundown('rundown.{{ $index }}.moderator_text', $event.target.value)"
                                           {{ in_array($moderator['text'], $row['moderator_text']) ? 'checked' : '' }}>
                                    <label for="moderator_{{ $index }}_{{ $key }}" class="form-check-label ms-2">{{ $moderator['text'] }}</label>
                                </div>
                            @endforeach
                        </div>
                    </td>

                    <!-- Aksi untuk Menghapus Baris -->
                    <td>
                        <div class="d-flex align-items-baseline">
                            <button type="button" wire:click="removeRow({{ $index }})" class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-trash me-0"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="row">
        <div class="col-12">
            <button type="button" wire:click="addRow" class="btn btn-primary mt-3 w-100">Tambah Baris</button>
        </div>
        <div class="col-12">
            <button type="button" wire:click="debug" class="btn btn-primary mt-3 w-100">debug</button>
        </div>
    </div>
</div>
