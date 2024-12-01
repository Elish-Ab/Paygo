<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function create_user(Request $request){
        $user = $request->validate();
        User::create($user);
        return "created";
    }
}
