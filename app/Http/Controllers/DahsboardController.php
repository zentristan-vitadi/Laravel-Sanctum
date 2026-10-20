<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DahsboardController extends Controller
{
    public function register(Request $r)
    {
        $res = Http::acceptJson()->post(config('services.api.url') . '/register', $r->all());
        if ($res->status() === 422) {
            return back()->withErrors($res->json('errors'))
                ->withInput($r->except('password', 'password_confirmation'));
        }
        return redirect('/login')->with('success', 'Register berhasil, silakan login');
    }

    public function login(Request $r)
    {
        $res = Http::acceptJson()->post(config('services.api.url') . '/login', $r->only('email', 'password'));
        if ($res->status() === 422) {
            return back()->withErrors($res->json('errors'))->withInput($r->only('email'));
        }
        if ($res->failed()) {
            return back()->withErrors(['email' => $res->json('message')])->withInput($r->only('email'));
        }
        session(['api_token' => $res->json('token')]);   // token hanya di session server
        return redirect('/dashboard');
    }

    public function dashboard()
    {
        $http = Http::withToken(session('api_token'))->acceptJson();
        $profil  = $http->get(config('services.api.url') . '/profil');
        $catatan = $http->get(config('services.api.url') . '/catatan');
        if ($profil->status() === 401) {          // token sudah dicabut / kedaluwarsa
            session()->flush();
            return redirect('/login');
        }
        return view('dashboard', ['user' => $profil->json(), 'catatan' => $catatan->json()]);
    }

    public function logout()
    {
        Http::withToken(session('api_token'))->acceptJson()->post(config('services.api.url') . '/logout');
        session()->flush();
        return redirect('/login');
    }
}
