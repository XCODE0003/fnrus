<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SenderController;
use App\Jobs\BroadcastStatusChange;
use App\Models\Category;
use App\Models\Member;
use App\Models\RolePermission;
use App\Models\ShopSettings;
use App\Models\StatusCheat;
use App\Models\TelegramChannel;
use Exception;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use GuzzleHttp\Client;

class StatusCheatController extends Controller
{
    public $user;
    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function select_all()
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $results = StatusCheat::get();

            $all = [];

            foreach ($results as $p) {

                $all[] = [
                    'id' => $p->id,
                    'title' => $p->title,
                    'status' => $p->status,
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function all()
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $results = StatusCheat::get();

            $all = [];

            foreach ($results as $p) {

                $all[] = [
                    'id' => $p->id,
                    'title' => $p->title,
                    'status' => $p->status,
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $status = StatusCheat::find($id);
            if (!$status) {
                throw new Exception('Чит не найден.', 1);
            }

            $games = Category::where('visibility', 1)->where('cid', 0)->orderBy('sort')->get();

            $games_all = [];

            foreach ($games as $p) {
                $games_all[] = [
                    'id' => $p->id,
                    'title' => $p->title
                ];
            }

            $status->games = $games_all;

            // Connected Telegram channels — frontend renders quick on/off list.
            $shop = Shop::getDefault();
            $status->channels = $shop
                ? TelegramChannel::where('sid', $shop->id)
                    ->orderBy('sort_order')->orderBy('id')
                    ->get(['id', 'title', 'chat_id', 'type', 'is_active'])
                : [];

            // Public URL for the previously uploaded image, if any.
            $status->image_url = $status->image_path
                ? asset('storage/' . ltrim($status->image_path, '/'))
                : null;

            return response()->json([
                'ok' => true,
                'result' => $status,
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }
    public function games_all(){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $games = Category::where('visibility', 1)->where('cid', 0)->orderBy('sort')->get();

            $games_all = [];

            foreach ($games as $p) {
                $games_all[] = [
                    'id' => $p->id,
                    'title' => $p->title
                ];
            }

            return response()->json([
                'ok' => true,
                'result' => $games_all,
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function create(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.add')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:4|max:255',
                'game_id' => 'required|int|min:1',
                'status' => 'required|int|min:1|max:4',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $sql = [
                'cid' => $request->game_id,
                'title' => $request->title,
                'status' => $request->status,
                'updated_at' => strtotime('NOW')
            ];

            $status = StatusCheat::create($sql);

            if ($status) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $status->id
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update($id, Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.edit')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'title'            => 'required|string|min:4|max:255',
                'game_id'          => 'required|int|min:1',
                'status'           => 'required|int|min:1|max:4',
                'is_notify'        => 'required|int|between:0,1',
                'message_template' => 'nullable|string|max:8000',
                'image_path'       => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $status = StatusCheat::find($id);
            if (!$status) {
                throw new Exception('Чит не найден.', 1);
            }

            $status_db = $status->status;

            $status->cid              = $request->game_id;
            $status->title            = $request->title;
            $status->status           = $request->status;
            $status->message_template = $request->input('message_template');
            $status->image_path       = $request->input('image_path');
            $status->updated_at       = strtotime('NOW');
            $status->save();

            if ($status_db != $request->status && (int) $request->is_notify === 1) {
                $this->dispatchTelegramBroadcast($status, $request->input('message_template'));
            }

            return response()->json([
                'ok' => true,
                'description' => 'Обновлено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    /**
     * Build the rendered message (placeholders applied) and queue a Telegram broadcast.
     * Falls back to a sane default template when none is configured.
     */
    private function dispatchTelegramBroadcast(StatusCheat $status, ?string $template): void
    {
        $game = Category::where('id', $status->cid)->value('title') ?? '';
        $statusLabel = StatusCheat::statusLabel((int) $status->status);

        $template = $template !== null && trim($template) !== ''
            ? $template
            : "<p>\u{267B}\u{FE0F} Изменение статуса софта</p><p>├ Игра: <b>{game}</b></p><p>├ Софт: <b>{product}</b></p><p>└ Статус: <b>{status}</b></p>";

        $publisher = app(\App\Services\TelegramPublisher::class);
        $rendered = $publisher->applyPlaceholders($template, [
            'game'    => $game,
            'product' => $status->title,
            'status'  => $statusLabel,
        ]);

        $imageUrl = $status->image_path
            ? asset('storage/' . ltrim($status->image_path, '/'))
            : null;

        try {
            BroadcastStatusChange::dispatch($rendered, $imageUrl);
        } catch (\Throwable $e) {
            // Fallback: queue not configured, run synchronously so the change still ships.
            Log::warning('StatusCheatController: queue dispatch failed, running sync', [
                'error' => $e->getMessage(),
            ]);
            try {
                $publisher->broadcast($rendered, $imageUrl);
            } catch (\Throwable $inner) {
                Log::error('StatusCheatController: sync broadcast failed', [
                    'error' => $inner->getMessage(),
                ]);
            }
        }
    }

    /**
     * Upload an image attached to a status post. Stored under storage/app/public/status-posts.
     */
    public function uploadImage(Request $request)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.edit')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $path = $request->file('image')->store('status-posts', 'public');

            return response()->json([
                'ok' => true,
                'result' => [
                    'path' => $path,
                    'url'  => asset('storage/' . $path),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'statuses.delete')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $status = StatusCheat::find($id);

            if (!$status) {
                throw new Exception('Чит не найден.', 1);
            }

            $status->delete();

            return response()->json([
                'ok' => true,
                'description' => 'Удалено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
}
