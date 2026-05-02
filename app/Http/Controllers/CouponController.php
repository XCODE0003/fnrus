<?php

namespace App\Http\Controllers;

use App\Models\CouponUse;
use App\Models\RolePermission;
use App\Models\ShopSettings;
use App\Models\Tariff;
use Exception;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Coupon;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Yajra\Datatables\Facades\Datatables;
use Telegram\Bot\Api;

class CouponController extends Controller
{

    public $user;
    private $set_shop;
    private $main_currency;
    private $currency_symbol;
    private $def_currency_symbol;
    private $shop_currency;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();

                $this->set_shop = ShopSettings::getDefault();
                $this->main_currency = $this->set_shop->currency;
                $this->shop_currency = ['RUB' => '₽', 'USD' => '$'];

                $this->def_currency_symbol = $this->shop_currency[$this->set_shop->currency];

                if ($this->user) {
                    $this->currency_symbol = $this->shop_currency[$this->user->currency];
                } else {
                    $this->currency_symbol = $this->shop_currency[$this->set_shop->currency];
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

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'coupons.info')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $coupon = Coupon::where('sid', $shop->id)->where('id', $id)->first();
            if (!$coupon) {
                throw new Exception('Промокод не найден.', 1);
            }

            $products = Product::where('sid', $shop->id)
                ->get();

            $products_all = [];

            foreach ($products as $p) {
                if($id != $p->id){
                    $products_all[] = [
                        'id' => $p->id,
                        'title' => $p->title
                    ];
                }
            }

            $result = [
                'gids' => json_decode($coupon->gids, true),
                'code' => $coupon->code,
                'sale' => $coupon->sale,
                'sale_type' => $coupon->sale_type,
                'min_sum' => $coupon->min_sum,
                'count_uses_min' => $coupon->count_uses_min,
                'count_uses_type' => $coupon->count_uses_type,
                'count_uses_max' => $coupon->count_uses_max,
                'is_new_users' => $coupon->is_new_users,
                'is_one_time' => $coupon->is_one_time,
                'products' => $products_all
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

    public function create(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'coupons.add')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'gids' => 'required|array',
                'code' => 'required|string|min:3|max:50',
                'sale_type' => 'required|int|between:0,1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            if($request->sale_type == 0){
                $validator = Validator::make($request->all(), [
                    'sale' => 'required|int|between:1,100',
                ]);
            }

            if($request->sale_type == 1){
                $validator = Validator::make($request->all(), [
                    'sale' => 'required|numeric|between:1,99999.99',
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $validator = Validator::make($request->all(), [
                'min_sum' => 'required|int|between:0,100000',
                'count_uses_min' => 'required|int|between:1,10000',
                'count_uses_type' => 'required|int|between:0,1',
                'count_uses_max' => 'required|int|between:0,10000',
                'is_new_users' => 'required|int|between:0,1',
                'is_one_time' => 'required|int|between:0,1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }


            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            foreach ($request->gids as $id) {
                $check_product = Product::where('sid', $shop->id)->where('id', $id)->first();
                if(!$check_product){
                    throw new Exception('Один из товаров не найден.', 1);
                }
            }

            $check_coupon = Coupon::where('sid', $shop->id)->where('code', $request->code)->first();
            if ($check_coupon) {
                throw new Exception('Такой промокод уже существует.', 1);
            }

            $sql = [
                'sid' => $shop->id,
                'gids' => json_encode($request->gids),
                'code' => $request->code,
                'sale' => $request->sale,
                'sale_type' => $request->sale_type,
                'min_sum' => $request->min_sum,
                'count_uses_min' => $request->count_uses_min,
                'count_uses_type' => $request->count_uses_type,
                'count_uses_max' => $request->count_uses_max,
                'count_expired' => 0,
                'count_expired_type' => 0,
                'is_new_users' => $request->is_new_users,
                'is_one_time' => $request->is_one_time,
                'updated_at' => $date_now,
                'created_at' => $date_now,
            ];

            $cid = Coupon::insertGetId($sql);

            if ($cid) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $cid
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function check(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'product_id' => 'required|int|min:1',
                'tariff_id' => 'required|int|min:1',
                'promocode' => 'required|string|min:3|max:50'
            ]);

            $validator->setAttributeNames([
                'promocode' => 'промокод',
                'product_id' => 'товар',
                'tariff_id' => 'тариф',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $joined_at = $this->user->created_at;
            $date_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('id', $request->product_id)->where('sid', $shop->id)->first();
            if(!$product){throw new Exception('Товар не найден.', 1);}

            $tariff = Tariff::getByID($shop->id, $product->id, $request->tariff_id);
            if(!$tariff){throw new Exception('Тариф не найден.');}

            $check = Coupon::getByCode($shop->id, $request->product_id, $request->promocode);
            if (!$check) { throw new Exception('Промокод не найден.');}

            if ($check){

                if($check->is_new_users == 1 && $date_ago > $joined_at){
                    throw new Exception('Этот промокод действует только для новых пользователей.');
                }

                $count_uses_user = CouponUse::where('promo_id', $check->id)
                    ->where('chat_id', $this->user->id)
                    ->where('shop_id', $shop->id)
                    ->count();

                if($check->is_one_time == 1 && $count_uses_user >= 1){
                    throw new Exception('Вы уже использовали данный промокод');
                }

                if($check->count_uses_max == 0){
                    throw new Exception('Число активаций промокода исчерпано.');
                }

                if($check->min_sum > $tariff->price){
                    throw new Exception('Минимальная сумма для использования промокода '.$promo->min_sum.'.');
                }

                if($check->sale_type == 0) {
                    if($check->sale > 100){
                        throw new Exception('Cкидка промокода не может превышать более 100%.');
                    }
                }
                if($check->sale_type == 1) {
                    if($check->sale > $tariff->price){
                        throw new Exception('Скидка промокода не может превышать сумму заказа.');
                    }
                }

                if ($check->sale_type == 0) {$coupon_sale = "-" . $check->sale . '%';}
                if ($check->sale_type == 1) {$coupon_sale = "-" . $check->sale . $this->def_currency_symbol;}
               return response()->json([
                   'ok' => true,
                   'result' => [
                       'sale' => $coupon_sale,
                   ]
               ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function all()
    {

        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'coupons.all')->allow;
            if (!$access) {
                throw new Exception('Access Denied');
            }

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Shop not found!');
            }

            $coupons = Coupon::where('sid', $shop->id)->orderByDesc('created_at')->get();

            $all = [];

            foreach ($coupons as $c) {

                $gids = json_decode($c->gids, true);

                $goods = [];

                foreach ($gids as $g) {
                    $product = Product::where('id', $g)->first();
                    if ($product) {
                        $goods[] = '<li class="badge bg-secondary px-2" style="color: #000;">' . $product->title . '</li>';
                    }
                }

                if (count($goods) == 1) {
                    $bgoods = $goods[0];
                }

                if (count($goods) == 2) {
                    $bgoods = $goods[0] . ' ' . $goods[1];
                }

                if (count($goods) > 2) {
                    $bgoods = $goods[0] . ' ' . $goods[1] . ' <li class="badge bg-secondary px-2" style="color: #000;">...</li>';
                }

                $block_goods = '<ul class="m-0 p-0" style="display: inline-block;flex-wrap: wrap;">' . $bgoods . '</ul>';

                $block_sale = $c->sale . '%';
                if ($c->sale_type == 1) {
                    $block_sale = $c->sale . $this->def_currency_symbol;
                }


                $block_count_uses_min = 'от ' . $c->count_uses_min . ' шт.';
                if ($c->count_uses_type == 1) {
                    $block_count_uses_min = 'от ' . $c->count_uses_min . $this->def_currency_symbol;
                }

                $all[] = [
                    'id' => $c->id,
                    'goods' => $block_goods,
                    'icon' => '<i class="far fa-badge-percent"></i>',
                    'sale' => $block_sale,
//                'count_expired' => $block_count_expired,
                    'code' => $c->code,
                    'count_uses_min' => $block_count_uses_min,
                    'count_uses_max' => $c->count_uses_max,
                ];
            }

            return datatables($all)
                ->addColumn('block_copy', function ($row) {
                    return '<a data-id="' . $row['id'] . '" data-link="' . $row['code'] . '" id="copy_link_share" onclick="copy(\'' . $row['code'] . '\', 0);" title="Скопировать промокод" href="javascript:;"><i class="far fa-copy fa-xl"></i></a>';
                })
                ->addColumn('block_edit', function ($row) {
                    return '<a data-id="' . $row['id'] . '" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editCoupon"><i class="far fa-edit fa-xl"></i></a>';
                })
                ->addColumn('block_delete', function ($row) {
                    return '<a onclick="remove(\'coupon\',' . $row['id'] . ');return false;" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a>';
                })
                ->rawColumns(['icon', 'goods', 'block_copy', 'block_edit', 'block_delete'])
                ->make(true);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'coupons.edit')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'id' => 'required|int|min:1',
                'gids' => 'required|array|min:1',
                'code' => 'required|string|min:3|max:50',
                'sale_type' => 'required|int|between:0,1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            if($request->sale_type == 0){
                $validator = Validator::make($request->all(), [
                    'sale' => 'required|int|between:1,100',
                ]);
            }

            if($request->sale_type == 1){
                $validator = Validator::make($request->all(), [
                    'sale' => 'required|numeric|between:1,99999.99',
                ]);
            }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $validator = Validator::make($request->all(), [
                'min_sum' => 'required|int|between:0,100000',
                'count_uses_min' => 'required|int|between:1,10000',
                'count_uses_type' => 'required|int|between:0,1',
                'count_uses_max' => 'required|int|between:0,10000',
                'is_new_users' => 'required|int|between:0,1',
                'is_one_time' => 'required|int|between:0,1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            foreach ($request->gids as $id) {
                $check_product = Product::where('sid', $shop->id)->where('id', $id)->first();
                if(!$check_product){
                    throw new Exception('Один из товаров не найден.', 1);
                }
            }

            $check_coupon = Coupon::where('sid', $shop->id)->where('code', $request->code)->first();
            if ($check_coupon && $check_coupon->id != $request->id) {
                throw new Exception('c', 1);
            }

            $sql = [
                'gids' => json_encode($request->gids),
                'code' => $request->code,
                'sale' => $request->sale,
                'sale_type' => $request->sale_type,
                'min_sum' => $request->min_sum,
                'count_uses_min' => $request->count_uses_min,
                'count_uses_type' => $request->count_uses_type,
                'count_uses_max' => $request->count_uses_max,
                'is_new_users' => $request->is_new_users,
                'is_one_time' => $request->is_one_time,
                'updated_at' => $date_now,
            ];

            $update = Coupon::where('sid', $shop->id)->where('id', $request->id)->update($sql);
            if ($update) {
                return response()->json(['ok' => true, 'description' => 'Сохранено']);
            }

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function delete(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'coupons.delete')->allow;
            if(!$access){throw new Exception('Access Denied');}

            if($request->filled($request->id)){
                throw new Exception('ID не найден.');
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $coupon = Coupon::where('sid', $shop->id)->where('id', $request->id)->first();
            if (!$coupon) {
                throw new Exception('Промокод не найден.', 1);
            }

            $delete = Coupon::where('id', $request->id)->delete();
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
    public function getNum($number, $one, $two, $five)
    {
        $n = abs($number);

        $n %= 100;

        if ($n >= 5 && $n <= 20) {
            return $number.' '.$five;
        }

        $n %= 10;

        if ($n === 1) {
            return $number.' '.$one;
        }

        if ($n >= 2 && $n <= 4) {
            return $number.' '.$two;
        }

        return $number.' '.$five;
    }

}
