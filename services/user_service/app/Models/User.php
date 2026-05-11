<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    /*
    =====================================
    FORGOT PASSWORD
    =====================================
    */

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        /*
        =====================================
        CEK EMAIL USER
        =====================================
        */

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan'
            ], 404);
        }

        /*
        =====================================
        KIRIM LINK RESET PASSWORD
        =====================================
        */

        $status = Password::sendResetLink([
            'email' => $request->email
        ]);

        if ($status === Password::RESET_LINK_SENT) {

            return response()->json([
                'message' => 'Link reset password berhasil dikirim'
            ]);
        }

        return response()->json([
            'message' => 'Gagal mengirim link reset password'
        ], 500);
    }
}