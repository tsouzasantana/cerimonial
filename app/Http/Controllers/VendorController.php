<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresContractOwnership;
use App\Http\Requests\VendorRequest;
use App\Models\Contract;
use App\Models\Vendor;
use App\Models\VendorServiceType;
use Illuminate\Http\RedirectResponse;

class VendorController extends Controller
{
    use EnsuresContractOwnership;

    public function store(VendorRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();
        $data['vendor_service_type_id'] = VendorServiceType::resolveId(
            $data['vendor_service_type_id'] ?? null,
            $data['new_service_type'] ?? null,
        );
        unset($data['new_service_type']);
        $data['created_by'] = $request->user()->id;

        $contract->vendors()->create($data);

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', 'Fornecedor cadastrado com sucesso.');
    }

    public function update(VendorRequest $request, Contract $contract, Vendor $vendor): RedirectResponse
    {
        $this->ensureBelongsToContract($vendor, $contract);

        $data = $request->validated();
        $data['vendor_service_type_id'] = VendorServiceType::resolveId(
            $data['vendor_service_type_id'] ?? null,
            $data['new_service_type'] ?? null,
        );
        unset($data['new_service_type']);

        $vendor->update($data);

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Contract $contract, Vendor $vendor): RedirectResponse
    {
        $this->ensureBelongsToContract($vendor, $contract);

        $vendor->delete();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', 'Fornecedor inativado com sucesso.');
    }

    public function restore(Contract $contract, int $vendor): RedirectResponse
    {
        $vendor = Vendor::withTrashed()->findOrFail($vendor);
        $this->ensureBelongsToContract($vendor, $contract);

        $vendor->restore();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', 'Fornecedor reativado com sucesso.');
    }
}
