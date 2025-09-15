<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
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

        $additionalMessage = $balanceOutputData < $this->outputInput && $balanceOutputData > 0 ? "<b>".($this->outputInput - $balanceOutputData)."</b> output melebihi batas input." : null;
        // if ($balanceOutputData < $this->outputInput) {
        //     $this->outputInput = $balanceOutputData;
        //

        $currentPo = DB::connection("mysql_nds")->table("ppic_master_so")->selectRaw("
                ppic_master_so.id
            ")
            ->where('ppic_master_so.po', $this->selectedPo)
            ->where('ppic_master_so.id_so_det', $this->sizeInput)
            ->first();

        $insertData = [];
        if ($this->outputInput > 0) {
            if ($currentPo) {
                for ($i = 0; $i < $this->outputInput; $i++)
                {
                    array_push($insertData, [
                        'master_plan_id' => $this->orderInfo->id,
                        'so_det_id' => $this->sizeInput,
                        'po_id' => $currentPo->id,
                        'status' => 'NORMAL',
                        'created_by' => Auth::user()->line_id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }

                $insertRft = RftModel::insert($insertData);

                if ($insertRft) {
                    $getSize = DB::table('so_det')
                        ->select('id', 'size')
                        ->where('id', $this->sizeInput)
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
                $getSize = DB::table('so_det')
                    ->select('id', 'size', 'dest')
                    ->where('id', $this->sizeInput)
                    ->first();

                $this->emit('alert', 'error', "PO tidak ditemukan untuk size <b>".$getSize->size.($getSize->dest && $getSize->dest != '-' ? ' - '.$getSize->dest : '')."</b> (ID SO : <b>".$this->sizeInput."</b>)");
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
