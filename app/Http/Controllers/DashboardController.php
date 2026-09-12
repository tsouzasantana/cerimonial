<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Installment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeContracts = Contract::where('status', Contract::STATUS_ATIVO)->count();
        $totalClients = Client::count();

        $upcomingEvents = Contract::with('client')
            ->whereIn('status', [Contract::STATUS_ATIVO, Contract::STATUS_RASCUNHO])
            ->whereDate('event_date', '>=', now())
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        $overdueInstallments = Installment::with('contract.client')
            ->where('status', Installment::STATUS_PENDENTE)
            ->whereDate('due_date', '<', now())
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'activeContracts',
            'totalClients',
            'upcomingEvents',
            'overdueInstallments',
        ));
    }
}
