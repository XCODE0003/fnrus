<?php

namespace App\Http\Controllers;

use App\Models\HackStatus;
use App\Models\Instruction;
use App\Models\Product;
use App\Models\RolePermission;
use Exception;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InstructionController extends Controller
{
    public $user;
    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function all()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'instructions.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $results = Instruction::get();

            $all = [];

            foreach ($results as $p) {

                $all[] = [
                    'id' => $p->id,
                    'icon' => '<i class="far fa-file mr-1"></i>',
                    'title' => '<a target="_blank" href="/instruction/'.$p->alias.'">'.$p->title.'</a>',
                    'views' => $p->views,
                    'link_share' => '<a data-id="'.$p->id.'" href="javascript:;" id="copy_link_share" onclick="copy(\''.config('app.url').'/instruction/'.$p->alias.'\', 0);" title="Скопировать ссылку" href="javascript:;"><i class="far fa-copy fa-xl"></i></a>',
                    'block_edit' => '<a data-id="'.$p->id.'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#changeInstruction"><i class="far fa-edit fa-xl"></i></a>',
                    'block_delete' => '<a href="#" onclick="deleteInstruction(\'' . $p->id . '\');return false;" title="Удалить" class="text-danger"><i class="far fa-trash fa-xl"></i></a>',
                ];
            }

            return datatables($all)
                ->rawColumns(['icon', 'title', 'link_share','block_edit', 'block_delete'])
                ->make(true);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function list()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'instructions.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $results = Instruction::get();

            $all = [];

            foreach ($results as $p) {

                $all[] = [
                    'id' => $p->id,
                    'title' => $p->title,
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'instructions.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $instruction = Instruction::find($id);

            if (!$instruction) {
                throw new Exception('Инструкция не найдена.', 1);
            }

            $products = Product::where('visibility', 1)->get();

            $products_all = [];

            foreach ($products as $p) {
                if($id != $p->id){

                    $hack_status = HackStatus::getByID($p->hack_status);

                    $status = '';
                    if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                    $products_all[] = [
                        'id' => $p->id,
                        'title' => $p->title.' '.$status
                    ];
                }
            }

            $instruction->products = $products_all;

            return response()->json([
                'ok' => true,
                'result' => $instruction,
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

            $access = RolePermission::getByPermission($this->user->role_id, 'instructions.add')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'pids' => 'required|json',
                'title' => 'required|string|min:4|max:255',
                'body' => 'required|string',
                'alias' => 'required|string|min:2|max:100',
                'buttons' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $check = Instruction::checkAlias($request->alias);
            if($check) {throw new Exception('Такой короткий адрес уже существует.');}

            $sql = [
                'pids' => $request->pids,
                'title' => $request->title,
                'body' => $request->body,
                'alias' => $request->alias,
                'views' => 0,
                'buttons' => $request->buttons,
                'updated_at' => strtotime('NOW')
            ];

            $instruction = Instruction::create($sql);

            if ($instruction) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $instruction->id
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update($id, Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'instructions.edit')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'pids' => 'required|json',
                'title' => 'required|string|min:4|max:255',
                'body' => 'required|string',
                'alias' => 'required|string|min:2|max:100',
                'buttons' => 'nullable|json',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $instruction = Instruction::find($id);

            if (!$instruction) {
                throw new Exception('Инструкция не найдена.', 1);
            }

            if($instruction->alias != $request->alias) {
                $check = Instruction::checkAlias($request->alias);
                if ($check) {
                    throw new Exception('Такой короткий адрес уже существует.');
                }
            }

            $instruction->pids = $request->pids;
            $instruction->title = $request->title;
            $instruction->body = $request->body;
            $instruction->alias = $request->alias;
            $instruction->buttons = $request->buttons;
            $instruction->updated_at = strtotime('NOW');
            $instruction->save();

            return response()->json([
                'ok' => true,
                'description' => 'Обновлено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function check_alias(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:0|max:100',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $apiUrl = 'https://petal.playstone.org/api/v5/text?detect=bg&from=ru&q=' . urlencode($request->title) . '&to=en';

            $headers = [
                'x-playstone-tz: Europe/Moscow',
                'x-playstone-v: 2.9.9',
                'Accept: */*',
                'Accept-Language: en-RU;q=1.0, ru-RU;q=0.9',
                'User-Agent: Translator/2.9.9 (com.playstone.petal; build:414; macOS 13.4.1) Alamofire/5.6.4',
                'x-playstone-l: en',
                'Connection: close'
            ];

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            curl_close($ch);
            $decode = json_decode($response, true);

            $alias = mb_strtolower($decode['primary']['translation_result']);
            $alias = mb_ereg_replace('[^-0-9a-z]', '-', $alias);
            $alias = mb_ereg_replace('[-]+', '-', $alias);
            $alias = trim($alias, '-');

            $check = Product::checkAlias($alias);
            if($check) {throw new Exception('Такой короткий адрес уже существует.');}

            return response()->json([
                'ok' => true,
                'result' => $alias
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function delete($id)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'instructions.delete')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $instruction = Instruction::find($id);

            if (!$instruction) {
                throw new Exception('Инструкция не найдена.', 1);
            }

            $instruction->delete();

            return response()->json([
                'ok' => true,
                'description' => 'Удалено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
}
