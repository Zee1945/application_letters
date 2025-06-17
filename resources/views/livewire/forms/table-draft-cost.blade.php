<div>

    <button class="btn btn-danger" wire:click="debugger">tess</button>
    <table class="table table-bordered table-striped">
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
            @forelse ($draft_costs as $index => $row)
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
            @endforelse
        </tbody>
    </table>
</div>
