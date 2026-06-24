<div>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tanggal/Waktu</th>
                <th>Acara</th>
                <th>Petugas</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rundown as $index => $row)
                <tr>
                    <!-- Tanggal/Waktu -->
                    <td style="width: 200px">
                        <select class="form-select"
                                wire:change="syncRundown" wire:model.live="rundown.{{ $index }}.date" {!! $this->handleDisable !!}>
                            @forelse ($this->dateOptionsFor($row['date'] ?? '') as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @empty
                                <option value="">— Isi Tanggal Pelaksanaan dahulu —</option>
                            @endforelse
                        </select>
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
                        @foreach ($this->options_2 as $key => $opt)
                        <div class="mt-2">
                            <strong>{{ $opt['label']}}:</strong>
                            @foreach ($opt['officers'] as $k => $ofc)
                                <div class="d-flex align-items-center mb-2">
                                    <input type="checkbox" 
                                           id="{{ $opt['slug'] }}_{{ $k }}"
                                           class="form-check-input"
                                           wire:click="toggleOfficer({{ $index }}, `{{ $ofc }}`)"
                                           {{ in_array($ofc, $row['officer_text'] ?? []) ? 'checked' : '' }} {!! $this->handleDisable !!}>
                                    <label for="moderator_{{ $index }}_{{ $key }}" class="form-check-label ms-2">
                                       
                                        {{ $this->_formatStripe($ofc) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @endforeach



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
                        @if ($this->handleDisable != 'disabled')
                            <button type="button" wire:click="removeRow({{ $index }})" class="btn btn-danger btn-sm">
                            <i class="fa-solid fa-trash me-0"></i>
                        </button>
                        @endif
                        
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    @if ($this->handleDisable != 'disabled')
    <div class="row">
        <div class="col-12">
            <button type="button" wire:click="addRow" class="btn btn-primary mt-3 w-100">Tambah Baris</button>
        </div>
    </div>
    @endif
</div>
