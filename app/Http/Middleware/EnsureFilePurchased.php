<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gate `/file/{hash}` so only paying customers can download.
 *
 * Three pass paths (any one is enough):
 *
 *  1. The current session has the file id whitelisted via
 *     `access.files`. Populated on visit to /delivery/{deliveryHash} —
 *     covers Telegram bot buyers who click the deeplink without ever
 *     logging in on the website.
 *
 *  2. The current JWT (`session_token` cookie or Authorization header)
 *     belongs to a user whose role >= config('admin.min_role_id') —
 *     admins/moderators can preview any file.
 *
 *  3. The current JWT belongs to a user whose Telegram chat_id
 *     (new_users.tid) is the buyer (`new_orders.bid`) of an order whose
 *     materials/instructions reference this file id.
 *
 * Otherwise: abort(404). 404 instead of 403 so an attacker cannot
 * enumerate which file ids exist by probing.
 */
class EnsureFilePurchased
{
    public function handle(Request $request, Closure $next)
    {
        $hash = (string) $request->route('hash', '');
        if ($hash === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $hash)) {
            abort(404);
        }

        // Path 1: session-based grant from /delivery/{hash} visit.
        $granted = (array) $request->session()->get('access.files', []);
        if (in_array($hash, $granted, true)) {
            return $next($request);
        }

        // Resolve current user via JWT cookie or header (does not require
        // the auth middleware to be active on the route).
        $user = $this->resolveUser($request);

        if ($user) {
            $minRole = (int) config('admin.min_role_id', 1);
            if ((int) ($user->role_id ?? 0) >= $minRole) {
                return $next($request); // Path 2: admin
            }

            if ($this->userOwnsFile($user, $hash)) {
                return $next($request); // Path 3: purchaser
            }
        }

        abort(404);
    }

    private function resolveUser(Request $request): ?object
    {
        $token = $request->cookie('session_token') ?: null;
        if (!$token) {
            $auth = (string) $request->header('Authorization', '');
            if (str_starts_with($auth, 'Bearer ')) {
                $token = substr($auth, 7);
            }
        }
        if (!$token) {
            return null;
        }
        try {
            $user = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($token)->authenticate();
            return $user ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function userOwnsFile(object $user, string $hash): bool
    {
        $tid = (int) ($user->tid ?? 0);
        if ($tid <= 0) {
            return false;
        }

        // Approach: scan instructions/materials whose body references
        // /file/{hash}, then verify the user has bought one of the
        // products tied to those instructions/orders.
        $like = '%/file/' . $hash . '%';

        // Instructions reference products via a JSON `pids` column.
        $instruction = DB::table('instructions')
            ->where('body', 'like', $like)
            ->select('pids')
            ->first();

        if ($instruction) {
            $pids = (array) json_decode((string) $instruction->pids, true);
            $pids = array_filter(array_map('intval', $pids));
            if (!empty($pids)) {
                $hit = DB::table('products_purchased')
                    ->where('chat_id', $tid)
                    ->whereIn('product_id', $pids)
                    ->exists();
                if ($hit) {
                    return true;
                }
            }
        }

        // Materials are linked to a specific order; the buyer is bid.
        $material = DB::table('materials')
            ->where('body', 'like', $like)
            ->select('oid')
            ->first();

        if ($material) {
            $hit = DB::table('orders')
                ->where('id', $material->oid)
                ->where('bid', $tid)
                ->exists();
            if ($hit) {
                return true;
            }
        }

        return false;
    }
}
