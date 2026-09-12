<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '') {
            return view('search.index', [
                'term' => $term,
                'clients' => collect(),
                'contracts' => collect(),
                'vendors' => collect(),
            ]);
        }

        $clients = Client::query()
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('document', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        $contracts = Contract::query()
            ->with('client')
            ->where(function ($query) use ($term) {
                $query->whereHas('client', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhere('event_location', 'like', "%{$term}%")
                    ->when(is_numeric($term), fn ($q) => $q->orWhere('id', (int) $term));
            })
            ->orderByDesc('event_date')
            ->limit(20)
            ->get();

        $vendors = Vendor::query()
            ->with('contract.client')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('document', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return view('search.index', [
            'term' => $term,
            'clients' => $clients,
            'contracts' => $contracts,
            'vendors' => $vendors,
        ]);
    }
}
