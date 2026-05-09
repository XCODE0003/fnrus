<?php

namespace App\Http\Controllers;

use App\Models\Attach;
use App\Models\Instruction;
use App\Models\Product;
use App\Models\RolePermission;
use Exception;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FaqController extends Controller
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
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }

    public function all()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'faq.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $faq = Faq::orderBy('sort')->get();

            $all = [];

            foreach ($faq as $p) {

                if($p['visibility'] == 0){$icon = 'fa-eye';}
                if($p['visibility'] == 1){$icon = 'fa-eye-slash';}

                $block_instruction = 'По умолчанию';
                if($p->in_id > 0){
                    $in = Instruction::getByID($p->in_id);
                    if($in) {
                        $block_instruction = '<a href="/instruction/' . $in->alias . '">' . $in->title . '</a>';
                    }
                }

                $all[] = [
                    'id' => $p->id,
                    'instruction' => $block_instruction,
                    'icon' => '<i class="far fa-question-circle mr-1"></i>',
                    'question' => '<span style="max-width: 308px;display: block;overflow: hidden;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;">'.$p->question.'</span>',
                    'block_move' => '<input type="hidden" name="sort[]" value="'.$p->id.'"><a href="javascript:;"><i class="fal fa-arrows handle"></i></a>',
                    'block_visibility' => '<a onclick="visibility(\'faq\','.$p->id.');return false;" title="Скрыть " href="javascript:;"><i class="far '.$icon.' fa-xl"></i></a>',
                    'block_edit' => '<a data-id="'.$p->id.'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#changeFaq"><i class="far fa-edit fa-xl"></i></a>',
                    'block_delete' => '<a href="javascript:;" title="Удалить" class="text-danger" onclick="deleteFaq('.$p->id.');return false;"><i class="far fa-trash fa-xl"></i></a>',
                ];
            }

            return datatables($all)
                ->rawColumns(['icon', 'instruction', 'question', 'block_move', 'block_visibility', 'block_edit', 'block_delete'])
                ->make(true);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function sort(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'faq.sort')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            if(!$request->filled('sort')){
                throw new Exception('Сортировка не определена.');
            }

            foreach ($request->sort as $i => $row) {
                $sql = ['sort' => ++$i];
                Faq::where('id', intval($row))->update($sql);
            }

            return response()->json([
                'ok' => true,
                'description' => 'Перемещено'
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'faq.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $faq = Faq::find($id);

            if (!$faq) {
                throw new Exception('FAQ не найден.', 1);
            }

            return response()->json([
                'ok' => true,
                'result' => $faq,
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

            $access = RolePermission::getByPermission($this->user->role_id, 'faq.add')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'in_id' => 'required|int|min:0',
                'question' => 'required|string|min:4|max:255',
                'answer' => 'nullable|string',
                'visibility' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $sql = [
                'in_id' => $request->in_id,
                'question' => $request->question,
                'answer' => $request->answer,
                'sort' => 0,
                'visibility' => $request->visibility,
                'updated_at' => strtotime('NOW')
            ];

            $faq = Faq::create($sql);

            if ($faq) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $faq->id
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update($id, Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'faq.edit')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'in_id' => 'required|int|min:0',
                'question' => 'required|string|min:4|max:255',
                'answer' => 'required|string',
                'visibility' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $faq = Faq::find($id);

            if (!$faq) {
                throw new Exception('FAQ не найден.', 1);
            }

            $faq->in_id = $request->in_id;
            $faq->question = $request->question;
            $faq->answer = $request->answer;
            $faq->visibility = $request->visibility;
            $faq->updated_at = strtotime('NOW');
            $faq->save();

            return response()->json([
                'ok' => true,
                'description' => 'Обновлено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public function visibility($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'faq.visibility')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $faq = Faq::find($id);

            if (!$faq) {
                throw new Exception('FAQ не найден.', 1);
            }

            if($faq->visibility == 0){$val = 1;}else{$val = 0;}

            $update = Faq::where('id', $id)->update(['visibility' => $val]);

            if($update) {
                if($val == 1){$msg = 'Вопрос снова общедоступен';}
                if($val == 0){$msg = 'Вопрос скрыт из общего доступа';}

                return response()->json([
                    'ok' => true,
                    'description' => $msg
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function delete($id)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'faq.delete')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $faq = Faq::find($id);

            if (!$faq) {
                throw new Exception('FAQ не найден.', 1);
            }

            $faq->delete();

            return response()->json([
                'ok' => true,
                'description' => 'Удалено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

}
