<div wire:init="loadRejectPage">
    <div class="loading-container-fullscreen" wire:loading wire:target="selectRejectAreaPosition, preSubmitInput, submitInput, updateOrder">
        <div class="loading-container">
            <div class="loading"></div>
        </div>
    </div>
    {{-- Production Input --}}
    <div class="production-input row row-gap-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-evenly gap-3" style="max-width:100%; overflow: auto;">
                        @foreach ($orderWsDetailSizes->groupBy("size") as $key => $order)
                            <div class="w-auto">
                                <div class="card w-100">
                                    <div class="card-header bg-reject text-white">
                                        <p class="fs-6 text-center mb-0">{{ $key }}</p>
                                    </div>
                                    <div class="card-body">
                                        <p class="fs-6 text-center mb-0">{{ $rejects->where('so_det_size', $key)->count() }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header align-items-center bg-reject text-light">
                    <p class="mb-0 fs-5">Defect List</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 align-self-center">
                            <div class="w-100 h-100" wire:loading wire:target='loadRejectPage'>
                                <div class="loading-container">
                                    <div class="loading"></div>
                                </div>
                            </div>
                            <div class="scroll-defect-area-img" wire:loading.remove wire:target='loadRejectPage'>
                                <div class="all-defect-area-img-container">
                                    @foreach ($allRejectPosition as $rejectPosition)
                                        <div class="all-defect-area-img-point" data-x="{{ floatval($rejectPosition->reject_area_x) }}" data-y="{{ floatval($rejectPosition->reject_area_y) }}"></div>
                                    @endforeach
                                    @if ($allRejectImage)
                                        <img src="http://10.10.5.62:8080/erp/pages/prod_new/upload_files/{{ $allRejectImage->gambar }}" class="all-defect-area-img" id="all-defect-area-img" alt="defect image">
                                    @else
                                        <img src="/assets/images/notfound.png" class="all-defect-area-img" alt="defect image">
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7 table-responsive">
                            <div class="d-flex align-items-center gap-3 my-3">
                                <button class="btn btn-reject fw-bold rounded-0 w-25 h-100" wire:click="$emit('preSubmitAllReject')">Reject all</button>
                                <input type="text" class="form-control rounded-0 w-75 h-100" wire:model='allRejectListFilter' placeholder="Search reject">
                            </div>
                            <table class="table table-bordered vertical-align-center">
                                <thead>
                                    <tr>
                                        <th>Tipe</th>
                                        <th>Area</th>
                                        <th>Dept.</th>
                                        <th>Total</th>
                                        <th class="d-none">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($allRejectList->count() < 1)
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div wire:loading>
                                                    <div class="loading-small"></div>
                                                </div>
                                                <div wire:loading.remove>
                                                    Defect tidak ditemukan
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($allRejectList as $rejectList)
                                            <tr>
                                                <td>{{ $rejectList->defect_type }}</td>
                                                <td>{{ $rejectList->defect_area }}</td>
                                                @php
                                                    $outputType = $rejectList->output_type;

                                                    if ($outputType == 'packing') {
                                                        $outputType = 'finishing';
                                                    } elseif ($outputType == 'qc_fns_pck_return') {
                                                        $outputType = 'QC FNS - PCK RETURN';
                                                    }
                                                @endphp
                                                <td>{{ strtoupper($outputType) }}</td>
                                                <td><b>{{ $rejectList->total }}</b></td>
                                                <td class="d-none">
                                                    <div wire:loading>
                                                        <div class="loading-small"></div>
                                                    </div>
                                                    <div wire:loading.remove>
                                                        <button class="btn btn-sm btn-reject fw-bold w-100"
                                                            wire:click="preSubmitMassReject('{{ $rejectList->reject_type_id }}', '{{ $rejectList->reject_area_id }}', '{{ $rejectList->defect_type }}', '{{ $rejectList->defect_area }}', '{{ $rejectList->output_type }}')"
                                                        >
                                                            Reject
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                            {{ $allRejectList->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-reject text-light">
                    <p class="mb-0 fs-5">Data Reject IN</p>
                    <div class="d-flex justify-content-end align-items-center gap-1">
                        {{-- <button type="button" class="btn btn-dark" wire:click="$emit('preSubmitUndo', 'defect')">
                            <i class="fa-regular fa-rotate-left"></i>
                        </button> --}}
                        {{-- <button type="button" class="btn btn-dark">
                            <i class="fa-regular fa-gear"></i>
                        </button> --}}
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <div class="d-flex justify-content-center align-items-center">
                        <input type="text" class="form-control mb-3 rounded-0" id="search-defect" name="search-defect" wire:model='searchRejectIn' placeholder="Search here...">
                    </div>
                    <table class="table table-bordered text-center align-middle">
                        <tr>
                            <th>No.</th>
                            <th>Dept.</th>
                            <th>Line</th>
                            <th>Waktu</th>
                            <th>ID</th>
                            <th>Size</th>
                            <th>Reject Type</th>
                            <th>Reject Area</th>
                            <th>Reject Area Image</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        @if ($rejectIn->count() < 1)
                            <tr>
                                <td colspan='11'>Reject tidak ditemukan</td>
                            </tr>
                        @else
                            @foreach ($rejectIn as $rejIn)
                                <tr>
                                    <td>{{ $rejectIn->firstItem() + $loop->index }}</td>
                                    @php
                                        $outputType = $rejIn->output_type;

                                        if ($outputType == 'packing') {
                                            $outputType = 'finishing';
                                        } elseif ($outputType == 'qc_fns_pck_return') {
                                            $outputType = 'QC FNS - PCK RETURN';
                                        }
                                    @endphp
                                    <td>{{ strtoupper($outputType) }}</td>
                                    <td>{{ strtoupper(str_replace("_", " ", $rejIn->sewing_line)) }}</td>
                                    <td>{{ $rejIn->updated_at }}</td>
                                    <td>{{ $rejIn->id }}</td>
                                    <td>{{ $rejIn->so_det_size }}</td>
                                    <td>{{ $rejIn->defect_type}}</td>
                                    <td>{{ $rejIn->defect_area }}</td>
                                    <td>
                                        <button type="button" class="btn btn-dark" wire:click="showDefectAreaImage('{{$allRejectImage->gambar}}', {{$rejIn->reject_area_x}}, {{$rejIn->reject_area_y}})'">
                                            <i class="fa-regular fa-image"></i>
                                        </button>
                                    </td>
                                    <td class="{{ $rejIn->reject_status == 'defect' ? 'text-defect' : 'text-reject'  }} fw-bold">{{ strtoupper($rejIn->reject_status) }}</td>
                                    <td>
                                        <div wire:loading>
                                            <div class="loading-small"></div>
                                        </div>
                                        <div wire:loading.remove>
                                            <button class="btn btn-sm btn-reject fw-bold w-100"
                                                wire:click="$emit('preSubmitReject', '{{ $rejIn->id }}', '{{ $rejIn->output_type }}', '{{ $rejIn->so_det_size }}', '{{ $rejIn->defect_type }}', '{{ $rejIn->defect_area }}', '{{ $allRejectImage->gambar }}', '{{ $rejIn->reject_area_x }}', '{{ $rejIn->reject_area_y }}')">
                                                REJECT
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </table>
                    {{ $rejectIn->links() }}
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center bg-reject text-light">
                    <p class="mb-0 fs-5">Data Reject</p>
                    <div class="d-flex justify-content-end align-items-center gap-1">
                        {{-- <button type="button" class="btn btn-dark" wire:click="$emit('preSubmitUndo', 'reject')">
                            <i class="fa-regular fa-rotate-left"></i>
                        </button> --}}
                        {{-- <button type="button" class="btn btn-dark">
                            <i class="fa-regular fa-gear"></i>
                        </button> --}}
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <div class="d-flex justify-content-center align-items-center">
                        <input type="text" class="form-control mb-3 rounded-0" id="search-reject" name="search-reject" wire:model='searchReject' placeholder="Search here...">
                    </div>
                    <table class="table table-bordered text-center align-middle">
                        <tr>
                            <th>No.</th>
                            <th>Dept</th>
                            <th>Line</th>
                            <th>Waktu</th>
                            <th>ID</th>
                            <th>PO</th>
                            <th>Size</th>
                            <th>Defect Type</th>
                            <th>Defect Area</th>
                            <th>Defect Area Image</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        @if ($rejects->count() < 1)
                            <tr>
                                <td colspan='12'>Reject tidak ditemukan</td>
                            </tr>
                        @else
                            @foreach ($rejects as $reject)
                                <tr>
                                    <td>{{ $rejects->firstItem() + $loop->index }}</td>
                                    @php
                                        $outputType = $reject->department;

                                        if ($outputType == 'packing') {
                                            $outputType = 'finishing';
                                        } elseif ($outputType == 'qc_fns_pck_return') {
                                            $outputType = 'QC FNS - PCK RETURN';
                                        }
                                    @endphp
                                    <td>{{ strtoupper($outputType) }}</td>
                                    <td>{{ strtoupper(str_replace("_", " ", $reject->created_by_line)) }}</td>
                                    <td>{{ $reject->updated_at }}</td>
                                    <td>{{ $reject->reject_id }}</td>
                                    <td>{{ $reject->po }}</td>
                                    <td>{{ $reject->so_det_size }}</td>
                                    <td>{{ $reject->defect_type ? $reject->defect_type : '-' }}</td>
                                    <td>{{ $reject->defect_area ? $reject->defect_area : '-' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-dark" wire:click="showDefectAreaImage('{{$allRejectImage->gambar}}', {{$reject->reject_area_x}}, {{$reject->reject_area_y}})'">
                                            <i class="fa-regular fa-image"></i>
                                        </button>
                                    </td>
                                    <td class="{{ $reject->status == 'defect' ? 'text-defect' : 'text-reject'  }} fw-bold">{{ strtoupper($reject->status) }}</td>
                                    <td>
                                        <div wire:loading>
                                            <div class="loading-small"></div>
                                        </div>
                                        <div wire:loading.remove>
                                            <button class="btn btn-sm btn-defect fw-bold w-100" wire:click="$emit('preCancelReject', '{{ $reject->id }}', '{{ $reject->so_det_size }}', '{{ $reject->defect_type }}', '{{ $reject->defect_area }}', '{{ $allRejectImage->gambar }}', {{$reject->reject_area_x}}, {{$reject->reject_area_y}}, '{{$reject->department}}')">CANCEL</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </table>
                    {{ $rejects->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" id="mass-reject-modal" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header bg-reject">
              <h5 class="modal-title text-light fw-bold">REJECT</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="hidden" name="mass-reject-department" id="mass-reject-department" wire:model="massRejectDepartment">
                    @error('massRejectDepartment')
                        <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                            <small>
                                <strong>Error</strong> {{$message}}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </small>
                        </div>
                    @enderror
                    <label class="form-label">Dept.</label>
                    <input type="text" class="form-control d-none @error('massRejectDepartment') is-invalid @enderror" wire:model="massRejectDepartment" disabled>
                    <input type="text" class="form-control @error('massRejectDepartment') is-invalid @enderror" value="{{ strtoupper($massRejectDepartment == "packing" ? "finishing" : $massRejectDepartment) }}" disabled>
                </div>
                <div class="mb-3">
                    <input type="hidden" name="mass-reject-type" id="mass-reject-type" wire:model="massRejectType">
                    @error('massRejectType')
                        <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                            <small>
                                <strong>Error</strong> {{$message}}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </small>
                        </div>
                    @enderror
                    <label class="form-label">Defect Type</label>
                    <input type="text" class="form-control @error('massRejectType') is-invalid @enderror" wire:model="massRejectTypeName" disabled>
                </div>
                <div class="mb-3">
                    <input type="hidden" name="mass-reject-area" id="mass-reject-area" wire:model=massRejectArea>
                    @error('massRejectArea')
                        <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                            <small>
                                <strong>Error</strong> {{$message}}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </small>
                        </div>
                    @enderror
                    <label class="form-label">Defect Area</label>
                    <input type="text" class="form-control @error('massRejectArea') is-invalid @enderror" wire:model="massRejectAreaName" disabled>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="mb-3">
                            @error('massQty')
                                <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                    <small>
                                        <strong>Error</strong> {{$message}}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </small>
                                </div>
                            @enderror
                            <label class="form-label">QTY</label>
                            <input type="number" class="form-control @error('massQty') is-invalid @enderror" name="mass-qty" id="mass-qty" value="1" wire:model="massQty">
                        </div>
                    </div>
                    <div class="col">
                        <div class="mb-3" x-data="{ sizeMass: $wire.entangle('massSize') }">
                            @error('massSize')
                                <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                    <small>
                                        <strong>Error</strong> {{$message}}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </small>
                                </div>
                            @enderror
                            <label class="form-label">Size</label>
                            <select class="form-select @error('massSize') is-invalid @enderror" name="mass-size" id="mass-size" x-model='sizeMass'>
                                <option value="" selected disabled>Select Size</option>
                                @foreach ($massSelectedReject as $reject)
                                    <option value="{{ $reject->so_det_id }}">{{ $reject->size }} ({{"qty : ".$reject->total}})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-reject" wire:click='submitMassReject()'>Reject</button>
                <button type="button" class="btn btn-no" data-dismiss="modal" wire:click="$emit('hideModal', 'massReject')">Batal</button>
            </div>
          </div>
        </div>
    </div>

    <div class="modal" tabindex="-1" id="all-reject-modal" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-body">
                    <h5>Reject semua defect?</h5>
                    <div class="d-flex justify-content-center align-items-center my-3">
                        <button type="button" class="btn btn-reject" wire:click='submitAllReject()'>Reject</button>
                        <button type="button" class="btn btn-no" data-dismiss="modal" wire:click="$emit('hideModal', 'allReject')">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal" tabindex="-1" id="reject-modal" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-reject text-light">
                    <h5 class="modal-title">REJECT</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        {{-- <div class="mb-3">
                            @error('productType')
                                <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                    <small>
                                        <strong>Error</strong> {{$message}}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </small>
                                </div>
                            @enderror
                            <div class="d-flex align-items-center mb-1">
                                <button type="button" class="btn btn-sm btn-light rounded-0 me-1" wire:click="$emit('showModal', 'addProductType')">
                                    <i class="fa-regular fa-plus fa-xs"></i>
                                </button>
                                <label class="form-label me-1 mb-0">Product Type</label>
                            </div>
                            <div wire:ignore id="select-product-type-container">
                                <select class="form-select @error('productType') is-invalid @enderror" id="product-type-select2" wire:model='productType'>
                                    <option value="" selected>Select product type</option>
                                    @foreach ($productTypes as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->product_type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> --}}
                        <div class="mb-3">
                            @error('defectType')
                                <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                    <small>
                                        <strong>Error</strong> {{$message}}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </small>
                                </div>
                            @enderror
                            <div class="d-flex align-items-center mb-1">
                                <label class="form-label me-1 mb-0">Reject Type</label>
                            </div>
                            <div wire:ignore id="select-reject-type-container">
                                <select class="form-select @error('rejectType') is-invalid @enderror" id="reject-type-select2" wire:model='rejectType'>
                                    <option value="" selected>Select defect type</option>
                                    @foreach ($defectTypes as $defect)
                                        <option value="{{ $defect->id }}">
                                            {{ $defect->defect_type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            @error('rejectArea')
                                <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                    <small>
                                        <strong>Error</strong> {{$message}}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </small>
                                </div>
                            @enderror
                            <div class="d-flex align-items-center mb-1">
                                <label class="form-label me-1 mb-0">Reject Area</label>
                            </div>
                            <div class="d-flex gap-1">
                                <div class="w-75" wire:ignore id="select-reject-area-container">
                                    <select class="form-select @error('rejectArea') is-invalid @enderror" id="reject-area-select2" wire:model='rejectArea'>
                                        <option value="" selected>Select defect area</option>
                                        @foreach ($defectAreas as $defect)
                                            <option value="{{ $defect->id }}">
                                                {{ $defect->defect_area }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-25">
                                    <button type="button" wire:click="selectRejectAreaPosition" class="btn btn-dark w-100">
                                        <i class="fa-regular fa-image"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            @if ($errors->has('rejectAreaPositionX') || $errors->has('rejectAreaPositionY'))
                                <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
                                    <small>
                                        <strong>Error</strong> Harap tentukan posisi reject area dengan mengklik tombol <button type="button"class="btn btn-dark btn-sm"><i class="fa-regular fa-image fa-2xs"></i></button> di samping 'select defect area'.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </small>
                                </div>
                            @endif
                            <div class="d-none">
                                <label class="form-label me-1 mb-2">Reject Area Position</label>
                                <div class="row">
                                    <div class="col d-flex justify-content-center align-items-center">
                                        <label class="form-label me-1 mb-0">X </label>
                                        <div class="d-flex">
                                            <input class="form-control @error('rejectAreaPositionX') is-invalid @enderror" id="reject-area-position-x-livewire" wire:model='rejectAreaPositionX' readonly>
                                        </div>
                                    </div>
                                    <div class="col d-flex justify-content-center align-items-center">
                                        <label class="form-label me-1 mb-1">Y </label>
                                        <div class="d-flex">
                                            <input class="form-control @error('rejectAreaPositionY') is-invalid @enderror" id="reject-area-position-y-livewire" wire:model='rejectAreaPositionY' readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" wire:click='submitInput'>Selesai</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="footer fixed-bottom py-3">
        <div class="container-fluid">
            <div class="d-flex justify-content-end">
                <button class="btn btn-dark btn-lg ms-auto fs-3" wire:click='preSubmitInput'>LANJUT</button>
            </div>
        </div>
    </footer>
</div>

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Defect Type
            $('#reject-type-select2').select2({
                theme: "bootstrap-5",
                width: $( this ).data( 'width' ) ? $( this ).data( 'width' ) : $( this ).hasClass( 'w-100' ) ? '100%' : 'style',
                placeholder: $( this ).data( 'placeholder' ),
                dropdownParent: $('#reject-modal .modal-content #select-reject-type-container')
            });

            $('#reject-type-select2').on('change', function (e) {
                var rejectType = $('#reject-type-select2').select2("val");
                @this.set('rejectType', rejectType);
            });

            // Defect Area
            $('#reject-area-select2').select2({
                theme: "bootstrap-5",
                width: $( this ).data( 'width' ) ? $( this ).data( 'width' ) : $( this ).hasClass( 'w-100' ) ? '100%' : 'style',
                placeholder: $( this ).data( 'placeholder' ),
                dropdownParent: $('#reject-modal .modal-content #select-reject-area-container')
            });

            $('#reject-area-select2').on('change', function (e) {
                var rejectArea = $('#reject-area-select2').select2("val");
                @this.set('rejectArea', rejectArea);
            });

            $(document).on('select2:open', () => {
                document.querySelector('.select2-search__field').focus();
            });

            Livewire.on('clearSelectRejectAreaPoint', () => {
                $('#reject-type-select2').val("").trigger('change');
                $('#reject-area-select2').val("").trigger('change');
            });

            document.getElementById('reject-input').addEventListener("keyup", async (event) => {
                if (event.key === 'Enter' || event.keyCode === 13) {
                    await @this.preSubmitInput();
                    let el = document.querySelector( ':focus' );
                    if( el ) el.blur();
                }
            });
        })
    </script>
@endpush

