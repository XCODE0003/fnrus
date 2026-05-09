<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\Shop;
use App\Models\TelegramChannel;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TelegramChannelController extends Controller
{
    public $user;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $this->user = Auth::user();
            return $next($request);
        });
    }

    private function canOrThrow(string $permission): void
    {
        $row = RolePermission::getByPermission($this->user->role_id, $permission);
        if (!$row || !$row->allow) {
            throw new Exception('Access Denied');
        }
    }

    public function index(): JsonResponse
    {
        try {
            $this->canOrThrow('telegram_channels.list');

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Shop not found');
            }

            $rows = TelegramChannel::where('sid', $shop->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'title', 'chat_id', 'type', 'is_active', 'sort_order']);

            return response()->json(['ok' => true, 'result' => $rows]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('telegram_channels.create');

            $validator = Validator::make($request->all(), [
                'title'   => 'required|string|min:1|max:255',
                'chat_id' => ['required', 'string', 'max:64', 'regex:/^(@[A-Za-z0-9_]{4,}|-?\d+)$/'],
                'type'    => 'nullable|in:channel,group',
            ], [
                'chat_id.regex' => 'chat_id должен быть @username или числовым id (например, -1001234567890)',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Shop not found');
            }

            $exists = TelegramChannel::where('sid', $shop->id)
                ->where('chat_id', $request->chat_id)
                ->exists();
            if ($exists) {
                throw new Exception('Этот канал уже добавлен.');
            }

            $row = TelegramChannel::create([
                'sid'        => $shop->id,
                'title'      => $request->title,
                'chat_id'    => $request->chat_id,
                'type'       => $request->input('type', 'channel'),
                'is_active'  => true,
                'sort_order' => (int) (TelegramChannel::where('sid', $shop->id)->max('sort_order') ?? 0) + 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            return response()->json(['ok' => true, 'description' => 'Канал добавлен', 'result' => $row]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('telegram_channels.edit');

            $validator = Validator::make($request->all(), [
                'title'   => 'nullable|string|min:1|max:255',
                'chat_id' => ['nullable', 'string', 'max:64', 'regex:/^(@[A-Za-z0-9_]{4,}|-?\d+)$/'],
                'type'    => 'nullable|in:channel,group',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $row = TelegramChannel::find($id);
            if (!$row) {
                throw new Exception('Канал не найден');
            }

            if ($request->filled('title'))   $row->title = $request->title;
            if ($request->filled('chat_id')) $row->chat_id = $request->chat_id;
            if ($request->filled('type'))    $row->type = $request->type;
            $row->updated_at = time();
            $row->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    /** Quick on/off toggle without entering edit modal. */
    public function toggle(int $id): JsonResponse
    {
        try {
            $this->canOrThrow('telegram_channels.edit');

            $row = TelegramChannel::find($id);
            if (!$row) {
                throw new Exception('Канал не найден');
            }
            $row->is_active = !$row->is_active;
            $row->updated_at = time();
            $row->save();

            return response()->json([
                'ok'        => true,
                'is_active' => (bool) $row->is_active,
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->canOrThrow('telegram_channels.delete');

            $row = TelegramChannel::find($id);
            if (!$row) {
                throw new Exception('Канал не найден');
            }
            $row->delete();

            return response()->json(['ok' => true, 'description' => 'Удалено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
}
