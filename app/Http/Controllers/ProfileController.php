<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json(['data' => ['user' => null]]);
    }

    public function update(Request $request)
    {
        return response()->json(['message' => 'profile updated']);
    }

    public function updateAvatar(Request $request)
    {
        return response()->json(['message' => 'avatar updated']);
    }
}
