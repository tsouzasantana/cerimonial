<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresContractOwnership;
use App\Http\Requests\FinancialEntryRequest;
use App\Models\Contract;
use App\Models\FinancialEntry;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;

class FinancialEntryController extends Controller
{
    use EnsuresContractOwnership;

    public function store(FinancialEntryRequest $request, Contract $contract): RedirectResponse
    {
        $data = $this->withVendorOwnershipChecked($request->validated(), $contract);
        $data['created_by'] = $request->user()->id;

        $contract->financialEntries()->create($data);

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'financeiro'])
            ->with('success', 'Lançamento financeiro adicionado com sucesso.');
    }

    public function update(FinancialEntryRequest $request, Contract $contract, FinancialEntry $financialEntry): RedirectResponse
    {
        $this->ensureBelongsToContract($financialEntry, $contract);

        $data = $this->withVendorOwnershipChecked($request->validated(), $contract);

        $financialEntry->update($data);

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'financeiro'])
            ->with('success', 'Lançamento financeiro atualizado com sucesso.');
    }

    public function destroy(Contract $contract, FinancialEntry $financialEntry): RedirectResponse
    {
        $this->ensureBelongsToContract($financialEntry, $contract);

        $financialEntry->delete();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'financeiro'])
            ->with('success', 'Lançamento financeiro inativado com sucesso.');
    }

    private function withVendorOwnershipChecked(array $data, Contract $contract): array
    {
        if (! empty($data['vendor_id'])) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            abort_unless($vendor->contract_id === $contract->id, 404);
        }

        return $data;
    }
}
