<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminIndukToAdminPanel
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = Filament::auth()->user();

        if ($user instanceof User && $user->isAdminInduk()) {
            return redirect()->to('/admin');
        }

        return $next($request);
    }
}
