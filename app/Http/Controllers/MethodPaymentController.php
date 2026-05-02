<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\MethodPayment;
use App\Models\PaymentAsset;
use App\Models\PaymentSystem;
use App\Models\RolePermission;
use App\Models\ShopSettings;
use Exception;
use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Yajra\Datatables\Facades\Datatables;

class MethodPaymentController extends Controller
{
    public $user;
    private $set_shop;
    private $main_currency;
    private $currency_symbol;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();

                $this->set_shop = ShopSettings::getDefault();
                $this->main_currency = $this->set_shop->currency;
                $this->shop_currency = ['RUB' => '₽', 'USD' => '$'];

                if ($this->user) {
                    $this->currency_symbol = $this->shop_currency[$this->user->currency];
                    $this->currency_code = $this->user->currency;
                } else {
                    $this->currency_symbol = $this->shop_currency[$this->set_shop->currency];
                    $this->currency_code = $this->set_shop->currency;
                }

                return $next($request);
            });

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
            ]);
        }
    }

    public function info($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = MethodPayment::where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $product->id,
                    'title' => $product->title
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }

    public function fullinfo($type){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $method = MethodPayment::where('sid', $shop->id)
                ->where('type', $type)
                ->first();

            $system = PaymentSystem::where('id', $method->psid)->first();

            if (!$method) {
                throw new Exception('Метод оплаты не найден.', 1);
            }

            $result = [
                'id' => $system->id,
                'title' => $system->title,
                'public_id' => $method->public_id,
                'public_key' => $method->public_key,
                'secret_key' => $method->secret_key,
                'secret_key_two' => $method->secret_key_two,
                'theme_code' => $method->theme_code,
                'assets' => json_decode($method->assets, true),
                'type' => $method->type,
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }


    public function exists($type){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $method = MethodPayment::where('sid', $shop->id)
                ->where('type', $type)
                ->first();

            if (!$method) {
                throw new Exception('Метод оплаты не найден.', 1);
            }

            return response()->json(['ok' => true]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false
            ]);
        }
    }

    public function visibility(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            if($request->filled($request->id)){
                throw new Exception('ID не найден.');
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('sid', $shop->id)
                ->where('id', $request->id)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            if($product->visibility == 0){$val = 1;}else{$val = 0;}

            $update = Product::where('id', $request->id)
                ->update(['visibility' => $val]);

            if($update) {
                if($val == 1){$msg = 'Товар снова общедоступен';}
                if($val == 0){$msg = 'Товар скрыт из общего доступа';}

                return response()->json([
                    'ok' => true,
                    'description' => $msg
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

    public function delete(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            if($request->filled($request->id)){throw new Exception('ID не найден.');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('sid', $shop->id)
                ->where('id', $request->id)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            $delete = Product::where('id', $request->id)
                ->delete();

            if($delete) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Удалено'
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

    public function sort(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            if($request->filled($request->sort)){
                throw new Exception('Сортировка не определена.');
            }

            foreach ($request->sort as $i => $row) {
                $sql = ['sort' => ++$i];
                DB::table('products')->where('id', intval($row))->update($sql);
            }

            return response()->json([
                'ok' => true,
                'description' => 'Перемещено'
            ]);

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
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Shop not found!');
            }

            $products = Product::where('sid', $shop->id)
                ->orderBy('sort')
                ->get();

            $all = [];

            foreach ($products as $p) {

                $cat = Category::where('sid', $shop->id)->where('id', $p->cid)->first();

                $block_cat = 'Без категории';
                if ($p->cid != 0) {
                    $block_cat = $cat->title;
                }

                $block_count_sales = '<span>' . $p->count_sales . '</span>';
                if ($p->count_sales > 0) {
                    $block_count_sales = '<span class="text-success">' . $p->count_sales . '</span>';
                }

                $block_count_all = '<span class="text-danger">' . $p->count_all . '</span>';
                if ($p->count_all > 0)
                    if ($p->is_endless == 1) {
                        $block_count_all = '<span class="text-success">∞</span>';
                    } else {
                        $block_count_all = '<span class="text-success">' . $p->count_all . '</span>';
                    }

                $all[] = [
                    'id' => $p->id,
                    'icon' => '<i class="far fa-shopping-bag"></i>',
                    'title' => $p->title,
                    'cid' => $block_cat,
                    'price' => $p->price . ' ₽',
                    'count_sales' => $block_count_sales,
                    'count_all' => $block_count_all,
                    'count_views' => $p->count_views,
                    'is_endless' => $p->is_endless,
                    'sort' => $p->sort,
                    'visibility' => $p->visibility,
                    'link_share' => 'https://t.me/' . $shop->username . '?start=product-' . $p->id
                ];
            }

            return datatables($all)
                ->addColumn('block_move', function ($row) {
                    return '<a href="javascript:;"><i class="fal fa-arrows handle"></i></a>';
                })
                ->addColumn('block_add_material', function ($row) {
                    if ($row['is_endless'] == 0) {
                        $block_params = 'data-toggle="modal" data-target="#addMaterial"';
                    }
                    if ($row['is_endless'] == 1) {
                        $block_params = 'class="text-gray-700"';
                    }
                    return '<a data-id="' . $row['id'] . '" ' . $block_params . ' href="javascript:;" title="Добавить" class=""><i class="fas fa-plus fa-xl"></i></a>';
                })
                ->addColumn('block_export', function ($row) {
                    if ($row['is_endless'] == 0) {
                        $block_params = 'data-toggle="modal" data-target="#exportMaterials"';
                    }
                    if ($row['is_endless'] == 1) {
                        $block_params = 'class="text-gray-700"';
                    }
                    return '<a data-id="' . $row['id'] . '" ' . $block_params . ' href="javascript:;" title="Выгрузить материал"> <i class="far fa-cloud-download fa-xl"></i></a>';
                })
                ->addColumn('block_link_share', function ($row) {
                    return '<a data-id="' . $row['id'] . '" data-link="' . $row['link_share'] . '" id="copy_link_share" onclick="copy(\'' . $row['link_share'] . '\', 0);" title="Скопировать ссылку" href="javascript:;"><i class="far fa-copy fa-xl"></i></a>';
                })
                ->addColumn('block_visibility', function ($row) {
                    if ($row['visibility'] == 0) {
                        $icon = 'fa-eye';
                    }
                    if ($row['visibility'] == 1) {
                        $icon = 'fa-eye-slash';
                    }
                    return '<a onclick="visibility(\'product\',' . $row['id'] . ');return false;" title="Скрыть товар" href="javascript:;"><i class="far ' . $icon . ' fa-xl"></i></a>';
                })
                ->addColumn('block_edit', function ($row) {
                    return '<a data-id="' . $row['id'] . '" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editProduct"><i class="far fa-edit fa-xl"></i></a>';
                })
                ->addColumn('block_delete', function ($row) {
                    return '<a onclick="remove(\'product\',' . $row['id'] . ');return false;" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a><input type="hidden" name="sort[]" value="' . $row['id'] . '">';
                })
                ->rawColumns(['icon', 'count_sales', 'count_all', 'block_move', 'block_add_material', 'block_export', 'block_link_share', 'block_visibility', 'block_edit', 'block_delete'])
                ->make(true);
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function create(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'cid' => 'required|int|min:0',
                'title' => 'required|string|min:4|max:100',
                'description' => 'string',
                'price' => 'required|numeric|between:0,99999.99',
                'currency' => 'required|int|between:0,2',
                'count_min' => 'required|int|between:1,9999',
                'count_max' => 'required|int|between:0,1',
                'count_type' => 'required|int|between:1,2',
                'is_endless' => 'required|int|between:0,1',
                'visibility' => 'required|int|between:0,1',
            ]);

            if($request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_no_image')){
                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
            }

            if(!$request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_image')){
                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_image').' символов.');
            }


            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $description = '';
            $image_db = '';

            if(!$request->filled($request->description)){$description = $request->description;}
            if(!$request->filled($request->image)){
                $image_db = substr($request->image, 1);
                $r = DB::table('attachments')->where('id', $image_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }


            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            if($request->cid != 0) {
                $category = DB::table('categories')
                    ->where('sid', $shop->id)
                    ->where('id', $request->cid)
                    ->first();

                if (!$category) {
                    throw new Exception('Категория не найдена.', 1);
                }
            }

            if($request->is_endless == 1){
                throw new Exception('Чтобы включить "Бесконечный товар" добавьте один материал.', 1);
            }

            $sql = [
                'cid' => $request->cid,
                'sid' => $shop->id,
                'title' => $request->title,
                'description' => $description,
                'image' => $image_db,
                'image_spoiler' => 0,
                'price' => $request->price,
                'currency' => $request->currency,
                'count_views' => 0,
                'count_min' => $request->count_min,
                'count_max' => $request->count_max,
                'count_sales' => 0,
                'count_all' => 0,
                'count_type' => $request->count_type,
                'is_endless' => $request->is_endless,
                'sort' => 0,
                'visibility' => $request->visibility,
                'updated_at' => $date_now,
                'created_at' => $date_now,
            ];

            $pid = DB::table('products')->insertGetId($sql);

            if ($pid) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $pid
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'type' => 'required|string|min:2|max:2',
                'action' => 'required|string|min:2|max:10',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $type = $request->get('type');
            $action = $request->get('action');
            $assets = $request->get('assets');

            $date_now = strtotime('NOW');

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $sid = $shop->id;
            $public_id = 0;
            $public_key = '';
            $secret_key_two = '';
            $secret_key = '';
            $theme_code = '';
            $assets = '[]';

            $system = PaymentSystem::where('type', $type)->first();
            $method = MethodPayment::where('sid', $sid)->where('psid', $system->id)->first();

            if($type == 'qw') {
                if (!$method) {

                    $validator = Validator::make($request->all(), [
                        'phone' => 'required|int|min:10',
                        'password' => 'required|string|min:4|max:50',
                        'theme_code' => 'required|string|min:4|max:50',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    $theme_code = $request->get('theme_code');

                } else if ($method) {

                    if($action == 'info') {
                        $validator = Validator::make($request->all(), [
                            'public_key' => 'required|string|min:100|max:300',
                            'secret_key' => 'required|string|min:100|max:300',
                            'theme_code' => 'required|string|min:4|max:50',
                        ]);

                        if ($validator->fails()) {
                            foreach ($validator->errors()->all() as $message) {
                                throw new Exception($message, 1);
                            }
                        }

                        $sql = [
                            'public_key' => $request->get('public_key'),
                            'secret_key' => $request->get('secret_key'),
                            'theme_code' => $request->get('theme_code'),
                            'updated_at' => $date_now
                        ];

                        $public_key = $request->get('public_key');
                        $secret_key = $request->get('secret_key');
                    }

                    if($action == 'status'){

                        $method = MethodPayment::where('type', $type)->first();
                        if($method->active == 0){$active = 1;}
                        if($method->active == 1){$active = 0;}

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];

                    }
                }


            }
            if($type == 'bt' || $type == 'bn' || $type == 'sp') {
                if($action == 'info') {
                    $validator = Validator::make($request->all(), [
                        'public_key' => 'required|string|min:5|max:300',
                        'secret_key' => 'required|string|min:5|max:300',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'public_key' => $request->get('public_key'),
                            'secret_key' => $request->get('secret_key'),
                            'updated_at' => $date_now
                        ];
                    } else {
                        $public_key = $request->get('public_key');
                        $secret_key = $request->get('secret_key');
                    }

                }

                if($action == 'status'){
                    $method = MethodPayment::where('type', $type)->first();
                    if($method->active == 0){$active = 1;}
                    if($method->active == 1){$active = 0;}

                    $sql = [
                        'active' => $active,
                        'updated_at' => $date_now
                    ];

                }
            }
            if($type == 'cp') {
                if($action == 'info') {

                    $validator = Validator::make($request->all(), [
                        'public_id' => 'required|string|min:1|max:200',
                        'secret_key' => 'required|string|min:4|max:200',
                        'secret_key_two' => 'required|string|min:4|max:200',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'public_id' => $request->get('public_id'),
                            'secret_key' => $request->get('secret_key'),
                            'secret_key_two' => $request->get('secret_key_two'),
                            'updated_at' => $date_now
                        ];
                    } else {
                        $public_id = $request->get('public_id');
                        $secret_key = $request->get('secret_key');
                        $secret_key_two = $request->get('secret_key_two');
                    }

                }

                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }
            if($type == 'et' || $type == 'ai') {
                if($action == 'info') {

                    $validator = Validator::make($request->all(), [
                        'public_id' => 'required|string|min:1|max:200',
                        'secret_key' => 'required|string|min:4|max:200',
                        'secret_key_two' => 'required|string|min:4|max:200',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'public_id' => $request->get('public_id'),
                            'public_key' => $request->get('public_key') ?? '',
                            'secret_key' => $request->get('secret_key'),
                            'secret_key_two' => $request->get('secret_key_two'),
                            'updated_at' => $date_now
                        ];
                    } else {
                        $public_id = $request->get('public_id');
                        $public_key = $request->get('public_key') ?? '';
                        $secret_key = $request->get('secret_key');
                        $secret_key_two = $request->get('secret_key_two');
                    }

                }

                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }
            if($type == 'fk') {
                if($action == 'info') {

                    $validator = Validator::make($request->all(), [
                        'public_id' => 'required|string|min:1|max:200',
                        'secret_key' => 'required|string|min:4|max:200',
                        'secret_key_two' => 'required|string|min:4|max:200',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'public_id' => $request->get('public_id'),
                            'public_key' => $request->get('public_key') ?? '',
                            'secret_key' => $request->get('secret_key'),
                            'secret_key_two' => $request->get('secret_key_two'),
                            'updated_at' => $date_now
                        ];
                    } else {
                        $public_id = $request->get('public_id');
                        $public_key = $request->get('public_key') ?? '';
                        $secret_key = $request->get('secret_key');
                        $secret_key_two = $request->get('secret_key_two');
                    }

                }

                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }
            if($type == 'lv' || $type == 'ap' || $type == 'rk' || $type == 'ym' || $type == 'po') {
                if($action == 'info') {
                    $validator = Validator::make($request->all(), [
                        'public_id' => 'required|string|min:1|max:100',
                        'secret_key' => 'required|string|min:4|max:200',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'public_id' => $request->get('public_id'),
                            'secret_key' => $request->get('secret_key'),
                            'updated_at' => $date_now
                        ];
                    } else {
                        $public_id = $request->get('public_id');
                        $secret_key = $request->get('secret_key');
                    }

                }

                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }
            if($type == 'pp') {
                if($action == 'info') {
                    $validator = Validator::make($request->all(), [
                        'public_id' => 'required|string|min:1|max:100',
                        'secret_key' => 'required|string|min:4|max:200',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'public_id' => $request->get('public_id'),
                            'secret_key' => $request->get('secret_key'),
                            'public_key' => $request->get('public_key') ?? '',
                            'secret_key_two' => $request->get('secret_key_two') ?? '',
                            'updated_at' => $date_now
                        ];
                    } else {
                        $public_id = $request->get('public_id');
                        $secret_key = $request->get('secret_key');
                        $public_key = $request->get('public_key') ?? '';
                        $secret_key_two = $request->get('secret_key_two') ?? '';
                    }

                }

                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }
            if($type == 'sm') {
                if($action == 'info') {
                    $validator = Validator::make($request->all(), [
                        'secret_key' => 'required|string|min:4|max:300',
                        'public_id' => 'nullable|string|max:300',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'secret_key' => $request->get('secret_key'),
                            'public_id' => $request->get('public_id'),
                            'updated_at' => $date_now
                        ];
                    } else {
                        $secret_key = $request->get('secret_key');
                        $public_id = $request->get('public_id');
                    }

                }

                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }
            if($type == 'cb') {
                if($action == 'info') {
                    $validator = Validator::make($request->all(), [
                        'secret_key' => 'required|string|min:4|max:200',
                        'assets' => "required|array",
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'secret_key' => $request->get('secret_key'),
                            'assets' => json_encode($request->get('assets')),
                            'updated_at' => $date_now
                        ];
                    } else {
                        $assets = json_encode($request->get('assets'));
                        $secret_key = $request->get('secret_key');
                    }

                }

                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }
            if($type == 'ts') {
                if($action == 'info') {

                    $validator = Validator::make($request->all(), [
                        'secret_key' => 'required|string|min:4|max:200',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $message) {
                            throw new Exception($message, 1);
                        }
                    }

                    if($method) {
                        $sql = [
                            'secret_key' => $request->get('secret_key'),
                            'secret_key_two' => $request->get('secret_key_two') ?? '0',
                            'updated_at' => $date_now
                        ];
                    } else {
                        $secret_key = $request->get('secret_key');
                        $secret_key_two = $request->get('secret_key_two') ?? '0';
                    }

                }
                if($action == 'status'){
                    $system = PaymentSystem::where('type', $type)->first();
                    if($system->active == 0){
                        throw new Exception('Способ оплаты отключен!');
                    }

                    $method = MethodPayment::where('type', $type)->first();
                    if(!$method) {
                        return response()->json(['ok' => true, 'action' => 'open_modal', 'type' => $type]);
                    }

                    if($method) {
                        if ($method->active == 0) {
                            $active = 1;
                        }
                        if ($method->active == 1) {
                            $active = 0;
                        }

                        $sql = [
                            'active' => $active,
                            'updated_at' => $date_now
                        ];
                    }

                }
            }

            if($method) {
                $result = MethodPayment::where('sid', $shop->id)->where('type', $type)->update($sql);
            } else {
                $sql = [
                    'sid' => $sid,
                    'psid' => $system->id,
                    'pid' => $system->pid,
                    'public_id' => $public_id,
                    'public_key' => $public_key,
                    'secret_key' => $secret_key,
                    'secret_key_two' => $secret_key_two,
                    'theme_code' => $theme_code,
                    'assets' => $assets,
                    'type' => $type,
                    'active' => 1,
                    'updated_at' => $date_now
                ];

                $result = MethodPayment::where('sid', $shop->id)->create($sql);
            }

            if ($result) {
                // Auto-register Telegram webhook for Stars bot when token is saved.
                // Without this, pre_checkout_query and successful_payment never reach the server
                // after the bot token is changed, breaking Stars payments entirely.
                if ($type == 'ts' && $action == 'info') {
                    $stars_token = $request->get('secret_key');
                    $secret_token = hash('sha256', $stars_token);
                    $webhook_url = config('app.url') . '/telegram/webhook';

                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => 'https://api.telegram.org/bot' . $stars_token . '/setWebhook',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_POSTFIELDS => http_build_query([
                            'url' => $webhook_url,
                            'secret_token' => $secret_token,
                            'allowed_updates' => json_encode(['message', 'pre_checkout_query']),
                            'drop_pending_updates' => 'true',
                            'max_connections' => 40,
                        ]),
                    ]);
                    $tg_response = curl_exec($ch);
                    $tg_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    \Log::info('Stars bot setWebhook called', [
                        'http_code' => $tg_http,
                        'response' => $tg_response,
                        'webhook_url' => $webhook_url,
                    ]);

                    $tg_data = json_decode($tg_response, true);
                    if (!is_array($tg_data) || empty($tg_data['ok'])) {
                        return response()->json([
                            'ok' => false,
                            'description' => 'Токен сохранён, но не удалось зарегистрировать Telegram webhook: '
                                . ($tg_data['description'] ?? 'unknown error'),
                        ]);
                    }
                }

                return response()->json(['ok' => true, 'description' => 'Сохранено']);
            }

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function selectAll()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $products = DB::table('products')->where('sid', $shop->id)->orderBy('sort')->get();

            $all = [];

            foreach ($products as $p) {
                $all[] = [
                    'id' => $p->id,
                    'title' => $p->title
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }


    public function psAll(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'price' => 'nullable|numeric|between:0,99999.99',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $price = Currency::convert($this->currency_code, $this->main_currency, $request->price);

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $payment_systems = PaymentSystem::where('active', 1)->orderBy('id')->get();

            $all = [];

            foreach ($payment_systems as $p) {

                if($p->active == 1) {

                    $method = MethodPayment::where('psid', $p->id)->where('sid', $shop->id)->first();

                    if(!$method || $method->active != 1) {
                        continue;
                    }

                    $method_assets = PaymentAsset::where('psid', $p->id)->where('active', 1)->get();

                    $assets = [];

                    foreach ($method_assets as $asset) {

                        $min = $asset->min;
                        $max = $asset->max;

                        // Convert min/max from asset currency to user's currency for frontend display
                        $min_user = Currency::convert($asset->currency, $this->currency_code, $min);
                        $max_user = Currency::convert($asset->currency, $this->currency_code, $max);

                        // Convert to main currency (for client-side comparison against order amount which is stored in main currency)
                        $min_main = Currency::convert($asset->currency, $this->main_currency, $min);
                        $max_main = Currency::convert($asset->currency, $this->main_currency, $max);

                        $assets[] = [
                            'id' => $asset->id,
                            'title' => $asset->title,
                            'icon' => $asset->icon,
                            'curr' => $this->currency_code,
                            'min' => $min,
                            'max' => $max,
                            'min_user' => round($min_user, 2),
                            'max_user' => round($max_user, 2),
                            'min_main' => round($min_main, 4),
                            'max_main' => round($max_main, 4),
                            'currency' => $asset->currency,
                            'is_active' => $asset->active
                        ];
                    }

                    $all[] = [
                        'id' => $p->id,
                        'pid' => $p->pid,
                        'title' => $p->title,
                        'icon' => $p->icon,
                        'link' => $p->link,
                        'type' => $p->type,
                        'assets' => $assets,
                        'is_active' => $p->active,
                    ];

                }
            }

            return response()->json(['ok' => true, 'price' => $price.$this->shop_currency[$this->set_shop->currency], 'price_raw' => (float)$price, 'main_currency' => $this->main_currency, 'result' => $all]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 200);
        }
    }


    public function psAllAdmin(Request $request)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.payments')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $payment_systems = PaymentSystem::orderBy('id')->get();

            $all = [];

            foreach ($payment_systems as $p) {

                $method = MethodPayment::where('psid', $p->id)->where('sid', $shop->id)->first();

                $is_active = 0;
                if($method && $method->active == 1) {
                    $is_active = 1;
                }

                $all[] = [
                    'id' => $p->id,
                    'pid' => $p->pid,
                    'title' => $p->title,
                    'icon' => $p->icon,
                    'link' => $p->link,
                    'type' => $p->type,
                    'is_active' => $is_active,
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
            ], 200);
        }
    }


    public function psAllByPsid($psid)
    {
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $method_assets = PaymentAsset::where('psid', $psid)->get();

            $assets = [];

            foreach ($method_assets as $asset) {

                $assets[] = [
                    'id' => $asset->id,
                    'title' => $asset->title,
                    'icon' => $asset->icon,
                    'min' => $asset->min,
                    'max' => $asset->max,
                    'currency' => $asset->currency,
                    'is_active' => $asset->active
                ];

            }

            return response()->json(['ok' => true, 'result' => $assets]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 200);
        }
    }


}
