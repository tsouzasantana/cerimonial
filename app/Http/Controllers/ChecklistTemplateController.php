<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChecklistTemplateRequest;
use App\Models\ChecklistTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChecklistTemplateController extends Controller
{
    public function index(): View
    {
        $templates = ChecklistTemplate::orderByDesc('days_offset')->paginate(20);

        return view('checklist-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('checklist-templates.create');
    }

    public function store(ChecklistTemplateRequest $request): RedirectResponse
    {
        ChecklistTemplate::create($request->validated());

        return redirect()->route('checklist-templates.index')
            ->with('success', 'Tarefa padrão cadastrada com sucesso.');
    }

    public function edit(ChecklistTemplate $checklistTemplate): View
    {
        return view('checklist-templates.edit', ['template' => $checklistTemplate]);
    }

    public function update(ChecklistTemplateRequest $request, ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        $checklistTemplate->update($request->validated());

        return redirect()->route('checklist-templates.index')
            ->with('success', 'Tarefa padrão atualizada com sucesso.');
    }

    public function destroy(ChecklistTemplate $checklistTemplate): RedirectResponse
    {
        $checklistTemplate->delete();

        return redirect()->route('checklist-templates.index')
            ->with('success', 'Tarefa padrão removida. Contratos já criados não são afetados.');
    }
}
