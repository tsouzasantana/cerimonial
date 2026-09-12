<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\Installment;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'financeiro' => $this->financeiro(),
            'contratos' => $this->contratos(),
            'checklist' => $this->checklist(),
            'clientes' => $this->clientes(),
        ]);
    }

    private function lastMonths(int $count): array
    {
        return collect(range($count - 1, 0))
            ->map(fn ($i) => now()->subMonths($i)->startOfMonth())
            ->all();
    }

    private function nextMonths(int $count): array
    {
        return collect(range(0, $count - 1))
            ->map(fn ($i) => now()->addMonths($i)->startOfMonth())
            ->all();
    }

    private function financeiro(): array
    {
        $recebido = Installment::where('status', Installment::STATUS_PAGO)->sum('amount');

        $aReceber = Installment::whereIn('status', [Installment::STATUS_PENDENTE, Installment::STATUS_ATRASADO])
            ->sum('amount');

        $atrasadas = Installment::where(function ($query) {
            $query->where('status', Installment::STATUS_ATRASADO)
                ->orWhere(fn ($q) => $q->where('status', Installment::STATUS_PENDENTE)->whereDate('due_date', '<', today()));
        });

        $receitaPorMes = collect($this->lastMonths(6))->map(function (Carbon $month) {
            return [
                'label' => $month->translatedFormat('M/y'),
                'total' => (float) Installment::where('status', Installment::STATUS_PAGO)
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)
                    ->sum('amount'),
            ];
        });

        $acumulado = 0.0;
        $fluxoProjetado = collect($this->nextMonths(6))->map(function (Carbon $month) use (&$acumulado) {
            $total = (float) Installment::whereIn('status', [Installment::STATUS_PENDENTE, Installment::STATUS_ATRASADO])
                ->whereYear('due_date', $month->year)
                ->whereMonth('due_date', $month->month)
                ->sum('amount');

            $acumulado += $total;

            return [
                'label' => $month->translatedFormat('M/y'),
                'total' => $total,
                'acumulado' => $acumulado,
            ];
        });

        return [
            'recebido' => (float) $recebido,
            'a_receber' => (float) $aReceber,
            'atrasadas_count' => $atrasadas->count(),
            'atrasadas_total' => (float) $atrasadas->sum('amount'),
            'receita_por_mes' => $receitaPorMes,
            'fluxo_projetado' => $fluxoProjetado,
        ];
    }

    private function contratos(): array
    {
        $porStatus = collect(Contract::statusOptions())->map(function ($label, $status) {
            return [
                'label' => $label,
                'total' => Contract::where('status', $status)->count(),
            ];
        })->values();

        $novosPorMes = collect($this->lastMonths(6))->map(function (Carbon $month) {
            return [
                'label' => $month->translatedFormat('M/y'),
                'total' => Contract::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
            ];
        });

        $proximosEventos = Contract::with('client')
            ->whereIn('status', [Contract::STATUS_ATIVO, Contract::STATUS_RASCUNHO])
            ->whereBetween('event_date', [today(), today()->addDays(30)])
            ->orderBy('event_date')
            ->limit(10)
            ->get();

        return [
            'por_status' => $porStatus,
            'novos_por_mes' => $novosPorMes,
            'proximos_eventos' => $proximosEventos,
            'proximos_eventos_count' => $proximosEventos->count(),
        ];
    }

    private function checklist(): array
    {
        $total = ContractTask::count();
        $concluidas = ContractTask::where('status', ContractTask::STATUS_CONCLUIDA)->count();
        $atrasadas = ContractTask::whereIn('status', ContractTask::openStatuses())
            ->whereDate('due_date', '<', today())
            ->count();

        $contratosComAtraso = Contract::query()
            ->withCount(['tasks as overdue_tasks_count' => function ($query) {
                $query->whereIn('status', ContractTask::openStatuses())
                    ->whereDate('due_date', '<', today());
            }])
            ->with('client')
            ->get()
            ->filter(fn (Contract $contract) => $contract->overdue_tasks_count > 0)
            ->sortByDesc('overdue_tasks_count')
            ->take(10)
            ->values();

        return [
            'total' => $total,
            'concluidas' => $concluidas,
            'atrasadas' => $atrasadas,
            'taxa_conclusao' => $total > 0 ? round($concluidas / $total * 100) : 0,
            'contratos_com_atraso' => $contratosComAtraso,
        ];
    }

    private function clientes(): array
    {
        $novosPorMes = collect($this->lastMonths(6))->map(function (Carbon $month) {
            return [
                'label' => $month->translatedFormat('M/y'),
                'total' => Client::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
            ];
        });

        return [
            'total_ativos' => Client::count(),
            'novos_por_mes' => $novosPorMes,
        ];
    }
}
