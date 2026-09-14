<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorInstallmentBatchRequest;
use App\Http\Requests\VendorInstallmentRequest;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Vendor;
use App\Models\VendorInstallment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class VendorInstallmentController extends Controller
{
    public function store(VendorInstallmentRequest $request, Contract $contract, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->contract_id === $contract->id, 404);

        $vendor->installments()->create($request->validated());

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', 'Parcela do fornecedor adicionada com sucesso.');
    }

    public function storeBatch(VendorInstallmentBatchRequest $request, Contract $contract, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->contract_id === $contract->id, 404);

        $data = $request->validated();

        $count = (int) $data['installment_count'];
        $totalCents = (int) round(((float) $data['total_amount']) * 100);
        $baseCents = intdiv($totalCents, $count);
        $remainderCents = $totalCents - ($baseCents * $count);

        $nextNumber = (int) ($vendor->installments()->max('number') ?? 0) + 1;
        $firstDueDate = Carbon::parse($data['first_due_date']);

        $installments = [];
        for ($i = 0; $i < $count; $i++) {
            $amountCents = $baseCents + ($i === $count - 1 ? $remainderCents : 0);

            $installments[] = [
                'number' => $nextNumber + $i,
                'amount' => $amountCents / 100,
                'due_date' => $firstDueDate->copy()->addMonthsNoOverflow($i * (int) $data['interval_months']),
                'status' => Installment::STATUS_PENDENTE,
            ];
        }

        $vendor->installments()->createMany($installments);

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', "{$count} parcelas do fornecedor geradas com sucesso.");
    }

    public function update(VendorInstallmentRequest $request, Contract $contract, Vendor $vendor, VendorInstallment $installment): RedirectResponse
    {
        abort_unless($vendor->contract_id === $contract->id, 404);
        abort_unless($installment->vendor_id === $vendor->id, 404);

        $installment->update($request->validated());

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', 'Parcela do fornecedor atualizada com sucesso.');
    }

    public function destroy(Contract $contract, Vendor $vendor, VendorInstallment $installment): RedirectResponse
    {
        abort_unless($vendor->contract_id === $contract->id, 404);
        abort_unless($installment->vendor_id === $vendor->id, 404);

        $installment->delete();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'fornecedores'])
            ->with('success', 'Parcela do fornecedor inativada com sucesso.');
    }
}
