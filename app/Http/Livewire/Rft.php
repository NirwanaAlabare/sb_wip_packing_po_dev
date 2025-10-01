<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\SignalBit\OutputGudangStok;
use App\Models\SignalBit\Rft as RftModel;
use App\Models\SignalBit\Defect;
use App\Models\SignalBit\Rework;
use App\Models\SignalBit\Reject;
use App\Models\SignalBit\EndlineOutput;
use App\Models\Nds\OutputPacking;
use Carbon\Carbon;
use DB;

class Rft extends Component
{
    public $orderInfo;
    public $orderWsDetailSizes;
    public $output;
    public $outputInput;
    public $sizeInput;
    public $sizeInputText;
    public $submitting;
    public $selectedPo;

    protected $rules = [
        'outputInput' => 'required|numeric|min:1',
        'sizeInput' => 'required',
    ];

    protected $messages = [
        'outputInput.required' => 'Harap tentukan kuantitas output.',
        'outputInput.numeric' => 'Harap isi kuantitas output dengan angka.',
        'outputInput.min' => 'Kuantitas output tidak bisa kurang dari 1.',
        'sizeInput.required' => 'Harap tentukan ukuran output.',
    ];

    protected $listeners = [
        'updateWsDetailSizes' => 'updateWsDetailSizes',
        'updatePo' => 'updatePo',
    ];

    public function mount(SessionManager $session, $orderWsDetailSizes)
    {
        $this->orderWsDetailSizes = $orderWsDetailSizes;
        $session->put('orderWsDetailSizes', $orderWsDetailSizes);
        $this->output = 0;
        $this->outputInput = 1;
        $this->sizeInput = null;
        $this->sizeInputText = null;
        $this->submitting = false;
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
        $this->output = collect(DB::select("select output_rfts_packing_po.*, so_det.size, COUNT(output_rfts_packing_po.id) output from `output_rfts_packing_po` left join `so_det` on `so_det`.`id` = `output_rfts_packing_po`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `status` = 'NORMAL' group by so_det.id"));
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

    public function submitInput()
    {
        $validatedData = $this->validate();

        $finishlineOutputData = DB::table("output_rfts_packing")->selectRaw("output_rfts_packing.*")->leftJoin("master_plan", "master_plan.id", "=", "output_rfts_packing.master_plan_id")->where("id_ws", $this->orderInfo->id_ws)->where("color", $this->orderInfo->color)->where("so_det_id", $this->sizeInput)->count();
        $currentRftData = RftModel::selectRaw("output_rfts_packing_po.*")->leftJoin("master_plan", "master_plan.id", "=", "output_rfts_packing_po.master_plan_id")->where('id_ws', $this->orderInfo->id_ws)->where("color", $this->orderInfo->color)->where("so_det_id", $this->sizeInput)->count();
        // $currentDefectData = Defect::selectRaw("output_defects_packing.*")->leftJoin("master_plan", "master_plan.id", "=", "output_defects_packing.master_plan_id")->where('id_ws', $this->orderInfo->id_ws)->where("color", $this->orderInfo->color)->where("so_det_id", $this->sizeInput)->where("defect_status", "defect")->count();
        // $currentRejectData = Reject::selectRaw("output_rejects_packing.*")->leftJoin("master_plan", "master_plan.id", "=", "output_rejects_packing.master_plan_id")->where('id_ws', $this->orderInfo->id_ws)->where("color", $this->orderInfo->color)->where("so_det_id", $this->sizeInput)->count();
        $currentOutputData = $currentRftData/*+$currentDefectData+$currentRejectData*/;
        $balanceOutputData = $finishlineOutputData-$currentOutputData;

        $additionalMessage = $balanceOutputData < $this->outputInput && $balanceOutputData > 0 ? "<b>".($this->outputInput - $balanceOutputData)."</b> output melebihi batas input QC Finishing." : null;
        if ($balanceOutputData < $this->outputInput) {
            $this->outputInput = $balanceOutputData;
        }

        // $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
        //         ppic_master_so.id
        //     ")
        //     ->where('ppic_master_so.po', $this->selectedPo)
        //     ->where('ppic_master_so.id_so_det', $this->sizeInput)
        //     ->first();

        $currentSizeInput = $this->sizeInput;
        $currentSizeInputText = $this->sizeInputText;

        $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                ppic_master_so.id,
                ppic_master_so.po,
                ppic_master_so.id_so_det,
                so_det.size,
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
            ->where('ppic_master_so.id_so_det', $currentSizeInput)
            ->groupBy('ppic_master_so.id')
            ->first();

        $insertData = [];
        if ($this->outputInput > 0) {
            if ($this->selectedPo == "GUDANG_STOK" || $currentPo) {

                if ($this->selectedPo == "GUDANG_STOK" || (($currentPo->qty_output + $this->outputInput) <= $currentPo->qty_po)) {
                    $batch = Str::uuid();
                    for ($i = 0; $i < $this->outputInput; $i++)
                    {
                        array_push($insertData, [
                            'master_plan_id' => $this->orderInfo->id,
                            'so_det_id' => $currentSizeInput,
                            'po_id' => $currentPo ? $currentPo->id : NULL,
                            'status' => 'NORMAL',
                            'alokasi' => $currentPo ? 'po' : 'gudang stok',
                            'created_by' => Auth::user()->id,
                            'created_by_username' => Auth::user()->username,
                            'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                            'batch' => $batch
                        ]);
                    }

                    $insertRft = RftModel::insert($insertData);

                    if ($insertRft) {
                        // Gudang Stok
                        if ($this->selectedPo == "GUDANG_STOK") {
                            $currentRft = RftModel::where("batch", $batch)->get();

                            $insertDataGudangStok = [];
                            foreach ($currentRft as $rft) {
                                array_push($insertDataGudangStok, [
                                    'so_det_id' => $currentSizeInput,
                                    'packing_po_id' => $rft->id,
                                    'created_by' => Auth::user()->id,
                                    'created_by_username' => Auth::user()->username,
                                    'created_by_line' => Auth::user()->line_type == "multi" ? $this->orderInfo->sewing_line : Auth::user()->line->username,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now()
                                ]);
                            }

                            OutputGudangStok::insert($insertDataGudangStok);
                        }

                        $getSize = DB::table('so_det')
                            ->select('id', 'size')
                            ->where('id', $currentSizeInput)
                            ->first();

                        $this->emit('alert', 'success', "<b>".$this->outputInput."</b> output berukuran <b>".$getSize->size."</b> berhasil terekam. ");
                        if ($additionalMessage) {
                            $this->emit('alert', 'error', $additionalMessage);
                        }

                        $this->outputInput = 1;
                        $this->sizeInput = '';

                        $this->emit('getPoSizeQty');
                    } else {
                        $this->emit('alert', 'error', "Terjadi kesalahan. Output tidak berhasil direkam.");
                    }
                } else {
                    $this->emit('alert', 'error', "QTY <b>Output</b> tidak dapat melebihi QTY <b>PO</b>.");
                }
            } else {
                $getSize = DB::table('so_det')
                    ->select('id', 'size', 'dest')
                    ->where('id', $currentSizeInput)
                    ->first();

                $this->emit('alert', 'error', "PO tidak ditemukan untuk size <b>".$getSize->size.($getSize->dest && $getSize->dest != '-' ? ' - '.$getSize->dest : '')."</b> (ID SO : <b>".$currentSizeInput."</b>)");
            }
        } else {
            $this->emit('alert', 'error', "Output packing-line tidak bisa melebihi finishline.");
        }
    }

    public function render(SessionManager $session)
    {
        $this->orderInfo = $session->get('orderInfo', $this->orderInfo);
        $this->orderWsDetailSizes = $session->get('orderWsDetailSizes', $this->orderWsDetailSizes);

        // Get total output
        $this->output = collect(DB::select("select output_rfts_packing_po.*, so_det.size, COUNT(output_rfts_packing_po.id) output from `output_rfts_packing_po` left join `so_det` on `so_det`.`id` = `output_rfts_packing_po`.`so_det_id` where `master_plan_id` = '".$this->orderInfo->id."' and `status` = 'NORMAL' group by so_det.id"));

        return view('livewire.rft');
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }
}
