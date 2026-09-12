<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractItemRequest;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;

class ContractItemController extends Controller
{
    public function store(ContractItemRequest $request, Contract $contract): RedirectResponse
    {
        $service = Service::findOrFail($request->validated('service_id'));
        $quantity = (int) $request->validated('quantity');

        $contract->items()->create([
            'service_id' => $service->id,
            'quantity' => $quantity,
            'unit_price' => $service->price,
            'total_price' => $service->price * $quantity,
        ]);

        $contract->recalculateTotals();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Item adicionado ao contrato.');
    }

    public function destroy(Contract $contract, ContractItem $item): RedirectResponse
    {
        abort_unless($item->contract_id === $contract->id, 404);

        $item->delete();
        $contract->recalculateTotals();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Item removido do contrato.');
    }
}
