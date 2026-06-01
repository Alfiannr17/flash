<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'referral_code' => 'nullable|string',
            'terms' => 'accepted', // HARUS DICENTANG
    ], [
        // PESAN ERROR CUSTOM
        'terms.accepted' => 'Anda wajib menyetujui Syarat dan Ketentuan layanan kami sebelum mendaftar!',
    ]);
       

        // 1. Buat User Baru
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'balance' => 0
        ]);

        // 2. Bikinkan Kode Referral untuk dia sendiri
        $user->update([
            'referral_code' => 'FLASH' . $user->id . strtoupper(\Illuminate\Support\Str::random(3))
        ]);

        // 3. Catat siapa yang ngajak (Upline)
        if ($request->referral_code) {
            $upline = User::where('referral_code', strtoupper($request->referral_code))->first();
            if ($upline) {
                $user->update(['referred_by' => $upline->id]);
            }
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect('/dashboard'); 
    
    }
}
