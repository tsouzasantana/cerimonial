<?php

namespace App\Http\Controllers;

use App\Http\Requests\OccurrenceTypeRequest;
use App\Models\Contract;
use App\Models\OccurrenceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OccurrenceTypeController extends Controller
{
    public function index(): View
    {
        $types = OccurrenceType::orderBy('name')->paginate(20);
        $statusOptions = Contract::statusOptions();

        return view('occurrence-types.index', compact('types', 'statusOptions'));
    }

    public function create(): View
    {
        $statusOptions = Contract::statusOptions();

        return view('occurrence-types.create', compact('statusOptions'));
    }

    public function store(OccurrenceTypeRequest $request): RedirectResponse
    {
        OccurrenceType::create($request->validated());

        return redirect()->route('occurrence-types.index')
            ->with('success', 'Tipo de ocorrência cadastrado com sucesso.');
    }

    public function edit(OccurrenceType $occurrenceType): View
    {
        $statusOptions = Contract::statusOptions();

        return view('occurrence-types.edit', ['type' => $occurrenceType, 'statusOptions' => $statusOptions]);
    }

    public function update(OccurrenceTypeRequest $request, OccurrenceType $occurrenceType): RedirectResponse
    {
        $occurrenceType->update($request->validated());

        return redirect()->route('occurrence-types.index')
            ->with('success', 'Tipo de ocorrência atualizado com sucesso.');
    }

    public function destroy(OccurrenceType $occurrenceType): RedirectResponse
    {
        $occurrenceType->delete();

        return redirect()->route('occurrence-types.index')
            ->with('success', 'Tipo de ocorrência removido.');
    }
}
