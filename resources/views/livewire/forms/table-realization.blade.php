<div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sub Uraian</th>
                <th> Total</th>
                <th> Realisasi</th>
                <th> Bukti Bayar</th>
            </tr>
        </thead>
        <tbody>
            @php
                $all_total = 0;
            @endphp
            @forelse ($realizations as $index => $row)
                @forelse ($row['children'] as $index_child => $child)
                @php
                    $all_total+=$child['total'];
                @endphp
                <tr>
                    <td> <div class="">
                       <div class="d-flex justify-content-betweenc w-100">
                            <span class="fw-bold me-1">
                               ({{$row['code']}})
                            </span>
                            <span class="fw-bold">
                                 {{$row['item']}}
                            </span>
                        </div>
                        <div class="">
                        {{$child['sub_item']}}

                        </div>
                       <div class="d-flex justify-content-betweenc w-100">
                        <div class="d-flex flex-column w-50">
                            <span class="fw-bold">Vol:</span>
                            <span>
                                {{$child['volume']}} {{$child['unit']}}
                             </span>
                        </div>
                        <div class="d-flex flex-column w-50 ms-auto">
                            <span class="fw-bold">Harga Satuan :</span>
                            <span>
                                {{$child['cost_per_unit']?viewHelper::currencyFormat($child['cost_per_unit']):''}}
                             </span>
                        </div>

                        </div>

                    </div> </td>
                    <td>{{$child['total']?viewHelper::currencyFormat($child['total']):''}}</td>
                    <td>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            <span>{{ viewHelper::currencyFormat($this->realizations[$index]['children'][$index_child]['realization']??0) }}</span>
                        @else
                        <input type="number" wire:model='realizations.{{$index}}.children.{{$index_child}}.realization' class="form-control w-100" id="InputDate"
                        aria-label="Biaya Realisasi">
                        @endif
                    </td>
                    <td>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            file_id => {{$this->debug($child['id'])}}
                        @else
                            <input type="file" class="form-control w-100" wire:model='realizations.{{$index}}.children.{{$index_child}}.file_id'
                            aria-label="Gambar Nota">
                        @endif
                    </td>
                </tr>
                @empty
                @endforelse



            @empty
            @endforelse
        </tbody>
    </table>
    <button class="btn btn-primary mt-3" wire:click="save">Simpan File</button>



</div>
