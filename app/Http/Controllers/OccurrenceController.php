<?php

namespace App\Http\Controllers;

use App\Http\Requests\OccurrenceRequest;
use App\Models\Contract;
use App\Models\Occurrence;
use App\Models\OccurrenceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OccurrenceController extends Controller
{
    public function downloadAttachment(Contract $contract, Occurrence $occurrence): StreamedResponse
    {
        abort_unless($occurrence->contract_id === $contract->id, 404);
        abort_unless($occurrence->attachment_path && Storage::disk('local')->exists($occurrence->attachment_path), 404);

        return Storage::disk('local')->download($occurrence->attachment_path, $occurrence->attachment_original_name);
    }

    public function store(OccurrenceRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();

        $path = null;
        $originalName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('occurrences', 'local');
            $originalName = $file->getClientOriginalName();
        }

        $contract->occurrences()->create([
            'occurrence_type_id' => $data['occurrence_type_id'],
            'user_id' => $request->user()->id,
            'occurrence_date' => $data['occurrence_date'],
            'deadline' => $data['deadline'] ?? null,
            'description' => $data['description'],
            'attachment_path' => $path,
            'attachment_original_name' => $originalName,
        ]);

        $type = OccurrenceType::find($data['occurrence_type_id']);
        if ($type && $type->contract_status) {
            $contract->update(['status' => $type->contract_status]);
        }

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'ocorrencias'])
            ->with('success', 'Ocorrência registrada com sucesso.');
    }

    public function destroy(Contract $contract, Occurrence $occurrence): RedirectResponse
    {
        abort_unless($occurrence->contract_id === $contract->id, 404);

        $occurrence->delete();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'ocorrencias'])
            ->with('success', 'Ocorrência inativada com sucesso.');
    }

    public function restore(Contract $contract, int $occurrence): RedirectResponse
    {
        $occurrence = Occurrence::withTrashed()->findOrFail($occurrence);
        abort_unless($occurrence->contract_id === $contract->id, 404);

        $occurrence->restore();

        return redirect()->route('contracts.show', ['contract' => $contract, 'tab' => 'ocorrencias'])
            ->with('success', 'Ocorrência reativada com sucesso.');
    }
}
