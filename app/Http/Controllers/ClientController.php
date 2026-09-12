<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Client::query()
            ->when($request->boolean('trashed'), fn ($query) => $query->onlyTrashed())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');
                $term = "%{$search}%";
                $matchingIds = Client::idsMatchingDocument($search);
                $query->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->when($matchingIds !== [], fn ($q) => $q->orWhereIn('id', $matchingIds)));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function show(Client $client): View
    {
        $client->load([
            'contracts' => fn ($q) => $q->orderByDesc('event_date'),
            'documents.documentType',
        ]);

        $documentTypes = DocumentType::orderBy('name')->get();

        return view('clients.show', compact('client', 'documentTypes'));
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Cliente inativado com sucesso.');
    }

    public function restore(int $client): RedirectResponse
    {
        Client::withTrashed()->findOrFail($client)->restore();

        return redirect()->route('clients.index')
            ->with('success', 'Cliente reativado com sucesso.');
    }
}
