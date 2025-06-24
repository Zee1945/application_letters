<div>
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
            @php
                $all_total = 0;
            @endphp
            @forelse ($draft_costs as $index => $row)
                <tr>
                    <td rowspan="{{$row['children_total']+1}}"><span class="fw-bold">{{$row['code']}}</span></td>
                    <td rowspan="{{$row['children_total']+1}}"><span class="fw-bold">{{$row['item']}}</span></td>
                </tr>
                @forelse ($row['children'] as $child)
                @php
                    $all_total+=$child['total'];
                @endphp
                <tr>
                    <td>{{$child['sub_item']}}</td>
                    <td>{{$child['volume']}}</td>
                    <td>{{$child['unit']}}</td>
                    <td>{{$child['cost_per_unit']?viewHelper::currencyFormat($child['cost_per_unit']):''}}</td>
                    <td>{{$child['total']?viewHelper::currencyFormat($child['total']):''}}</td>
                </tr>
                @empty
                @endforelse



            @empty
            @endforelse
        </tbody>
    </table>
    {{-- <div class="row">
        <div class="col-sm-12">
            <div class="ms-auto">
                <table class="table-bordered">
                    @if (count($draft_costs)>0)
                    <tr>
                        <td colspan="2"></td>
                        <td><span class="fw-bold">Total</span></td>
                        <td><span>{{viewHelper::currencyFormat($all_total)}}</span></td>
                    </tr>
                     @endif
                </table>
            </div>
        </div>
    </div> --}}


</div>
