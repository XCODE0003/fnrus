<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Public, no-auth unsubscribe handler. Token is opaque per-user
 * (random 48 chars). Response is GET (browser one-click) and POST
 * (RFC 8058 List-Unsubscribe-Post header).
 */
class UnsubscribeController extends Controller
{
    public function unsubscribe(string $token, Request $request)
    {
        if (!$token || strlen($token) < 16 || strlen($token) > 64) {
            return response('Invalid token', 400);
        }

        $affected = DB::table('users')
            ->where('unsubscribe_token', $token)
            ->update(['email_notify_marketing' => 0]);

        if ($affected === 0) {
            return response('Ссылка недействительна или уже использована.', 200)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        return response(
            "Вы успешно отписались от наших рассылок.\n\n" .
            "Если это произошло по ошибке, зайдите в личный кабинет и включите уведомления обратно.",
            200
        )->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
