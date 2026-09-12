<?php

namespace App\Http\Middleware;

use App\Models\Contract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPublicContractAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');
        $contract = Contract::where('public_token', $token)->firstOrFail();

        if (! session()->get("public_verified_{$contract->id}")) {
            return redirect()->route('public.gate', $token);
        }

        $request->attributes->set('publicContract', $contract);

        return $next($request);
    }
}
