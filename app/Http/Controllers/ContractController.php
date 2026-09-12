<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Mail\ContractPdfMail;
use App\Models\Client;
use App\Models\Contract;
use App\Models\DocumentType;
use App\Models\Installment;
use App\Models\OccurrenceType;
use App\Models\Service;
use App\Models\VendorServiceType;
use App\Support\ChecklistQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $contracts = Contract::query()
            ->with('client')
            ->when($request->boolean('trashed'), fn ($query) => $query->onlyTrashed())
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = "%{$request->string('search')}%";
                $query->whereHas('client', fn ($q) => $q->where('name', 'like', $term));
            })
            ->orderByDesc('event_date')
            ->paginate(15)
            ->withQueryString();

        $statusOptions = Contract::statusOptions();

        return view('contracts.index', compact('contracts', 'statusOptions'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();
        $statusOptions = Contract::statusOptions();

        return view('contracts.create', compact('clients', 'statusOptions'));
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['discount'] = $data['discount'] ?? 0;

        $contract = Contract::create($data);
        $contract->recalculateTotals();
        $contract->applyChecklistTemplate();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrato criado com sucesso.');
    }

    public function show(Contract $contract, Request $request): View
    {
        $contract->load([
            'client',
            'items.service',
            'installments' => fn ($q) => $q->orderBy('number'),
            'documents.documentType',
            'occurrences' => fn ($q) => $q->with('type')->orderByDesc('occurrence_date'),
            'tasks',
            'vendors' => fn ($q) => $q->with(['vendorServiceType', 'documents.documentType']),
        ]);

        $services = Service::where('active', true)->orderBy('name')->get();
        $occurrenceTypes = OccurrenceType::orderBy('name')->get();
        $paymentMethods = Installment::paymentMethodOptions();
        $installmentStatuses = Installment::statusOptions();
        $documentTypes = DocumentType::orderBy('name')->get();
        $vendorServiceTypes = VendorServiceType::orderBy('name')->get();
        $checklistTasks = ChecklistQuery::forContract($contract, $request);

        return view('contracts.show', compact(
            'contract',
            'services',
            'occurrenceTypes',
            'paymentMethods',
            'installmentStatuses',
            'documentTypes',
            'vendorServiceTypes',
            'checklistTasks',
        ));
    }

    public function edit(Contract $contract): View
    {
        $clients = Client::orderBy('name')->get();
        $statusOptions = Contract::statusOptions();
        $hasChecklistTasks = $contract->tasks()->exists();

        return view('contracts.edit', compact('contract', 'clients', 'statusOptions', 'hasChecklistTasks'));
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();
        $data['discount'] = $data['discount'] ?? 0;

        $oldEventDate = $contract->event_date->copy();

        $contract->update($data);
        $contract->recalculateTotals();

        $newEventDate = $contract->event_date;

        if (! $oldEventDate->isSameDay($newEventDate) && $request->boolean('shift_checklist_dates')) {
            $deltaDays = $oldEventDate->diffInDays($newEventDate, false);
            $contract->shiftChecklistDates((int) $deltaDays);
        }

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrato atualizado com sucesso.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato inativado com sucesso.');
    }

    public function restore(int $contract): RedirectResponse
    {
        Contract::withTrashed()->findOrFail($contract)->restore();

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato reativado com sucesso.');
    }

    public function regeneratePublicLink(Contract $contract): RedirectResponse
    {
        $contract->regeneratePublicToken();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Novo link público gerado. O link anterior deixou de funcionar.');
    }

    public function generatePdf(Contract $contract): RedirectResponse
    {
        $contract->load(['client', 'items.service', 'installments']);

        $pdf = Pdf::loadView('pdf.contract', compact('contract'));
        $path = "contracts/pdf/contrato-{$contract->id}.pdf";

        Storage::disk('local')->put($path, $pdf->output());
        $contract->update(['pdf_path' => $path]);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'PDF do contrato gerado com sucesso.');
    }

    public function downloadPdf(Contract $contract): StreamedResponse
    {
        abort_unless($contract->pdf_path && Storage::disk('local')->exists($contract->pdf_path), 404, 'Gere o PDF do contrato antes de baixá-lo.');

        return Storage::disk('local')->download($contract->pdf_path, "contrato-{$contract->id}.pdf");
    }

    public function sendContractEmail(Contract $contract): RedirectResponse
    {
        $contract->load(['client', 'items.service', 'installments']);

        if (! $contract->client->email) {
            return back()->with('error', 'O cliente não possui e-mail cadastrado.');
        }

        if (! $contract->pdf_path || ! Storage::disk('local')->exists($contract->pdf_path)) {
            $pdf = Pdf::loadView('pdf.contract', compact('contract'));
            $path = "contracts/pdf/contrato-{$contract->id}.pdf";
            Storage::disk('local')->put($path, $pdf->output());
            $contract->update(['pdf_path' => $path]);
        }

        Mail::to($contract->client->email)->send(new ContractPdfMail($contract));

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrato enviado por e-mail para o cliente.');
    }
}
