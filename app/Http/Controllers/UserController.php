<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function create_user(Request $request)
    {
        // Validate the request data

        $validatedData = $request->validate([
            'name' => 'required|string|max:25',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:15',
            'telegram_id' => 'nullable|string|max:20',
            'balance' => 'nullable|numeric',
            'is_verified' => 'required|boolean',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        // Hash the password before saving
        $validatedData['password'] = bcrypt($validatedData['password']);

        // Create the user
        User::create($validatedData);

        return response()->json(['message' => 'User created successfully'], 201);
    }
}
