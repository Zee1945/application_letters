<div>
    <table class="table table-striped mb-0">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Nip</th>
                <th>Pangkat (Golongan)</th>
                <th>Jabatan Fungsional</th>
                @if ($participantType != 'commitee')
                    <th>Jabatan - Lembaga</th>
                @endif
                @if ($participantType != 'participant')
                    <th>Peran</th>
                @endif
            </tr>
        </thead>
        <tbody id="table-body-{{$participantType}}">
            @foreach ($filteredParticipants as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{$row['name']}}</td>
                    <td>{{$row['nip']}}</td>
                    <td>{{$row['rank']}}</td>
                    <td>{{$row['functional_position']}}</td>

                    @if ($participantType != 'commitee')
                        <td>{{$row['institution']}}</td>
                    @endif

                    <td>
                        @if ($participantType != 'participant')
                            @if ($participantType == 'commitee')
                                {{-- <select wire:model="rows.{{ $index }}.commitee_position_id" class="form-select">
                                    @foreach ($commiteePositions as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select> --}}
                                <span class="text-capitalize">{{$this->findName('commitee',$row['commitee_position_id'])}}</span>
                            @else
                                <span class="text-capitalize">{{$this->findName('participant',$row['participant_type_id'])}}</span>

                            @endif
                        @endif
                    </td>


                </tr>
            @endforeach
        </tbody>
    </table>
</div>
