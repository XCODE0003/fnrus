<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Order;
use App\Models\RolePermission;
use App\Models\Shop;
// use App\Models\Ticket; // disabled - ticket system removed
use App\Models\Withdrawal;
use App\Models\ShopSettings;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class StatController extends Controller
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

    public function get(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'analytics.get')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'period' => 'required|int|between:1,5',
                'type' => 'required|int|between:0,3'
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            if($request->period == 1){
                $param[0] = '';
                $param[1] = '';
            }

            if($request->period == 2){
                $param[0] = '-1 days';
                $param[1] = '-1 days';
            }

            if($request->period == 3){
                $param[0] = '-7 days';
                $param[1] = '';
            }

            if($request->period == 4){
                $param[0] = '-30 days';
                $param[1] = '';
            }

            if($request->period == 5){
                $param[0] = '-9999 weeks';
                $param[1] = '';
            }

            $start_date = strtotime(date('Y-m-d 00:00:00').$param[0]);
            $end_date = strtotime(date('Y-m-d 23:59:59').$param[1]);

            if($request->type == 0 || $request->type == 1) {
                $sales_count = Order::where('status', 2)
                    ->where('sid', $shop->id)
                    ->where('payment_at', '>=', $start_date)
                    ->where('payment_at', '<=', $end_date)
                    ->count();
                $result = ['sales' => $sales_count];
            }

            if($request->type == 0 || $request->type == 2) {
                $profits_count = Order::where('status', 2)
                    ->where('sid', $shop->id)
                    ->where('payment_at', '>=', $start_date)
                    ->where('payment_at', '<=', $end_date)
                    ->sum('amount');
                $result = ['profits' => number_format($profits_count, 2, '.', ' ').$this->def_currency_symbol];
            }

            if($request->type == 0 || $request->type == 3) {
                $members_count = Member::where('is_active', 1)
                    ->where('created_at', '>=', $start_date)
                    ->where('created_at', '<=', $end_date)
                    ->count();
                $result = ['members' => $members_count];
            }

            if($request->type == 0) {
                $result = [
                    'sales' => $sales_count,
                    'profits' => number_format($profits_count, 2, '.', ' ').$this->def_currency_symbol,
                    'members' => $members_count
                ];
            }

            return response()->json([
                'ok' => true,
                'result' => $result
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine()], 200);
        }
    }

    public function get_counter(){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'tickets.stats')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Магазин не найден.');}

            // $new_tickets = Ticket::where('status', 0)->count(); // disabled - ticket system removed
            $new_withdrawals = Withdrawal::where('status', 1)->count();

            $result = [
                'new_tickets' => 0, // disabled
                'new_withdrawals' => $new_withdrawals
            ];

            return response()->json([
                'ok' => true,
                'result' => $result
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage(), 'line' => $e->getLine()], 200);
        }
    }




}
