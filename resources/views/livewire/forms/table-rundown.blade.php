<div>
    <button wire:click="debugger" class="btn btn-danger">tess</button>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Acara</th>
                <th>Narasumber</th>
                <th>Moderator</th>
                <th>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rundown as $index => $row)
                <tr>
                    <td><input type="date" class="form-control" wire:model="rundown.{{ $index }}.date"></td>
                    <td><input type="time" class="form-control" wire:model="rundown.{{ $index }}.time"></td>
                    <td><input type="text" class="form-control" wire:model="rundown.{{ $index }}.event"></td>

                    <td>
                        <!-- Dropdown Narasumber Dinamis -->
                        @foreach ($row['speakers'] as $subIndex => $speaker)
                            <div class="input-group mb-2">
                                <select class="form-select" wire:model="rundown.{{ $index }}.speakers.{{ $subIndex }}">
                                    <option value="">Pilih Narasumber</option>
                                    @foreach ($get_speakers as $user)
                                        <option value="{{ $user['name']. '-'. $user['institution']  }}">{{ $user['name']. '-'. $user['institution']  }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-danger" wire:click="removeSpeaker({{ $index }}, {{ $subIndex }})"><i class="fa-solid fa-user-minus"></i></button>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-success btn-xs" wire:click="addSpeaker({{ $index }})"><i class="fa-solid fa-user-plus"></i></button>
                        </div>
                    </td>

                    <td>
                        <!-- Dropdown Moderator Dinamis -->
                        @foreach ($row['moderators'] as $subIndex => $moderator)
                            <div class="input-group mb-2">
                                <select class="form-select" wire:model="rundown.{{ $index }}.moderators.{{ $subIndex }}">
                                    <option value="">Pilih Moderator</option>
                                    @foreach ($get_moderators as $user)
                                        <option value="{{ $user['name']. '-'. $user['institution']  }}">{{ $user['name']. '-'. $user['institution']  }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-danger" wire:click="removeModerator({{ $index }}, {{ $subIndex }})"><i class="fa-solid fa-user-minus"></i></button>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-success btn-xs" wire:click="addModerator({{ $index }})"><i class="fa-solid fa-user-plus"></i></button>
                        </div>
                    </td>

                    <td>
                        <button type="button" class="btn btn-danger btn-sm" wire:click="removeRow({{ $index }})"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6">
                    <div class="d-flex w-100 justify-content-center">
                        <button type="button" class="btn btn-success btn-sm" wire:click="addRow"><i class="fa-solid fa-plus"></i> Tambah Baris Baru</button>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

</div>
