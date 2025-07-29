<?php


namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'message' => $status === Password::RESET_LINK_SENT
                ? 'Reset link sent successfully.'
                : 'Unable to send reset link.',
            'status' => $status,
        ], $status === Password::RESET_LINK_SENT ? 200 : 400);
    }
}



