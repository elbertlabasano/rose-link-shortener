<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        return view('tokens', ['tokens' => $request->user()->tokens()->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:80']);
        $token = $request->user()->createToken($data['name'], ['links:manage'], now()->addYear());

        return to_route('tokens.index')->with('new_token', $token->plainTextToken);
    }

    public function destroy(Request $request, string $token)
    {
        $request->user()->tokens()->findOrFail($token)->delete();

        return to_route('tokens.index')->with('status', 'API token revoked.');
    }
}
