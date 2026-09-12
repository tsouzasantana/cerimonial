<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicDocumentRequest;
use App\Http\Requests\PublicTaskUpdateRequest;
use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\DocumentFile;
use App\Models\DocumentType;
use App\Support\ChecklistQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicContractController extends Controller
{
    private function resolveContract(string $token): Contract
    {
        return Contract::where('public_token', $token)->firstOrFail();
    }

    public function gate(Request $request, string $token): View|RedirectResponse
    {
        $contract = $this->resolveContract($token);

        if (session()->get("public_verified_{$contract->id}")) {
            return redirect()->route('public.show', $token);
        }

        return view('public.gate', compact('contract', 'token'));
    }

    public function verify(Request $request, string $token): RedirectResponse
    {
        $contract = $this->resolveContract($token);
        $contract->loadMissing('client');

        $data = $request->validate([
            'document' => ['required', 'string'],
        ]);

        $registered = preg_replace('/\D/', '', (string) $contract->client->document);
        $submitted = preg_replace('/\D/', '', $data['document']);

        if (empty($registered) || $submitted === '' || $submitted !== $registered) {
            throw ValidationException::withMessages([
                'document' => 'CPF não confere com o cadastro. Confira o número e tente novamente.',
            ]);
        }

        session()->put("public_verified_{$contract->id}", true);

        return redirect()->route('public.show', $token);
    }

    public function show(Request $request, string $token): View
    {
        $contract = $request->attributes->get('publicContract');

        $contract->load([
            'client',
            'items.service',
            'installments' => fn ($q) => $q->orderBy('number'),
            'documents.documentType',
            'occurrences' => fn ($q) => $q->with('type')->orderByDesc('occurrence_date'),
            'tasks',
        ]);

        $documentTypes = DocumentType::orderBy('name')->get();
        $checklistTasks = ChecklistQuery::forContract($contract, $request);

        return view('public.show', compact('contract', 'token', 'documentTypes', 'checklistTasks'));
    }

    public function updateTaskStatus(Request $request, string $token, ContractTask $task): JsonResponse
    {
        $contract = $request->attributes->get('publicContract');
        abort_unless($task->contract_id === $contract->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContractTask::statusOptions()))],
        ]);

        $task->update(['status' => $data['status']]);

        return response()->json([
            'status' => $task->status,
            'status_label' => ContractTask::statusOptions()[$task->status],
            'is_overdue' => $task->isOverdue(),
        ]);
    }

    public function updateTask(PublicTaskUpdateRequest $request, string $token, ContractTask $task): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        abort_unless($task->contract_id === $contract->id, 404);

        $task->update($request->validated());

        return redirect()->route('public.show', ['token' => $token, 'tab' => 'checklist'])
            ->with('success', 'Tarefa atualizada.');
    }

    public function downloadDocument(Request $request, string $token, DocumentFile $document): StreamedResponse
    {
        $contract = $request->attributes->get('publicContract');
        abort_unless($document->contract_id === $contract->id || $document->client_id === $contract->client_id, 404);
        abort_if($document->isLink(), 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    public function storeDocument(PublicDocumentRequest $request, string $token): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        $data = $request->validated();

        $attributes = [
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'uploaded_by' => null,
            'document_type_id' => $data['document_type_id'],
            'title' => $data['title'],
        ];

        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');

            $attributes += [
                'original_filename' => $uploaded->getClientOriginalName(),
                'path' => $uploaded->store('documents', 'local'),
                'mime_type' => $uploaded->getClientMimeType(),
                'size' => $uploaded->getSize(),
            ];
        } else {
            $attributes['url'] = $data['url'];
        }

        DocumentFile::create($attributes);

        return redirect()->route('public.show', ['token' => $token, 'tab' => 'documentos'])
            ->with('success', 'Documento enviado com sucesso.');
    }
}
