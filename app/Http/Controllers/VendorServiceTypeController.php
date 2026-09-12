<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorServiceTypeRequest;
use App\Models\VendorServiceType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorServiceTypeController extends Controller
{
    public function index(): View
    {
        $types = VendorServiceType::withCount('vendors')->orderBy('name')->paginate(20);

        return view('vendor-service-types.index', compact('types'));
    }

    public function create(): View
    {
        return view('vendor-service-types.create');
    }

    public function store(VendorServiceTypeRequest $request): RedirectResponse
    {
        VendorServiceType::create($request->validated());

        return redirect()->route('vendor-service-types.index')
            ->with('success', 'Tipo de serviço cadastrado com sucesso.');
    }

    public function edit(VendorServiceType $vendorServiceType): View
    {
        return view('vendor-service-types.edit', ['type' => $vendorServiceType]);
    }

    public function update(VendorServiceTypeRequest $request, VendorServiceType $vendorServiceType): RedirectResponse
    {
        $vendorServiceType->update($request->validated());

        return redirect()->route('vendor-service-types.index')
            ->with('success', 'Tipo de serviço atualizado com sucesso.');
    }

    public function destroy(VendorServiceType $vendorServiceType): RedirectResponse
    {
        try {
            $vendorServiceType->delete();
        } catch (QueryException) {
            return redirect()->route('vendor-service-types.index')
                ->with('error', 'Não é possível remover: existem fornecedores usando este tipo.');
        }

        return redirect()->route('vendor-service-types.index')
            ->with('success', 'Tipo de serviço removido.');
    }
}
