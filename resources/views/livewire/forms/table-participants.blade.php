<div>
    <table class="table mb-0">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama</th>
                @if ($participantType != 'commitee')
                    <th>Jabatan - Lembaga</th>
                @endif
                @if ($participantType != 'participant')
                    <th>Peran</th>
                @endif
                <th></th>
                <th>
                    <button wire:click="addRow" class="btn btn-sm btn-primary mt-3">+</button>
                    <button wire:click="saveData" class="btn btn-sm btn-primary mt-3">Tes</button>
                </th>
            </tr>
        </thead>
        <tbody id="table-body-{{$participantType}}">
            @foreach ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><input type="text" wire:model="rows.{{ $index }}.name" class="form-control" placeholder="Nama"></td>

                    @if ($participantType != 'commitee')
                        <td><input type="text" wire:model="rows.{{ $index }}.institution" class="form-control" placeholder="Jabatan - Lembaga"></td>
                    @else
                        <input type="hidden" wire:model="rows.{{ $index }}.institution" value="">
                    @endif

                    <td>
                        @if ($participantType != 'participant')
                            @if ($participantType == 'commitee')
                                <select wire:model="rows.{{ $index }}.commitee_position_id" class="form-select">
                                    @foreach ($commiteePositions as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <select wire:model="rows.{{ $index }}.participant_type_id" class="form-select">
                                    @foreach ($get_participant_type as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        @else
                        <input type="hidden"
                        wire:model="rows.{{ $index }}.commitee_position_id"
                        value="">

                 <input type="hidden"
                        wire:model="rows.{{ $index }}.participant_type_id"
                        value="{{ $get_participant_type ? $get_participant_type->id : '' }}">
                        @endif
                    </td>

                    <td>
                        <button wire:click="deleteRow({{ $index }})" class="btn btn-danger btn-sm">
                            <i class='bx bx-trash'></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
