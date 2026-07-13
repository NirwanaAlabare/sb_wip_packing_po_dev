<?php

namespace App\Http\Livewire;

use App\Models\SignalBit\ReturnPacking;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProductionPanelReturn extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap'; 

    public $output_rfts_packing_po_id;
    public $master_plan_id;
    public $selectedPo;
    public $selectedPoId;
    public $selectedPoWs;
    public $selectedPoColor;
    public $selectedPoSize;
    public $selectedPoPackingLine;
    public $selectedPoFinishingLine;
    public $kpno;
    public $style;
    public $soDetId;
    public $actCostingId;
    public $qtyReturn;
    public $qtyPackingLine;

    public $startDate;
    public $endDate;
    public $searchSummary;

    public $selectedTanggal;

    protected $rules = [
        'selectedPo' => 'required',
        'selectedPoWs' => 'required',
        'selectedPoColor' => 'required',
        'selectedPoSize' => 'required',
        'selectedPoPackingLine' => 'required',
        'selectedPoFinishingLine' => 'required',
        'kpno' => 'required',
        'style' => 'required',
        'qtyReturn' => 'required|numeric|min:1|lte:qtyPackingLine',
    ];

    protected $messages = [
        'selectedPo.required' => 'PO wajib dipilih',
        'selectedPoWs.required' => 'WS wajib dipilih',
        'selectedPoColor.required' => 'Color wajib dipilih',
        'selectedPoSize.required' => 'Size wajib dipilih',
        'selectedPoPackingLine.required' => 'Packing Line wajib dipilih',
        'selectedPoFinishingLine.required' => 'Line QC Finishing wajib dipilih',
        'kpno.required' => 'KP No wajib ada',
        'style.required' => 'Style wajib ada',
        'qtyReturn.required' => 'QTY Return wajib diisi',
        'qtyReturn.numeric' => 'QTY harus angka',
        'qtyReturn.min' => 'QTY minimal 1',
        'qtyReturn.lte' => 'QTY Return tidak boleh lebih dari qty packing line',
    ];

    public function mount()
    {
        $this->output_rfts_packing_po_id = '';
        $this->master_plan_id = '';
        $this->selectedPo = '';
        $this->selectedPoId = '';
        $this->selectedPoWs = '';
        $this->selectedPoColor = '';
        $this->selectedPoSize = '';
        $this->selectedPoPackingLine = '';
        $this->selectedPoFinishingLine = '';
        $this->kpno = '';
        $this->style = '';
        $this->actCostingId = '';
        $this->soDetId = '';
        $this->qtyReturn = '';
        $this->qtyPackingLine = '';

        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate   = Carbon::today()->format('Y-m-d');
    }

    public function dehydrate()
    {
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        for ($i = 0; $i < $this->qtyReturn; $i++) {
            ReturnPacking::create([
                'output_rfts_packing_po_id' => $this->output_rfts_packing_po_id,
                'master_plan_id' => $this->master_plan_id,
                'ppic_master_id' => $this->selectedPoId,
                'act_costing_id' => $this->actCostingId,
                'so_det_id' => $this->soDetId,
                'po' => $this->selectedPo,
                'kpno' => $this->kpno,
                'style' => $this->style,
                'color' => $this->selectedPoColor,
                'size' => $this->selectedPoSize,
                'packing_line' => $this->selectedPoPackingLine,
                'qty_return' => 1, // setiap row bernilai 1
                'line_qc_finishing' => $this->selectedPoFinishingLine,
                'created_by' => Auth::user()->id,
                'created_by_username' => Auth::user()->username,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        $this->reset([
            'output_rfts_packing_po_id',
            'master_plan_id',
            'selectedPo',
            'selectedPoId',
            'selectedPoWs',
            'actCostingId',
            'kpno',
            'style',
            'selectedPoColor',
            'soDetId',
            'selectedPoSize',
            'selectedPoPackingLine',
            'qtyReturn',
            'selectedPoFinishingLine',
        ]);

        $this->emit('resetSelect2');
        $this->emit('afterSave');
        $this->emit('alert', 'success', 'Data berhasil disimpan');
    }

    public function openModal($tanggal)
    {
        $this->selectedTanggal = $tanggal;
        $this->resetPage('modalDetailsPage'); 
        $this->emit('openModal');
    }

    public function getModalDetailsProperty()
    {
        if (!$this->selectedTanggal) {
            return new LengthAwarePaginator([], 0, 10, 1, [
                'pageName' => 'modalDetailsPage',
            ]);
        }

        return ReturnPacking::selectRaw("
            DATE(created_at) as tanggal,
            packing_line,
            po,
            kpno,
            style,
            color,
            size,
            line_qc_finishing,
            COUNT(*) as qty_return,
            SUM(
                CASE
                    WHEN status <> 'rft'
                    THEN 1
                    ELSE 0
                END
            ) as qty_check,
            COUNT(*) - SUM(
                CASE
                    WHEN status <> 'rft'
                    THEN 1
                    ELSE 0
                END
            ) as qty_blc,
            MIN(id) as id
        ")
        ->whereDate(
            'created_at',
            Carbon::createFromFormat('d-m-Y', $this->selectedTanggal)
        )
        ->groupBy(
            DB::raw('DATE(created_at)'),
            'packing_line',
            'po',
            'kpno',
            'style',
            'color',
            'size',
            'line_qc_finishing'
        )
        ->orderBy('tanggal')
        ->paginate(10, ['*'], 'modalDetailsPage')
        ->through(function ($item) {
            $item->tanggal = Carbon::parse($item->tanggal)->format('d-m-Y');
            $item->worksheet = $item->kpno;
            $item->qty_cek_qc = $item->qty_check;
            $item->qc_line = $item->line_qc_finishing;

            return $item;
        });
    }

    public function render()
    {
        $query = ReturnPacking::selectRaw("
            DATE_FORMAT(created_at, '%d-%m-%Y') as tanggal,
            COUNT(*) as qty_return,
            SUM(
                CASE
                    WHEN status <> 'rft'
                    THEN 1
                    ELSE 0
                END
            ) as qty_check,
            COUNT(*) - SUM(
                CASE
                    WHEN status <> 'rft'
                    THEN 1
                    ELSE 0
                END
            ) as qty_blc
        ")->groupBy('tanggal');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ]);
        }

        if ($this->searchSummary) {
            $search = $this->searchSummary;
            $query->havingRaw("tanggal LIKE ?", ["%{$search}%"])
                ->orHavingRaw("COUNT(*) LIKE ?", ["%{$search}%"])
                ->orHavingRaw("
                    SUM(
                        CASE
                            WHEN status <> 'rft'
                            THEN 1
                            ELSE 0
                        END
                    ) LIKE ?
                ", ["%{$search}%"])
                ->orHavingRaw("
                    COUNT(*) - SUM(
                        CASE
                            WHEN status <> 'rft'
                            THEN 1
                            ELSE 0
                        END
                    ) LIKE ?
                ", ["%{$search}%"]);
        }

        $summary = $query->paginate(10);

        return view('livewire.production-panel-return', [
            'summary' => $summary,
            'modalDetails' => $this->modalDetails,
        ]);
    }
}