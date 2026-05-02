<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\Shop;
use App\Models\Tariff;
use Exception;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class MaterialController extends Controller
{
    public $user;
    public $currencies = ['₽','$','€'];

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

    public function add(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'materials.add')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'pid' => 'required|int|min:1',
                'tid' => 'required|int|between:1,1000'
            ]);

            if(!$request->filled($request->body)){
                $validator = Validator::make($request->all(), [
                    'body' => 'required|string|min:5',
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'file' => 'required|string',
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = DB::table('products')
                ->where('sid', $shop->id)
                ->where('id', $request->pid)
                ->first();

            if(!$product){
                throw new Exception('Product not found!', 1);
            }

            $count = 0;

            $dir = str_replace('app/Http/Controllers', '', __dir__);

            if(!$request->filled($request->file)){
                $file = substr($request->file, 1);
                $r = DB::table('attachments')->where('id', $file)->first();
                if(!$r){throw new Exception('File not found!', 1);}
                $count = count(file($dir.'storage/app/public/files/'.$r->id.'.'.$r->ext));
            }

            $materials = explode("\n", $request->body);

            if($count > 0){
                $materials = file($dir.'storage/app/public/files/'.$r->id.'.'.$r->ext);
            }

            $success = 0;

            foreach ($materials as $material) {

                $body = trim($material);

                $date_now = Carbon::now()->timestamp;

                $sql = [
                    'sid' => $shop->id,
                    'pid' => $request->pid,
                    'tid' => $request->tid,
                    'eid' => 0,
                    'oid' => 0,
                    'bid' => 0,
                    'body' => htmlspecialchars($body, ENT_QUOTES),
                    'status' => 1,
                    'created_at' => $date_now,
                ];

                if(DB::table('materials')->insert($sql)){
                   $success++;
                }

            }

            if($success > 0){
                DB::table('products')
                    ->where('sid', $shop->id)
                    ->where('id', $request->pid)
                    ->increment('count_all', $success);

                return response()->json([
                    'ok' => true,
                    'description' => 'Material '.$success.' added'
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }

    public function export(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'materials.export')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'remove_from_stock' => 'required|int|between:0,1',
                'tid' => 'required|int|between:1,1000',
                'pid' => 'required|int|min:1',
                'count' => 'required|int|between:1,1000',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = DB::table('products')
                ->where('sid', $shop->id)
                ->where('id', $request->pid)
                ->first();

            if(!$product){
                throw new Exception('Product not found!', 1);
            }

            $materials_count = DB::table('materials')
                ->where('sid', $shop->id)
                ->where('tid', $request->tid)
                ->where('pid', $request->pid)
                ->where('status', 1)
                ->count();

            if($materials_count == 0){
                throw new Exception('No content available', 1);
            }

            if($request->count > $materials_count){
                throw new Exception('Available only '.$materials_count.' materials', 1);
            }

            $materials = DB::table('materials')
                ->where('sid', $shop->id)
                ->where('tid', $request->tid)
                ->where('pid', $request->pid)
                ->where('status', 1)
                ->limit($request->count)
                ->get();

            if($request->remove_from_stock == 0){$status_remove_from_stock = 1;}
            if($request->remove_from_stock == 1){$status_remove_from_stock = 3;}

            $success = 0;

            $body = [];

            $date_now = Carbon::now()->timestamp;

            $tariff = Tariff::getByID($shop->id, $request->pid, $request->tid);

            $sql = [
                'sid' => $shop->id,
                'tid' => $request->tid,
                'pid' => $request->pid,
                'title' => $product->title,
                'title_tariff' => Tariff::num_decline($tariff->title, ['день','дня','дней'] ),
                'is_stock' => $request->remove_from_stock,
                'count_all' => $request->count,
                'created_at' => $date_now
            ];

            $mexport_id = DB::table('materials_exports')->insertGetId($sql);

            foreach ($materials as $material) {

                $sql = ['eid' => $mexport_id];

                if($request->remove_from_stock == 1) {
                    $sql = ['status' => 3, 'eid' => $mexport_id];
                }

                DB::table('materials')
                    ->where('id', $material->id)
                    ->update($sql);

                $body[] = $material->body;
                $success++;
            }

            if($request->remove_from_stock == 1) {
                DB::table('products')
                    ->where('sid', $shop->id)
                    ->where('id', $request->pid)
                    ->decrement('count_all', $request->count);
            }

            if($success > 0){
                return response()->json([
                    'ok' => true,
                    'description' => 'Exported '.$success.' materials',
                    'result' => [
                        'export_id' => $mexport_id,
                        'body' => implode("\n", $body)
                    ]
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }


    public function all(Request $request){
        try {

            $start = $request->input('start');
            $length = $request->input('length');
            $search_term = $request->input('search.value');
            $order_column = $request->input('order.0.column');
            $order_dir = $request->input('order.0.dir'); // направление сортировки (asc или desc)
            $column_name = $request->input('columns.' . $order_column . '.data');
            $cat_id = $request->input('cat_id');

            $access = RolePermission::getByPermission($this->user->role_id, 'materials.all')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Магазин не найден.');
            }

            $materials = Material::query();

            $materials = $materials->where('sid', $shop->id);
            
            $column_name = $column_name ?: 'created_at';
            $order_dir = $order_dir ?: 'desc';

            $materials = $materials->orderBy($column_name, $order_dir);

            $total_count = Material::count();

            if ($search_term) {
                $materials = $materials->where(function($query) use ($search_term) {
                    $query->where('body', 'LIKE', '%' . $search_term . '%');
                });
            }

            $filtered_count = $materials->count();
            $materials = $materials->offset($start)->limit($length)->get();
        
            $all = [];

            foreach ($materials as $m) {

                    if ($m->status == 1) {
                        $block_status = '<span class="badge bg-success px-2" style="color: #000">В наличии</span>';
                    }

                    if ($m->status == 2) {
                        $block_status = '<span class="badge bg-warning px-2" style="color: #000">Выкуплено</span>';
                    }

                    if ($m->status == 3) {
                        $block_status = '<span class="badge bg-secondary px-2" style="color: #000">Выгружено</span>';
                    }

                    $product = Product::where('sid', $shop->id)->where('id', $m->pid)->first();

                    $product_title = 'Неизвестно';
                    if ($product) {$product_title = $product->title;}

                    $tariff = Tariff::getByID($shop->id, $m->pid, $m->tid);

                    $block_tariff = 'Неизвестно';
                    if($tariff){$block_tariff = Tariff::num_decline($tariff->title, ['день','дня','дней'] );}

                    $all[] = [
                        'icon' => '<i class="fal fa-shopping-bag"></i>',
                        'title' => $product_title,
                        'tariff' => $block_tariff,
                        'body' => $m->body,
                        'status' => $block_status,
                        'block_delete' => '<span class="d-block text-center"><a href="javascript:;" style="color:#adadad"  onclick="remove(\'material\', ' . $m->id . ');return false;"><i class="fal fa-trash text-danger"></i></a></span>'
                    ];
            }

            return response()->json(['data' => $all, 'recordsTotal' => $total_count, 'recordsFiltered' => $filtered_count]);

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }

    public function all_trash(){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $materials = Material::where('sid', $shop->id)->orderBy('created_at', 'DESC')->get();

            $all = [];

            foreach ($materials as $m) {

                $product = Product::where('sid', $shop->id)->where('id', $m->pid)->first();

                $product_title = 'Неизвестно';
                if ($product) {$product_title = $product->title;}

                if(!$product){
                    Material::where('id', $m->id)->delete();
                    $all[]['status'] = 'deleted';
                }

                $tariff = Tariff::getByID($shop->id, $m->pid, $m->tid);

                $block_tariff = 'Неизвестно';
                if($tariff){$block_tariff = Tariff::num_decline($tariff->title, ['день','дня','дней'] );}

                $all[] = [
                    'id' => $m->id,
                    'title' => $product_title,
                ];
            }

            return response()->json($all);

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }

    public function delete($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'materials.delete')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $material = Material::where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$material) {
                throw new Exception('Материал не найден.', 1);
            }

            $mat = Material::where('id', $id)
                ->delete();

            if($mat) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Удалено'
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }


//
//    public function export_to_member(Request $request){
//        try {
//
//            $validator = Validator::make($request->all(), [
//                'pid' => 'required|int|min:1',
//                'bid' => 'required|int|min:1',
//                'count' => 'required|int|between:1,1000',
//            ]);
//
//            if ($validator->fails()) {
//                foreach ($validator->errors()->all() as $message) {
//                    throw new Exception($message, 1);
//                }
//            }
//
//            $shop = DB::table('shops')
//                ->where('uid', $this->user->id)
//                ->first();
//
//            if(!$shop){
//                throw new Exception('Shop not found!', 1);
//            }
//
//            $product = DB::table('products')
//                ->where('sid', $shop->id)
//                ->where('id', $request->pid)
//                ->first();
//
//            if(!$product){
//                throw new Exception('Product not found!', 1);
//            }
//
//            $materials_count = DB::table('materials')
//                ->where('sid', $shop->id)
//                ->where('pid', $request->pid)
//                ->where('status', 1)
//                ->count();
//
//            if($materials_count == 0){
//                throw new Exception('No content available', 1);
//            }
//
//            if($request->count > $materials_count){
//                throw new Exception('Available only '.$materials_count.' materials', 1);
//            }
//
//            $materials = DB::table('materials')
//                ->where('sid', $shop->id)
//                ->where('pid', $request->pid)
//                ->where('status', 1)
//                ->limit($request->count)
//                ->get();
//
//            $success = 0;
//
//            $body = [];
//
//            $date_now = Carbon::now()->timestamp;
//
//            $sql = [
//                'sid' => $shop->id,
//                'pid' => $request->pid,
//                'title' => $product->title,
//                'is_stock' => 1,
//                'count_all' => $request->count,
//                'created_at' => $date_now
//            ];
//
//            $mexport_id = DB::table('materials_exports')->insertGetId($sql);
//
//            foreach ($materials as $material) {
//
//                $sql = ['bid' => $request->bid, 'status' => 3, 'eid' => $mexport_id];
//
//                DB::table('materials')
//                    ->where('id', $material->id)
//                    ->update($sql);
//
//                $body[] = $material->body;
//                $success++;
//            }
//
//                DB::table('products')
//                    ->where('sid', $shop->id)
//                    ->where('id', $request->pid)
//                    ->decrement('count_all', $request->count);
//
//            if($success > 0){
//                return response()->json([
//                    'ok' => true,
//                    'description' => 'Exported '.$success.' materials',
//                    'result' => [
//                        'export_id' => $mexport_id,
//                        'body' => implode("\n", $body)
//                    ]
//                ]);
//            }
//
//        } catch (Exception $e){
//            return response()->json([
//                'ok' => false,
//                'error_code' => $e->getCode(),
//                'description' => $e->getMessage()
//            ]);
//        }
//    }

}
