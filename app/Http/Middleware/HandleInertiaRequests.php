<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
        ],
        'flash' => [
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
        ],
        // INI OBAT ANTI CRASH-NYA BOS 👇 (Kita paksa kirim data)
        'errors' => function () use ($request) {
            $errors = $request->session()->get('errors');
            // Kalau gak ada error, paksa kirim object kosong biar Vue gak undefined
            return $errors ? $errors->getBag('default')->getMessages() : (object)[];
        },
        'settings' => [
            // Kita siapin dua-duanya biar JavaScript lu milih yang mana yang dia butuh
            'api' => config('services.pakasir.api_key', env('PAKASIR_API_KEY', '')), 
            'pakasir_api' => config('services.pakasir.api_key', env('PAKASIR_API_KEY', '')),
        ],
    ]);
}
}