<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentTypeRequest;
use App\Models\DocumentType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        $types = DocumentType::withCount('documents')->orderBy('name')->paginate(20);

        return view('document-types.index', compact('types'));
    }

    public function create(): View
    {
        return view('document-types.create');
    }

    public function store(DocumentTypeRequest $request): RedirectResponse
    {
        DocumentType::create($request->validated());

        return redirect()->route('document-types.index')
            ->with('success', 'Tipo de documento cadastrado com sucesso.');
    }

    public function edit(DocumentType $documentType): View
    {
        return view('document-types.edit', ['type' => $documentType]);
    }

    public function update(DocumentTypeRequest $request, DocumentType $documentType): RedirectResponse
    {
        $documentType->update($request->validated());

        return redirect()->route('document-types.index')
            ->with('success', 'Tipo de documento atualizado com sucesso.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        try {
            $documentType->delete();
        } catch (QueryException) {
            return redirect()->route('document-types.index')
                ->with('error', 'Não é possível remover: existem documentos usando este tipo.');
        }

        return redirect()->route('document-types.index')
            ->with('success', 'Tipo de documento removido.');
    }
}
