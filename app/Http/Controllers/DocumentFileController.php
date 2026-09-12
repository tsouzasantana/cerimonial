<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentFileRequest;
use App\Mail\DocumentFileMail;
use App\Models\DocumentFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentFileController extends Controller
{
    public function index(Request $request): View
    {
        $documents = DocumentFile::query()
            ->with(['client', 'contract'])
            ->when($request->boolean('trashed'), fn ($query) => $query->onlyTrashed())
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $categories = DocumentFile::categoryOptions();

        return view('documents.index', compact('documents', 'categories'));
    }

    public function store(DocumentFileRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $uploaded = $request->file('file');

        $path = $uploaded->store('documents', 'local');

        DocumentFile::create([
            'client_id' => $data['client_id'] ?? null,
            'contract_id' => $data['contract_id'] ?? null,
            'uploaded_by' => $request->user()->id,
            'title' => $data['title'],
            'category' => $data['category'],
            'original_filename' => $uploaded->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize(),
        ]);

        return back()->with('success', 'Documento enviado com sucesso.');
    }

    public function download(DocumentFile $document): StreamedResponse
    {
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

        Mail::to($email)->send(new DocumentFileMail($document));

        return back()->with('success', 'Documento enviado por e-mail com sucesso.');
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
