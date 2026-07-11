<div>
    <table class="table table-bordered">
        <thead>
                <th width="30%">Sub Uraian</th>
                <th width="12%"> Rencana</th>
                <th width="12%"> Realisasi</th>
                <th width="25%"> Kuitansi & SPBY</th>
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
                @if (strtolower(trim($row['code'] ?? '')) === 'mak')
                <tr>
                    <td><span class="fw-bold"> ({{$row['code']}})</span>
                        {{ ($row['item'] ?? '') . (!empty($child['sub_item']) ? '. ' . $child['sub_item'] : '') }}
                    </td>
                    <td colspan="">
                    </td>
                    <td colspan="">
                    </td>
                    <td colspan="">
                    </td>
                </tr>
                @else
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
                                {{$child['volume_realization']}}
                                @else
                                <input type="number" class="form-control form-control-sm w-50" name="realizations[{{$child['id']}}][volume_realization]" value="{{$child['volume_realization']}}"> <span> &nbsp; {{$child['unit']}}</span>
                                <input type="hidden" name="realizations[{{$child['id']}}][draft_cost_id]" value="{{$child['id']}}">
                                @endif
                            </span>
                        </div>
                        <div class="d-flex flex-column w-50 ms-auto">
                            <span class="fw-bold">Harga Satuan :</span>
                            <span class="d-flex flex-column align-items-baseline">
                                 @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                                    {{viewHelper::currencyFormat($child['unit_cost_realization'])}}
                                @else
                                    <input type="number" class="form-control form-control-sm w-50 js-currency-input" name="realizations[{{$child['id']}}][unit_cost_realization]" value="{{$child['unit_cost_realization']}}">
                                    <small class="text-muted js-currency-preview">{{ !empty($child['unit_cost_realization']) ? viewHelper::currencyFormat($child['unit_cost_realization']) : '-' }}</small>
                                @endif
                            </span>
                        </div>

                        </div>

                    </div> </td>
                    <td class="align-bottom">
                        {{$child['total']?viewHelper::currencyFormat($child['total']):''}}</td>
                    <td class="align-bottom">
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')
                            <span class="d-flex text-nowrap">{{ viewHelper::currencyFormat($child['realization']??0) }}</span>
                        @else
                        <div class="d-flex flex-column align-items-baseline">
                           <input type="number" name="realizations[{{$child['id']}}][realization]" value="{{$child['realization']}}" class="form-control w-100 js-currency-input"
                            aria-label="Biaya Realisasi">
                            <small class="text-muted js-currency-preview">{{ !empty($child['realization']) ? viewHelper::currencyFormat($child['realization']) : '-' }}</small>
                        </div>

                        @endif
                    </td>
                    <td class="align-bottom">
                        @if(viewHelper::handleFieldDisabled($this->application,false,true) == 'disabled')

                            {{-- Untuk menampilkan gambar --}}


                            {{-- Untuk menampilkan PDF (embedded) --}}

                            {{-- Atau download link --}}
                        @if (!empty($child['files']) && count($child['files']) > 0)
                        <button class="btn btn-xs btn-outline-success" type="button" wire:click="openModalPreview({{$child['id']}})">Lihat</button>
                            @else
                            <small><i>File tidak diupload</i></small>
                        @endif

                        @else
                            {{-- Display existing files if any --}}
                            @if (!empty($child['files']) && count($child['files']) > 0)
                                <div class="mb-2">
                                    @foreach ($child['files'] as $file)
                                    <div class="row">
                                          {{-- <div class="d-flex w-100"> --}}
                                               <div class="d-flex align-items-center mb-1" style="max-width: 350px">
                                                        <div class="card d-flex flex-grow-1  card-hover" wire:click="openModalPreview({{$child['id']}})">
                                                                    <div class="card-body py-2 text-truncate">
                                                                        <span>
                                                                            <i class="fa-solid fa-paperclip"></i> {{$file['filename']}}
                                                                        </span>
                                                                    </div>
                                                                    <!-- Overlay untuk icon mata dan teks preview -->
                                                                    <div class="preview-overlay">
                                                                        <div class="bg-primary px-2 rounded">

                                                                        <i class="fa-solid fa-eye"></i>
                                                                        <span>Preview</span>
                                                                        </div>

                                                                    </div>
                                                                </div> 
                                            {{-- <button class="btn btn-xs btn-outline-success me-2" type="button" wire:click="openModalPreview({{$child['id']}})">
                                                <i class="fa-solid fa-eye"></i> {{$file['filename']}}
                                            </button> --}}
                                            <button class="btn btn-xs btn-outline-danger py-2 me-0" type="button" wire:click="deleteFile({{$file['id']}}, {{$child['id']}})">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    {{-- </div> --}}
                                    </div>
                                  
                                     
                                    @endforeach
                                </div>
                            @endif

                            {{-- Input for new file upload --}}
                            <input type="file" class="form-control w-100" name="realizations[{{$child['id']}}][file_bukti]" aria-label="Gambar Nota" accept=".jpg,.jpeg,.png,.pdf">
                        @endif
                    </td>
                </tr>
                @endif
                @empty
                @endforelse



            @empty
            @endforelse
        </tbody>
    </table>

    {{-- <button class="btn btn-md btn-primary" wire:click="debug">Debuggg</button> --}}
    {{-- <livewire:utils.modal-preview :modalId="'modalPreviewRealization'" :key="'realization-modal'"/> --}}

</div>

@script
<script>
    // Preview format rupiah reaktif tanpa wire:model (input pakai name/value HTML biasa).
    // Event delegation di document agar tetap jalan setelah Livewire me-render ulang DOM.
    (function () {
        const formatRupiah = (value) => {
            const number = parseInt(value, 10);
            if (isNaN(number) || value === '' || value === null) {
                return '-';
            }
            return 'Rp ' + number.toLocaleString('id-ID');
        };

        const updatePreview = (input) => {
            const wrapper = input.closest('.d-flex');
            if (!wrapper) return;
            const preview = wrapper.querySelector('.js-currency-preview');
            if (preview) {
                preview.textContent = formatRupiah(input.value);
            }
        };

        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('js-currency-input')) {
                updatePreview(e.target);
            }
        });
    })();
</script>
@endscript
