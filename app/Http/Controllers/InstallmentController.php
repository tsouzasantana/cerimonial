<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstallmentRequest;
use App\Models\Contract;
use App\Models\Installment;
use Illuminate\Http\RedirectResponse;

class InstallmentController extends Controller
{
    public function store(InstallmentRequest $request, Contract $contract): RedirectResponse
    {
        $contract->installments()->create($request->validated());

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Parcela adicionada com sucesso.');
    }

    public function update(InstallmentRequest $request, Contract $contract, Installment $installment): RedirectResponse
    {
        abort_unless($installment->contract_id === $contract->id, 404);

        $data = $request->validated();

        if ($data['status'] === Installment::STATUS_PAGO && empty($data['paid_at'])) {
            $data['paid_at'] = now()->toDateString();
        }

        $installment->update($data);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Parcela atualizada com sucesso.');
    }

    public function destroy(Contract $contract, Installment $installment): RedirectResponse
    {
        abort_unless($installment->contract_id === $contract->id, 404);

        $installment->delete();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Parcela inativada com sucesso.');
    }

    public function restore(Contract $contract, int $installment): RedirectResponse
    {
        $installment = Installment::withTrashed()->findOrFail($installment);
        abort_unless($installment->contract_id === $contract->id, 404);

        $installment->restore();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Parcela reativada com sucesso.');
    }
}
