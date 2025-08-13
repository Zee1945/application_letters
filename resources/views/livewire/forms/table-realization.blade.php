<div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sub Uraian</th>
                <th> Rencana</th>
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
                            <span class="d-flex align-items-baseline">
                                @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                                {{$this->realizations[$index]['children'][$index_child]['volume_realization']}}
                                @else
                                <input type="number" class="form-control form-control-sm w-50" id="numberInput" wire:change="syncRealization" wire:model.live='realizations.{{$index}}.children.{{$index_child}}.volume_realization'> <span> {{$child['unit']}}</span>
                                @endif
                            </span>
                        </div>
                        <div class="d-flex flex-column w-50 ms-auto">
                            <span class="fw-bold">Harga Satuan :</span>
                            <span class="d-flex align-items-baseline">
                                 @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                                    {{viewHelper::currencyFormat($this->realizations[$index]['children'][$index_child]['unit_cost_realization'])}}
                                @else
                                    <span>Rp.</span> <input type="number" class="form-control form-control-sm w-50" id="numberInput" wire:change="syncRealization" wire:model.live='realizations.{{$index}}.children.{{$index_child}}.unit_cost_realization'>
                                @endif
                            </span>
                        </div>

                        </div>

                    </div> </td>
                    <td>{{$child['total']?viewHelper::currencyFormat($child['total']):''}}</td>
                    <td>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            <span class="d-flex text-nowrap">{{ viewHelper::currencyFormat($this->realizations[$index]['children'][$index_child]['realization']??0) }}</span>
                        @else
                        <div class="d-flex align-items-baseline">
                           <span> Rp.</span> <input type="number" wire:change="syncRealization" wire:model.live='realizations.{{$index}}.children.{{$index_child}}.realization' class="form-control w-100" id="inputcurrency"
                            aria-label="Biaya Realisasi">
                        </div>

                        @endif
                    </td>
                    <td>
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')

                            {{-- Untuk menampilkan gambar --}}


                            {{-- Untuk menampilkan PDF (embedded) --}}


                            {{-- Atau download link --}}

                        <button class="btn btn-xs btn-outline-success" wire:click="openModalPreview({{$child['id']}})">Lihat</button>

                        @else
                            <input type="file" class="form-control w-100" wire:change="syncRealization" wire:model.live='realizations.{{$index}}.children.{{$index_child}}.file_id'
                            aria-label="Gambar Nota" accept=".jpg,.jpeg,.png,.pdf">
                        @endif
                    </td>
                </tr>
                @empty
                @endforelse



            @empty
            @endforelse
        </tbody>
    </table>

    <button class="btn btn-md btn-primary" wire:click="debug">Debuggg</button>
    {{-- <livewire:utils.modal-preview :modalId="'modalPreviewRealization'" :key="'realization-modal'"/> --}}

</div>
