<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController
{

    public function create(Request $request){

        $date_now = strtotime('NOW');

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        $s = [
            'pid' => 0,
            'tid' => 0,
            'sid' => 0,
            'username' => $username,
            'password' => Hash::make($password),
            'is_ban' => 0,
            'is_policy' => 0,
            'is_adm' => 0,
            'updated_at' => $date_now,
            'created_at' => $date_now
        ];

        if(DB::table('admins')->insert($s)){
            return response()->json(['ok' => true, 'redirect' => '/main'], 200);
        }
    }

    public function login(Request $request){

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        $u = DB::table('admins')->where('username', $username)->first();

        if ($u && Hash::check($password, $u->password)) {
            return response()->json(['ok' => true], 200);
        }

        return response()->json(['ok' => false, 'description' => 'Invalid credentials'], 200);
    }
}
