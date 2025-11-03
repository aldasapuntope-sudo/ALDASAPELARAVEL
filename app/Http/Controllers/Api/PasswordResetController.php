<?php

namespace App\Http\Controllers\Api;

use App\Mail\ResetPasswordMail;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        $resetLink = "http://localhost:3000/reset-password?token=$token&email={$request->email}";

        // Enviar correo con diseño personalizado
        Mail::to($request->email)->send(new ResetPasswordMail($resetLink, $request->email));

        return response()->json(['message' => 'Se ha enviado un correo con el enlace para restablecer tu contraseña.']);
    }

    public function validateToken($token, Request $request)
    {
        $record = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->where('email', $request->query('email'))
            ->first();

        if (!$record || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'Token inválido o expirado.'], 400);
        }

        return response()->json(['message' => 'Token válido.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|confirmed|min:6'
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Token inválido.'], 400);
        }

        // Actualiza contraseña
        /*User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);*/
        DB::table('usuario')
        ->where('email', $request->email)
        ->update(['password' => Hash::make($request->password)]);

        // Elimina token usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);

        $status = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$status) {
            return response()->json(['valid' => false, 'message' => 'Token inválido o expirado'], 400);
        }

        return response()->json(['valid' => true, 'message' => 'Token válido']);
    }
}
