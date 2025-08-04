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
                               wire:change="syncRundown" wire:model.live="rundown.{{ $index }}.date" {!! $this->handleDisable !!}>
                        <div class="d-flex mt-3">
                            <input type="time" class="form-control"
                                   wire:change="syncRundown" wire:model.live="rundown.{{ $index }}.start_date" {!! $this->handleDisable !!}>
                            <h5 class="mx-2">-</h5>
                            <input type="time" class="form-control"
                                   wire:change="syncRundown" wire:model.live="rundown.{{ $index }}.end_date" {!! $this->handleDisable !!}>
                        </div>
                    </td>

                    <!-- Nama Acara -->
                    <td>
                        <textarea class="form-control"
                                  wire:change="syncRundown" wire:model.live="rundown.{{ $index }}.name" {!! $this->handleDisable !!}></textarea>
                    </td>

                <td>
                        <div>
                            <strong>Narasumber:</strong>
                            @foreach ($this->options['opt_speakers'] as $key => $speaker)
                                <div class="d-flex align-items-center mb-2">
                                    <input type="checkbox" 
                                           id="speaker_{{ $index }}_{{ $key }}"
                                           class="form-check-input"
                                           wire:click="toggleSpeaker({{ $index }}, '{{ $speaker['text'] }}')"
                                           {{ in_array($speaker['text'], $row['speaker_text'] ?? []) ? 'checked' : '' }} {!! $this->handleDisable !!}>
                                    <label for="speaker_{{ $index }}_{{ $key }}" class="form-check-label ms-2">
                                        {{ $speaker['text'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-2">
                            <strong>Moderator:</strong>
                            @foreach ($this->options['opt_moderators'] as $key => $moderator)
                                <div class="d-flex align-items-center mb-2">
                                    <input type="checkbox" 
                                           id="moderator_{{ $index }}_{{ $key }}"
                                           class="form-check-input"
                                           wire:click="toggleModerator({{ $index }}, '{{ $moderator['text'] }}')"
                                           {{ in_array($moderator['text'], $row['moderator_text'] ?? []) ? 'checked' : '' }} {!! $this->handleDisable !!}>
                                    <label for="moderator_{{ $index }}_{{ $key }}" class="form-check-label ms-2">
                                        {{ $moderator['text'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        {{-- @dump(isset($row['moderator_text'], $row['speaker_text']))
                        @if (
                            isset($row['moderator_text'], $row['speaker_text']) &&
                            is_array($row['moderator_text']) && is_array($row['speaker_text']) &&
                            count($row['moderator_text']) === 0 && count($row['speaker_text']) === 0
                        )
                        <div class="mt-2">
                            <strong>Input Kustom:</strong>
                                <div class="d-flex align-items-center mb-2">
                                    <textarea  class="form-control"></textarea>

                                </div>
                        </div>
                        @endif --}}

                        
                        <!-- Debug info untuk melihat array -->
                        {{-- <div class="mt-2">
                            <small class="text-muted">
                                Selected Speakers: {{ json_encode($row['speaker_text'] ?? []) }}<br>
                                Selected Moderators: {{ json_encode($row['moderator_text'] ?? []) }}
                            </small>
                        </div> --}}
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
            <button type="button" wire:click="debug" class="btn btn-primary mt-3 w-100">Debug</button>
        </div>
        <div class="col-12">
            <button type="button" wire:click="addRow" class="btn btn-primary mt-3 w-100">Tambah Baris</button>
        </div>
    </div>
</div>
