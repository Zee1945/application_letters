<div>

    <button class="btn btn-danger" wire:click="debugger">tess</button>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Uraian</th>
                <th>Sub Uraian</th>
                <th>Volume</th>
                <th>Satuan</th>
                <th>Harga Satuan</th>
                <th> Total</th>
            </tr>
        </thead>
        <tbody>
            {{-- @forelse ($draft_costs as $index => $row)
                <tr>
                    <td>{{$row['code']}}</td>
                    <td>{{$row['item']}}</td>
                    <td>{{$row['sub_item']}}</td>
                    <td>{{$row['volume']}}</td>
                    <td>{{$row['unit']}}</td>
                    <td>{{$row['cost_per_unit']}}</td>
                    <td>{{$row['total']}}</td>
                </tr>
            @empty
            @endforelse --}}

            @forelse ($draft_costs as $index => $row)
                <tr>
                    <td rowspan="{{$row['children_total']+1}}"><span class="fw-bold">{{$row['code']}}</span></td>
                    <td rowspan="{{$row['children_total']+1}}"><span class="fw-bold">{{$row['item']}}</span></td>
                </tr>
                @forelse ($row['children'] as $child)
                <tr>

                    <td>{{$child['sub_item']}}</td>
                    <td>{{$child['volume']}}</td>
                    <td>{{$child['unit']}}</td>
                    <td>{{$child['cost_per_unit']}}</td>
                    <td>{{$child['total']}}</td>
                </tr>
                @empty
                @endforelse



            @empty
            @endforelse

        </tbody>
    </table>
</div>
