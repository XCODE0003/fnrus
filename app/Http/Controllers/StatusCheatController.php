<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SenderController;
use App\Models\Category;
use App\Models\Member;
use App\Models\RolePermission;
use App\Models\ShopSettings;
use App\Models\StatusCheat;
use Exception;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
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
                'title' => 'required|string|min:4|max:255',
                'game_id' => 'required|int|min:1',
                'status' => 'required|int|min:1|max:4',
                'is_notify' => 'required|int|between:0,1',
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

            $status->cid = $request->game_id;
            $status->title = $request->title;
            $status->status = $request->status;
            $status->updated_at = strtotime('NOW');
            $status->save();

            if($status_db != $request->status) {

                if ($request->status == 1) {
                    $block_status = 'Рекомендуем к использованию';
                }
                if ($request->status == 2) {
                    $block_status = 'Не рекомендуем к использованию';
                }
                if ($request->status == 3) {
                    $block_status = 'На обновлении';
                }
                if ($request->status == 4) {
                    $block_status = 'На свой страх и риск';
                }

                $title = $request->title;

                if($request->is_notify == 1) {

//                    $members = Member::where('email', '!=', '')->get();
//
//                    foreach ($members as $m) {
//
//                        if($m->email_notify_status_changed == 1) {
//                            Mail::send('emails.status-changed', ['title' => $title, 'status' => $block_status], function ($message) use ($m, $title) {
//                                $message->to($m->email, $m->username)
//                                    ->subject('Изменился статус: ' . $title);
//                            });
//                        }
//                    }

                    $senderController = app(SenderController::class);
                    $started_at = date('Y-m-d\TH:i');

                    $message = "<p>♻️Изменение статус чита</p><p>├ Чит: <strong>".$title."</strong></p><p>└ Статус: <strong>".$block_status."</strong></p>";

                    $newRequest = new \Illuminate\Http\Request();
                    $newRequest->merge([
                        'type' => 1,
                        'title' => 'Изменение статуса: ' . $title,
                        'message' => $message,
                        'buttons' => '[]',
                        'disable_web_page_preview' => 0,
                        'has_spoiler' => 0,
                        'type_time' => '0',
                        'started_at' => $started_at,
                    ]);
                    $response = $senderController->create($newRequest);
                }
            }


            return response()->json([
                'ok' => true,
                'description' => 'Обновлено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
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
