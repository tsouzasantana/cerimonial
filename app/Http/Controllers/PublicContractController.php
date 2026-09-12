<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractTaskRequest;
use App\Http\Requests\PublicDocumentRequest;
use App\Http\Requests\PublicTaskUpdateRequest;
use App\Http\Requests\PublicVendorUpdateRequest;
use App\Http\Requests\VendorRequest;
use App\Models\Contract;
use App\Models\ContractTask;
use App\Models\DocumentFile;
use App\Models\DocumentType;
use App\Models\Vendor;
use App\Models\VendorServiceType;
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
            'vendors' => fn ($q) => $q->with(['vendorServiceType', 'documents.documentType']),
        ]);

        $documentTypes = DocumentType::orderBy('name')->get();
        $vendorServiceTypes = VendorServiceType::orderBy('name')->get();
        $checklistTasks = ChecklistQuery::forContract($contract, $request);

        return view('public.show', compact('contract', 'token', 'documentTypes', 'vendorServiceTypes', 'checklistTasks'));
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

    public function storeTask(ContractTaskRequest $request, string $token): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        $maxOrder = (int) $contract->tasks()->max('sort_order');

        $contract->tasks()->create($request->validated() + [
            'status' => ContractTask::STATUS_A_INICIAR,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('public.show', ['token' => $token, 'tab' => 'checklist'])
            ->with('success', 'Tarefa adicionada ao checklist.');
    }

    public function destroyTask(Request $request, string $token, ContractTask $task): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        abort_unless($task->contract_id === $contract->id, 404);

        $task->delete();

        return redirect()->route('public.show', ['token' => $token, 'tab' => 'checklist'])
            ->with('success', 'Tarefa inativada.');
    }

    public function downloadDocument(Request $request, string $token, DocumentFile $document): StreamedResponse
    {
        $contract = $request->attributes->get('publicContract');
        abort_unless($document->contract_id === $contract->id || $document->client_id === $contract->client_id, 404);
        abort_if($document->isLink(), 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    public function storeVendor(VendorRequest $request, string $token): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        $data = $request->validated();
        $data['vendor_service_type_id'] = VendorServiceType::resolveId(
            $data['vendor_service_type_id'] ?? null,
            $data['new_service_type'] ?? null,
        );
        unset($data['new_service_type']);

        $contract->vendors()->create($data);

        return redirect()->route('public.show', ['token' => $token, 'tab' => 'fornecedores'])
            ->with('success', 'Fornecedor cadastrado com sucesso.');
    }

    public function updateVendor(PublicVendorUpdateRequest $request, string $token, Vendor $vendor): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        abort_unless($vendor->contract_id === $contract->id, 404);

        $vendor->update($request->validated());

        return redirect()->route('public.show', ['token' => $token, 'tab' => 'fornecedores'])
            ->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroyVendor(Request $request, string $token, Vendor $vendor): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        abort_unless($vendor->contract_id === $contract->id, 404);

        $vendor->delete();

        return redirect()->route('public.show', ['token' => $token, 'tab' => 'fornecedores'])
            ->with('success', 'Fornecedor inativado com sucesso.');
    }

    public function storeDocument(PublicDocumentRequest $request, string $token): RedirectResponse
    {
        $contract = $request->attributes->get('publicContract');
        $data = $request->validated();

        $vendor = null;
        if (! empty($data['vendor_id'])) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            abort_unless($vendor->contract_id === $contract->id, 404);
        }

        $attributes = [
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'vendor_id' => $vendor?->id,
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

        return redirect()->route('public.show', ['token' => $token, 'tab' => $vendor ? 'fornecedores' : 'documentos'])
            ->with('success', 'Documento enviado com sucesso.');
    }
}
