<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentFileRequest;
use App\Mail\DocumentFileMail;
use App\Models\DocumentFile;
use App\Models\DocumentType;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentFileController extends Controller
{
    public function index(Request $request): View
    {
        $documents = DocumentFile::query()
            ->with(['client', 'contract.client', 'vendor', 'documentType'])
            ->when($request->boolean('trashed'), fn ($query) => $query->onlyTrashed())
            ->when($request->filled('document_type_id'), fn ($query) => $query->where('document_type_id', $request->integer('document_type_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(function ($query) use ($term) {
                    $query->where('title', 'like', $term)
                        ->orWhereHas('contract.client', fn ($query) => $query->where('name', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $documentTypes = DocumentType::orderBy('name')->get();

        return view('documents.index', compact('documents', 'documentTypes'));
    }

    public function store(DocumentFileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $vendor = ! empty($data['vendor_id']) ? Vendor::find($data['vendor_id']) : null;

        $attributes = [
            'client_id' => $data['client_id'] ?? null,
            'contract_id' => $vendor?->contract_id ?? $data['contract_id'] ?? null,
            'vendor_id' => $vendor?->id,
            'uploaded_by' => $request->user()->id,
            'uploaded_by_type' => 'admin',
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

        return back()->with('success', 'Documento salvo com sucesso.');
    }

    public function download(DocumentFile $document): StreamedResponse
    {
        abort_if($document->isLink(), 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    public function sendEmail(DocumentFile $document): RedirectResponse
    {
        $document->load(['client', 'contract.client']);

        $email = $document->client?->email ?? $document->contract?->client?->email;

        if (! $email) {
            return back()->with('error', 'Não há e-mail de cliente associado a este documento.');
        }

        try {
            Mail::to($email)->queue(new DocumentFileMail($document));
        } catch (\Throwable $e) {
            Log::error('Falha ao enfileirar e-mail do documento: '.$e->getMessage());

            return back()->with('error', 'Não foi possível enviar o e-mail agora. Tente novamente em alguns minutos.');
        }

        return back()->with('success', 'Documento será enviado por e-mail em instantes.');
    }

    public function destroy(DocumentFile $document): RedirectResponse
    {
        $document->delete();

        return back()->with('success', 'Documento inativado com sucesso. Ele pode ser recuperado depois.');
    }

    public function restore(int $document): RedirectResponse
    {
        DocumentFile::withTrashed()->findOrFail($document)->restore();

        return back()->with('success', 'Documento reativado com sucesso.');
    }
}
