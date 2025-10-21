<?php

namespace App\Http\Livewire;

use App\Models\SignalBit\OutputGudangStok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Session\SessionManager;
use App\Models\SignalBit\DefectType;
use App\Models\SignalBit\DefectArea;
use App\Models\SignalBit\Defect;
use App\Models\SignalBit\Rft;
use App\Models\SignalBit\Reject as RejectModel;
use App\Models\SignalBit\MasterPlan;
use Carbon\Carbon;
use DB;

class Reject extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $orderInfo;
    public $orderWsDetailSizes;
    public $selectedPo;
    public $output;
    public $outputInput;
    public $sizeInput;
    public $sizeInputText;

    public $searchRejectIn;
    public $searchReject;
    public $rejectImage;
    public $rejectPositionX;
    public $rejectPositionY;
    public $allRejectListFilter;
    public $allRejectImage;
    public $allRejectPosition;
    public $massQty;
    public $massSize;
    public $massRejectType;
    public $massRejectTypeName;
    public $massRejectArea;
    public $massRejectAreaName;
    public $massSelectedReject;
    public $massRejectDepartment;
    public $info;

    public $defectTypes;
    public $defectAreas;
    public $rejectType;
    public $rejectArea;
    public $rejectAreaPositionX;
    public $rejectAreaPositionY;

    protected $rules = [
        'outputInput' => 'required|numeric|min:1',
        'sizeInput' => 'required',

        // 'rejectType' => 'required',
        // 'rejectArea' => 'required',
        // 'rejectAreaPositionX' => 'required',
        // 'rejectAreaPositionY' => 'required',
    ];

    protected $messages = [
        'outputInput.required' => 'Harap tentukan kuantitas output.',
        'outputInput.numeric' => 'Harap isi kuantitas output dengan angka.',
        'outputInput.min' => 'Kuantitas output tidak bisa kurang dari 1.',
        'sizeInput.required' => 'Harap tentukan ukuran output.',

        // 'rejectType.required' => 'Harap tentukan jenis reject.',
        // 'rejectArea.required' => 'Harap tentukan area reject.',
        // 'rejectAreaPositionX.required' => "Harap tentukan posisi reject area dengan mengklik tombol 'gambar' di samping 'select product type'.",
        // 'rejectAreaPositionY.required' => "Harap tentukan posisi reject area dengan mengklik tombol 'gambar' di samping 'select product type'.",
    ];

    protected $listeners = [
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'updateOutputReject' => 'updateOutput',

        'submitReject' => 'submitReject',
        'submitAllReject' => 'submitAllReject',
        'cancelReject' => 'cancelReject',
        'hideDefectAreaImageClear' => 'hideDefectAreaImage',

        'setRejectAreaPosition' => 'setRejectAreaPosition',
        'clearInput' => 'clearInput',
        'updatePo' => 'updatePo',
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->outputInput = 1;
        $this->sizeInput = null;
        $this->sizeInputText = null;

        $this->rejectType = null;
        $this->rejectArea = null;
        $this->rejectAreaPositionX = null;
        $this->rejectAreaPositionY = null;
    }

    public function loadRejectPage()
    {
        $this->emit('loadRejectPageJs');
    }

    public function updateWsDetailSizes()
    {
        $this->outputInput = 1;
        $this->sizeInput = null;
        $this->sizeInputText = '';

        $this->orderInfo = session()->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = session()->get('orderWsDetailSizes', $this->orderWsDetailSizes);
    }

    public function updatePo($po)
    {
        $this->selectedPo = $po;
    }

    public function updateOutput()
    {
        $this->output = collect(DB::select("select output_rfts_packing_po.*, so_det.size, COUNT(output_rfts_packing_po.id) output from `output_rfts_packing_po` left join `so_det` on `so_det`.`id` = `output_rfts_packing_po`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `type` = 'reject' group by so_det.id"));
    }

    public function clearInput()
    {
        $this->outputInput = 1;
        $this->sizeInput = null;
        $this->sizeInputText = '';
    }

    public function outputIncrement()
    {
        $this->outputInput++;
    }

    public function outputDecrement()
    {
        if (($this->outputInput-1) < 1) {
            $this->emit('alert', 'warning', "Kuantitas output tidak bisa kurang dari 1.");
        } else {
            $this->outputInput--;
        }
    }

    public function setSizeInput($size, $sizeText)
    {
        $this->sizeInput = $size;
        $this->sizeInputText = $sizeText;
    }

    public function selectRejectAreaPosition()
    {
        $masterPlan = MasterPlan::select('gambar')->find($this->orderInfo->id);

        if ($masterPlan) {
            $this->emit('showSelectRejectArea', $masterPlan->gambar);
        } else {
            $this->emit('alert', 'error', 'Harap pilih tipe produk terlebih dahulu');
        }
    }

    public function setRejectAreaPosition($x, $y)
    {
        $this->rejectAreaPositionX = $x;
        $this->rejectAreaPositionY = $y;
    }

    // public function preSubmitInput()
    // {
    //     $this->emit('clearSelectRejectAreaPoint');

    //     $this->rejectType = null;
    //     $this->rejectArea = null;
    //     $this->rejectAreaPositionX = null;
    //     $this->rejectAreaPositionY = null;

    //     $this->validateOnly('outputInput');
    //     $this->validateOnly('sizeInput');

    //     $this->emit('showModal', 'reject');
    // }

    // public function submitInput(SessionManager $session)
    // {
    //     $validatedData = $this->validate();

    //     $insertData = [];
    //     for ($i = 0; $i < $this->outputInput; $i++)
    //     {
    //         array_push($insertData, [
    //             'master_plan_id' => $this->orderInfo->id,
    //             'so_det_id' => $this->sizeInput,
    //             'status' => 'NORMAL',
    //             'reject_type_id' => $this->rejectType,
    //             'reject_area_id' => $this->rejectArea,
    //             'reject_area_x' => $this->rejectAreaPositionX,
    //             'reject_area_y' => $this->rejectAreaPositionY,
    //             'reject_status' => 'mati',
    //             'created_by' => Auth::user()->line_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now()
    //         ]);
    //     }

    //     $insertReject = RejectModel::insert($insertData);

    //     if ($insertReject) {
    //         $type = DefectType::select('defect_type')->find($this->rejectType);
    //         $area = DefectArea::select('defect_area')->find($this->rejectArea);
    //         $getSize = DB::table('so_det')
    //             ->select('id', 'size')
    //             ->where('id', $this->sizeInput)
    //             ->first();

    //         $this->emit('alert', 'success', $this->outputInput." REJECT output berukuran ".$getSize->size." dengan jenis : ".$type->defect_type." dan area : ".$area->defect_area." berhasil terekam.");
    //         $this->emit('hideModal', 'reject');

    //         $this->outputInput = 1;
    //         $this->sizeInput = '';
    //     } else {
    //         $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
    //     }
    // }

    public function closeInfo()
    {
        $this->info = false;
    }

    public function setDefectAreaPosition($x, $y)
    {
        $this->defectPositionX = $x;
        $this->defectPositionY = $y;
    }

    public function showDefectAreaImage($defectImage, $x, $y)
    {
        $this->defectImage = $defectImage;
        $this->defectPositionX = $x;
        $this->defectPositionY = $y;

        $this->emit('showDefectAreaImage', $this->defectImage, $this->defectPositionX, $this->defectPositionY);
    }

    public function hideDefectAreaImage()
    {
        $this->defectImage = null;
        $this->defectPositionX = null;
        $this->defectPositionY = null;
    }

    public function updatingSearchRejectIn()
    {
        $this->resetPage('rejectInPage');
    }

    public function updatingSearchReject()
    {
        $this->resetPage('rejectsPage');
    }

    public function submitAllReject() {
        $availableReject = 0;

        // Get All Rejects
        $rejectQcList = DB::table('output_rejects')->selectRaw('"qc" as output_type, master_plan.id_ws, output_rejects.id, output_rejects.reject_status, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area')->
            leftJoin("so_det", "so_det.id", "=", "output_rejects.so_det_id")->
            leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)-> 
            groupBy('output_rejects.id');
        $rejectPackingList = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, master_plan.id_ws, output_rejects_packing.id, output_rejects_packing.reject_status, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area')->
            leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)-> 
            groupBy('output_rejects_packing.id');
        $allRejectList = $rejectQcList->union($rejectPackingList)->get();

        if ($allRejectList->count() > 0) {
            $insertRejectArr = [];
            $batch = Str::uuid();
            foreach ($allRejectList as $reject) {
                $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                    ppic_master_so.id,
                    ppic_master_so.po,
                    ppic_master_so.id_so_det,
                    so_det.id as so_det_id,
                    so_det.size,
                    so_det.dest,
                    ppic_master_so.qty_po,
                    COUNT(output_rfts_packing_po.id) as qty_output
                ")
                ->leftJoin('signalbit_erp.output_rfts_packing_po', 'output_rfts_packing_po.po_id', '=', 'ppic_master_so.id')
                ->leftJoin('signalbit_erp.so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
                ->leftJoin('signalbit_erp.so', 'so.id', '=', 'so_det.id_so')
                ->leftJoin('signalbit_erp.act_costing', 'act_costing.id', '=', 'so.id_cost')
                ->leftJoin('signalbit_erp.mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
                ->leftJoin('signalbit_erp.master_size_new', 'master_size_new.size', '=', 'so_det.size')
                ->leftJoin('signalbit_erp.masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
                ->where('so_det.cancel', '!=', 'Y')
                ->where('ppic_master_so.po', $this->selectedPo)
                ->where('so_det.color', $reject->color)
                ->where('so_det.size', $reject->size)
                ->groupBy('ppic_master_so.id')
                ->first();

                // create reject
                array_push($insertRejectArr,[
                    'master_plan_id' => $this->orderInfo->id,
                    'so_det_id' => $currentPo ? $currentPo->so_det_id : $reject->so_det_id,
                    'po_id' => $currentPo ? $currentPo->id : NULL,
                    'status' => $reject->reject_status,
                    'alokasi' => $currentPo ? "po" : "gudang stok",
                    'reject_id' => $reject ? $reject->id : NULL,
                    'type' => 'reject',
                    'department' => $reject->output_type,
                    'created_by' => Auth::user()->id,
                    'created_by_username' => Auth::user()->username,
                    'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $availableReject += 1;
            }

            $insertReject = Rft::insert($insertRejectArr);

            if ($insertReject) {
                $currentReject = Rft::where("batch", $batch)->get();

                $insertDataGudangStok = [];
                foreach ($currentReject as $reject) {
                    array_push($insertDataGudangStok, [
                        'so_det_id' => $reject->so_det_id,
                        'packing_po_id' => $reject->id,
                        'type' => 'reject',
                        'created_by' => Auth::user()->id,
                        'created_by_username' => Auth::user()->username,
                        'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }

                OutputGudangStok::insert($insertDataGudangStok);
            }

            if ($availableReject > 0) {
                $this->emit('alert', 'success', $availableReject." REJECT berhasil masuk.");

                $this->emit('getPoSizeQty');
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. DEFECT tidak berhasil masuk.");
            }
        } else {
            $this->emit('alert', 'warning', "Data tidak ditemukan.");
        }
    }

    public function preSubmitMassReject($defectType, $defectArea, $defectTypeName, $defectAreaName, $department) {
        $this->massQty = 1;
        $this->massSize = '';
        $this->massRejectType = $defectType;
        $this->massRejectTypeName = $defectTypeName;
        $this->massRejectArea = $defectArea;
        $this->massRejectAreaName = $defectAreaName;
        $this->massRejectDepartment = $department;

        $this->emit('showModal', 'massReject');
    }

    public function submitMassReject() {
        $availableReject = 0;

        // Get Selected Reject
        if ($this->massRejectDepartment == "qc") {
            $rejectList = DB::table('output_rejects')->selectRaw('"qc" as output_type, output_rejects.id, output_rejects.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin("so_det", "so_det.id", "=", "output_rejects.so_det_id")->
                leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects.reject_type_id', $this->massRejectType)->
                where('output_rejects.reject_area_id', $this->massRejectArea)->
                where('output_rejects.so_det_id', $this->massSize)->
                whereNotNull("output_rejects.id")->
                groupBy("output_rejects.id")->
                take($this->massQty)->
                get();
        } else if ($this->massRejectDepartment == "packing") {
            $rejectList = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, output_rejects_packing.id, output_rejects_packing.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
                leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects_packing.reject_type_id', $this->massRejectType)->
                where('output_rejects_packing.reject_area_id', $this->massRejectArea)->
                where('output_rejects_packing.so_det_id', $this->massSize)->
                whereNotNull("output_rejects_packing.id")->
                groupBy("id")->
                take($this->massQty)->
                get();
        } else {
            $rejectQcList = DB::table('output_rejects')->selectRaw('"qc" as output_type, output_rejects.id, output_rejects.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin("so_det", "so_det.id", "=", "output_rejects.so_det_id")->
                leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects.reject_type_id', $this->massRejectType)->
                where('output_rejects.reject_area_id', $this->massRejectArea)->
                where('output_rejects.so_det_id', $this->massSize);
            $rejectPackingList = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, output_rejects_packing.id, output_rejects_packing.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
                leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects_packing.reject_type_id', $this->massRejectType)->
                where('output_rejects_packing.reject_area_id', $this->massRejectArea)->
                where('output_rejects_packing.so_det_id', $this->massSize);
            $rejectList = $rejectQcList->union($rejectPackingList)->whereNotNull("id")->groupBy("output_type","id")->take($this->massQty)->get();
        }

        if ($rejectList->whereNotNull("id")->count() > 0) {
            $insertRejectArr = [];
            $batch = Str::uuid();
            foreach ($rejectList as $reject) {
                $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                    ppic_master_so.id,
                    ppic_master_so.po,
                    ppic_master_so.id_so_det,
                    so_det.id as so_det_id,
                    so_det.size,
                    so_det.dest,
                    ppic_master_so.qty_po,
                    COUNT(output_rfts_packing_po.id) as qty_output
                ")
                ->leftJoin('signalbit_erp.output_rfts_packing_po', 'output_rfts_packing_po.po_id', '=', 'ppic_master_so.id')
                ->leftJoin('signalbit_erp.so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
                ->leftJoin('signalbit_erp.so', 'so.id', '=', 'so_det.id_so')
                ->leftJoin('signalbit_erp.act_costing', 'act_costing.id', '=', 'so.id_cost')
                ->leftJoin('signalbit_erp.mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
                ->leftJoin('signalbit_erp.master_size_new', 'master_size_new.size', '=', 'so_det.size')
                ->leftJoin('signalbit_erp.masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
                ->where('so_det.cancel', '!=', 'Y')
                ->where('ppic_master_so.po', $this->selectedPo)
                ->where('so_det.color', $reject->color)
                ->where('so_det.size', $reject->size)
                ->groupBy('ppic_master_so.id')
                ->first();

                // create reject
                array_push($insertRejectArr, [
                    'master_plan_id' => $this->orderInfo->id,
                    'so_det_id' => $currentPo ? $currentPo->so_det_id : $reject->so_det_id,
                    'po_id' => $currentPo ? $currentPo->id : NULL,
                    'status' => $reject->reject_status,
                    'alokasi' => $currentPo ? "po" : "gudang stok",
                    'reject_id' => $reject ? $reject->id : NULL,
                    'type' => 'reject',
                    'department' => $reject->output_type,
                    'created_by' => Auth::user()->id,
                    'created_by_username' => Auth::user()->username,
                    'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'batch' => $batch
                ]);

                $availableReject += 1;
            }

            // Mass Insert Reject
            $insertReject = Rft::insert($insertRejectArr);

            if ($insertReject) {
                $currentReject = Rft::where("batch", $batch)->get();

                $insertGudangStokArr = [];
                foreach ($currentReject as $reject) {
                    array_push($insertGudangStokArr, [
                        'so_det_id' => $reject->so_det_id,
                        'packing_po_id' => $reject->id,
                        'type' => 'reject',
                        'created_by' => Auth::user()->id,
                        'created_by_username' => Auth::user()->username,
                        'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }

                OutputGudangStok::insert($insertGudangStokArr);
            }

            if ($availableReject > 0) {
                $this->emit('alert', 'success', "REJECT dengan Ukuran : ".$rejectList[0]->size.", Tipe : ".$this->massRejectTypeName." dan Area : ".$this->massRejectAreaName." berhasil masuk sebanyak ".$rejectList->count()." kali.");

                $this->emit('getPoSizeQty');

                $this->emit('hideModal', 'massReject');
            } else {
                $this->emit('alert', 'error', "Terjadi kesalahan. REJECT dengan Ukuran : ".$rejectList[0]->size.", Tipe : ".$this->massRejectTypeName." dan Area : ".$this->massRejectAreaName." tidak berhasil masuk.");
            }
        } else {
            $this->emit('alert', 'warning', "Data tidak ditemukan.");
        }
    }

    public function submitReject($rejectId, $department) {
        $thisDefectReject = Rft::where('reject_id', $rejectId)->where("department", $department)->count();

        if ($thisDefectReject < 1) {
            // remove from defect
            if ($department == "qc") {
                $reject = DB::table('output_rejects')->selectRaw('"qc" as output_type, output_rejects.id, output_rejects.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                    leftJoin("so_det", "so_det.id", "=", "output_rejects.so_det_id")->
                    leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
                    leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
                    leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
                    leftJoin('output_rfts_packing_po', function ($join) {
                        $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                        $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
                    })->
                    whereNull('output_rfts_packing_po.id')->
                    where('output_rejects.id', $rejectId)->
                    first();
            } else if ($department == "packing") {
                $reject = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, output_rejects_packing.id, output_rejects_packing.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                    leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
                    leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
                    leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
                    leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
                    leftJoin('output_rfts_packing_po', function ($join) {
                        $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                        $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
                    })->
                    whereNull('output_rfts_packing_po.id')->
                    where('output_rejects_packing.id', $rejectId)->
                    first();
            } else {
                $rejectQc = DB::table('output_rejects')->selectRaw('"qc" as output_type, output_rejects.id, output_rejects.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                    leftJoin("so_det", "so_det.id", "=", "output_rejects.so_det_id")->
                    leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
                    leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
                    leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
                    leftJoin('output_rfts_packing_po', function ($join) {
                        $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                        $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
                    })->
                    whereNull('output_rfts_packing_po.id')->
                    where('output_rejects.id', $rejectId);
                $rejectPacking = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, output_rejects_packing.id, output_rejects_packing.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                    leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
                    leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
                    leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
                    leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
                    leftJoin('output_rfts_packing_po', function ($join) {
                        $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                        $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
                    })->
                    whereNull('output_rfts_packing_po.id')->
                    where('output_rejects_packing.id', $rejectId);
                $reject = $rejectQc->union($rejectPacking)->first();
            }

            // Get PO
            $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                    ppic_master_so.id,
                    ppic_master_so.po,
                    ppic_master_so.id_so_det,
                    so_det.id as so_det_id,
                    so_det.size,
                    so_det.dest,
                    ppic_master_so.qty_po,
                    COUNT(output_rfts_packing_po.id) as qty_output
                ")
                ->leftJoin('signalbit_erp.output_rfts_packing_po', 'output_rfts_packing_po.po_id', '=', 'ppic_master_so.id')
                ->leftJoin('signalbit_erp.so_det', 'so_det.id', '=', 'ppic_master_so.id_so_det')
                ->leftJoin('signalbit_erp.so', 'so.id', '=', 'so_det.id_so')
                ->leftJoin('signalbit_erp.act_costing', 'act_costing.id', '=', 'so.id_cost')
                ->leftJoin('signalbit_erp.mastersupplier', 'mastersupplier.id_supplier', '=', 'act_costing.id_buyer')
                ->leftJoin('signalbit_erp.master_size_new', 'master_size_new.size', '=', 'so_det.size')
                ->leftJoin('signalbit_erp.masterproduct', 'masterproduct.id', '=', 'act_costing.id_product')
                ->where('so_det.cancel', '!=', 'Y')
                ->where('ppic_master_so.po', $this->selectedPo)
                ->where('so_det.color', $reject->color)
                ->where('so_det.size', $reject->size)
                ->groupBy('ppic_master_so.id')
                ->first();

            if ($this->selectedPo == "GUDANG_STOK" || $currentPo) {
                // add to reject
                $createReject = Rft::create([
                    'master_plan_id' => $this->orderInfo->id,
                    'so_det_id' => $currentPo ? $currentPo->so_det_id : $reject->so_det_id,
                    'po_id' => $currentPo ? $currentPo->id : NULL,
                    'status' => $reject->reject_status,
                    'alokasi' => $currentPo ? "po" : "gudang stok",
                    'reject_id' => $reject ? $reject->id : NULL,
                    'type' => 'reject',
                    'department' => $reject->output_type,
                    'created_by' => Auth::user()->id,
                    'created_by_username' => Auth::user()->username,
                    'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                if ($createReject) {
                    if ($this->selectedPo == "GUDANG_STOK") {
                        OutputGudangStok::create([
                            'so_det_id' => $reject->so_det_id,
                            'packing_po_id' => $createReject->id,
                            'created_by' => Auth::user()->id,
                            'created_by_username' => Auth::user()->username,
                            'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);
                    }

                    $this->emit('alert', 'success', "REJECT dengan ID : ".$rejectId." berhasil masuk.");

                    $this->emit('getPoSizeQty');

                    // $this->emit('triggerDashboard', Auth::user()->username, Carbon::now()->format('Y-m-d'));
                } else {
                    $this->emit('alert', 'error', "Terjadi kesalahan. REJECT dengan ID : ".$rejectId." tidak berhasil masuk.");
                }
            }
        } else {
            $this->emit('alert', 'warning', "Pencegahan data redundant. REJECT dengan ID : ".$rejectId." sudah ada.");
        }
    }

    public function cancelReject($rejectId) {
        // delete from reject
        $deleteReject = Rft::where('id', $rejectId)->delete();

        if ($deleteReject) {
            $this->emit('alert', 'success', "REJECT dengan ID : ".$rejectId." berhasil di hapus.");
        } else {
            $this->emit('alert', 'error', "Terjadi kesalahan. REJECT dengan REJECT ID : ".$rejectId." tidak berhasil hapus.");
        }
    }

    public function render(SessionManager $session)
    {
        $this->emit('loadRejectPageJs');

        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        // Get total output
        $this->output = collect(DB::select("select output_rejects_packing.*, so_det.size, COUNT(output_rejects_packing.id) output from `output_rejects_packing` left join `so_det` on `so_det`.`id` = `output_rejects_packing`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `status` = 'NORMAL' group by so_det.id"));

        // Reject Image
        $this->allRejectImage = MasterPlan::select('gambar')->find($this->orderInfo->id);

        // Reject List
        $allRejectPositionQc = DB::table('output_rejects')->select("reject_area_x", "reject_area_y")->
            leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color);
        $allRejectPositionPacking = DB::table('output_rejects_packing')->select("reject_area_x", "reject_area_y")->
            leftJoin("master_plan", "master_plan.id", "=", "output_rejects_packing.master_plan_id")->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color);
        $this->allRejectPosition = $allRejectPositionQc->union($allRejectPositionPacking)->get();

        // Reject List
        $rejectQcList = DB::table('output_rejects')->selectRaw('"qc" as output_type, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
            leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)->
            whereRaw("
                (
                    output_defect_types.defect_type LIKE '%".$this->allRejectListFilter."%' OR
                    output_defect_areas.defect_area LIKE '%".$this->allRejectListFilter."%'
                )
            ")->
            groupBy('output_rejects.reject_type_id', 'output_rejects.reject_area_id', 'output_defect_types.defect_type', 'output_defect_areas.defect_area')->
            orderBy('total', 'desc');
        $rejectPackingList = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)->
            whereRaw("
                (
                    output_defect_types.defect_type LIKE '%".$this->allRejectListFilter."%' OR
                    output_defect_areas.defect_area LIKE '%".$this->allRejectListFilter."%'
                )
            ")->
            groupBy('output_rejects_packing.reject_type_id', 'output_rejects_packing.reject_area_id', 'output_defect_types.defect_type', 'output_defect_areas.defect_area')->
            orderBy('total', 'desc');
        $allRejectList = $rejectQcList->union($rejectPackingList)->groupBy('output_type', 'reject_type_id', 'reject_area_id', 'defect_type', 'defect_area')->orderBy("total", "desc")->paginate(5, ['*'], 'allRejectListPage');

        // Reject IN
        $rejectsQc = DB::table('output_rejects')->selectRaw('output_rejects.id, output_rejects.kode_numbering, master_plan.sewing_line, output_rejects.updated_at, output_rejects.created_at, output_rejects.reject_area_x, output_rejects.reject_area_y, output_rejects.reject_status, "qc" as output_type, output_defect_types.defect_type, output_defect_areas.defect_area, so_det.size as so_det_size')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects.master_plan_id')->
            leftJoin('so_det', 'so_det.id', '=', 'output_rejects.so_det_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)->
            whereRaw("(
                'qc' LIKE '%".$this->searchRejectIn."%' OR
                master_plan.sewing_line LIKE '%".$this->searchRejectIn."%' OR
                output_rejects.id LIKE '%".$this->searchRejectIn."%' OR
                so_det.size LIKE '%".$this->searchRejectIn."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchRejectIn."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchRejectIn."%' OR
                output_rejects.reject_status LIKE '%".$this->searchRejectIn."%'
            )")->
            orderBy('output_rejects.updated_at', 'desc');
        $rejectsPacking = DB::table('output_rejects_packing')->selectRaw('output_rejects_packing.id, output_rejects_packing.kode_numbering, master_plan.sewing_line, output_rejects_packing.updated_at, output_rejects_packing.created_at, output_rejects_packing.reject_area_x, output_rejects_packing.reject_area_y, output_rejects_packing.reject_status, "packing" as output_type, output_defect_types.defect_type, output_defect_areas.defect_area, so_det.size as so_det_size')->
            leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
            leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
            leftJoin('output_rfts_packing_po', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
            })->
            whereNull('output_rfts_packing_po.id')->
            where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
            where('master_plan.id_ws', $this->orderInfo->id_ws)->
            where('master_plan.color', $this->orderInfo->color)->
            whereRaw("(
                'finishing' LIKE '%".$this->searchRejectIn."%' OR
                master_plan.sewing_line LIKE '%".$this->searchRejectIn."%' OR
                output_rejects_packing.id LIKE '%".$this->searchRejectIn."%' OR
                so_det.size LIKE '%".$this->searchRejectIn."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchRejectIn."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchRejectIn."%' OR
                output_rejects_packing.reject_status LIKE '%".$this->searchRejectIn."%'
            )")->
            orderBy('output_rejects_packing.updated_at', 'desc');
        $rejectIn = $rejectsQc->union($rejectsPacking)->paginate(10, ['*'], 'rejectInPage');

        // Rejects
        $rejects = Rft::selectRaw('output_rfts_packing_po.*, ppic_master_so.po, COALESCE(output_rejects_packing.reject_type_id, output_rejects.reject_type_id) as reject_type_id, COALESCE(output_rejects_packing.reject_area_id, output_rejects.reject_area_id) as reject_area_id, COALESCE(output_rejects_packing.reject_area_x, output_rejects.reject_area_x) as reject_area_x, COALESCE(output_rejects_packing.reject_area_y, output_rejects.reject_area_y) as reject_area_y, output_defect_types.defect_type, output_defect_areas.defect_area, UPPER(output_rfts_packing_po.status) as reject_status, so_det.size as so_det_size')->
            leftJoin('output_rejects', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
            })->
            leftJoin('output_rejects_packing', function ($join) {
                $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
            })->
            leftJoin('output_defect_types', 'output_defect_types.id', '=', DB::raw('COALESCE(output_rejects_packing.reject_type_id, output_rejects.reject_type_id)'))->
            leftJoin('output_defect_areas', 'output_defect_areas.id', '=', DB::raw('COALESCE(output_rejects_packing.reject_area_id, output_rejects.reject_area_id)'))->
            leftJoin('so_det', 'so_det.id', '=', 'output_rfts_packing_po.so_det_id')->
            leftJoin('laravel_nds.ppic_master_so', 'ppic_master_so.id', '=', 'output_rfts_packing_po.po_id')->
            where('output_rfts_packing_po.type', 'reject')->
            where('output_rfts_packing_po.master_plan_id', $this->orderInfo->id)->
            whereRaw("(
                output_rfts_packing_po.department LIKE '%".$this->searchReject."%' OR
                output_rfts_packing_po.created_by LIKE '%".$this->searchReject."%' OR
                ppic_master_so.po LIKE '%".$this->searchReject."%' OR
                output_rfts_packing_po.id LIKE '%".$this->searchReject."%' OR
                so_det.size LIKE '%".$this->searchReject."%' OR
                output_defect_areas.defect_area LIKE '%".$this->searchReject."%' OR
                output_defect_types.defect_type LIKE '%".$this->searchReject."%' OR
                output_rfts_packing_po.status LIKE '%".$this->searchReject."%'
            )")->
            orderBy('output_rfts_packing_po.updated_at', 'desc')->paginate(10, ['*'], 'rejectsPage');

        if ($this->massRejectDepartment == "qc") {
            $massRejectList = DB::table('output_rejects')->selectRaw('"qc" as output_type, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin("so_det", "so_det.id", "=", "output_rejects.so_det_id")->
                leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects.reject_type_id', $this->massRejectType)->
                where('output_rejects.reject_area_id', $this->massRejectArea)->
                groupBy('output_rejects.so_det_id')->
                get();
        } else if ($this->massRejectDepartment == "packing") {
            $massRejectList = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
                leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects_packing.reject_type_id', $this->massRejectType)->
                where('output_rejects_packing.reject_area_id', $this->massRejectArea)->
                groupBy('output_rejects_packing.so_det_id')->
                get();
        } else {
            $massRejectQcList = DB::table('output_rejects')->selectRaw('"qc" as output_type, output_rejects.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects.reject_type_id, output_rejects.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin("so_det", "so_det.id", "=", "output_rejects.so_det_id")->
                leftJoin("master_plan", "master_plan.id", "=", "output_rejects.master_plan_id")->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"qc"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects.reject_type_id', $this->massRejectType)->
                where('output_rejects.reject_area_id', $this->massRejectArea)->
                groupBy('output_rejects.so_det_id');
            $massRejectPackingList = DB::table('output_rejects_packing')->selectRaw('"packing" as output_type, output_rejects_packing.reject_status, master_plan.id_ws, so_det.id as so_det_id, so_det.color, so_det.size, output_rejects_packing.reject_type_id, output_rejects_packing.reject_area_id, output_defect_types.defect_type, output_defect_areas.defect_area, count(*) as total')->
                leftJoin('so_det', 'so_det.id', '=', 'output_rejects_packing.so_det_id')->
                leftJoin('master_plan', 'master_plan.id', '=', 'output_rejects_packing.master_plan_id')->
                leftJoin('output_defect_areas', 'output_defect_areas.id', '=', 'output_rejects_packing.reject_area_id')->
                leftJoin('output_defect_types', 'output_defect_types.id', '=', 'output_rejects_packing.reject_type_id')->
                leftJoin('output_rfts_packing_po', function ($join) {
                    $join->on('output_rfts_packing_po.reject_id', '=', 'output_rejects_packing.id');
                    $join->on('output_rfts_packing_po.department', '=', DB::raw('"packing"'));
                })->
                whereNull('output_rfts_packing_po.id')->
                where('master_plan.tgl_plan', $this->orderInfo->tgl_plan)->
                where('master_plan.id_ws', $this->orderInfo->id_ws)->
                where('master_plan.color', $this->orderInfo->color)->
                where('output_rejects_packing.reject_type_id', $this->massRejectType)->
                where('output_rejects_packing.reject_area_id', $this->massRejectArea)->
                groupBy('output_rejects_packing.so_det_id');
            $massRejectList = $massRejectQcList->union($massRejectPackingList)->groupBy('output_type', 'so_det_id', 'size')->get();
        }
        $this->massSelectedReject = $massRejectList;

        // Defect types
        $this->defectTypes = DB::table("output_defect_types")->leftJoin(DB::raw("(select reject_type_id, count(id) total_reject from output_rejects where updated_at between '".date("Y-m-d", strtotime(date("Y-m-d").' -10 days'))." 00:00:00' and '".date("Y-m-d")." 23:59:59' group by reject_type_id) as rejects"), "rejects.reject_type_id", "=", "output_defect_types.id")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_type')->get();

        // Defect areas
        $this->defectAreas = DB::table("output_defect_areas")->leftJoin(DB::raw("(select reject_area_id, count(id) total_reject from output_rejects where updated_at between '".date("Y-m-d", strtotime(date("Y-m-d").' -10 days'))." 00:00:00' and '".date("Y-m-d")." 23:59:59' group by reject_area_id) as rejects"), "rejects.reject_area_id", "=", "output_defect_areas.id")->whereRaw("(hidden IS NULL OR hidden != 'Y')")->orderBy('defect_area')->get();

        return view('livewire.reject', ['rejectIn' => $rejectIn, 'rejects' => $rejects, 'allRejectList' => $allRejectList]);
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
