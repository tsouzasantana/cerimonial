<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresContractOwnership;
use App\Http\Requests\InstallmentBatchRequest;
use App\Http\Requests\InstallmentRequest;
use App\Models\Contract;
use App\Models\Installment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class InstallmentController extends Controller
{
    use EnsuresContractOwnership;

    public function store(InstallmentRequest $request, Contract $contract): RedirectResponse
    {
        $contract->installments()->create($request->validated());

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Parcela adicionada com sucesso.');
    }

    public function storeBatch(InstallmentBatchRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();

        $count = (int) $data['installment_count'];
        $totalCents = (int) round(((float) $data['total_amount']) * 100);
        $baseCents = intdiv($totalCents, $count);
        $remainderCents = $totalCents - ($baseCents * $count);

        $nextNumber = (int) ($contract->installments()->max('number') ?? 0) + 1;
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

        $contract->installments()->createMany($installments);

        return redirect()->route('contracts.show', $contract)
            ->with('success', "{$count} parcelas geradas com sucesso.");
    }

    public function update(InstallmentRequest $request, Contract $contract, Installment $installment): RedirectResponse
    {
        $this->ensureBelongsToContract($installment, $contract);

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
        $this->ensureBelongsToContract($installment, $contract);

        $installment->delete();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Parcela inativada com sucesso.');
    }

    public function restore(Contract $contract, int $installment): RedirectResponse
    {
        $installment = Installment::withTrashed()->findOrFail($installment);
        $this->ensureBelongsToContract($installment, $contract);

        $installment->restore();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Parcela reativada com sucesso.');
    }
}
