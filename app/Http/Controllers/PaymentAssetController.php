<?php

namespace App\Http\Controllers;

use App\Models\PaymentAsset;
use Exception;
use App\Models\MethodPayment;
use App\Models\PaymentSystem;
use App\Models\Shop;
use App\Models\ShopSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Yajra\Datatables\Facades\Datatables;

class PaymentAssetController extends Controller
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
                $shop_currency = ['RUB' => '₽', 'USD' => '$'];

                if ($this->user) {
                    $this->currency_symbol = $shop_currency[$this->user->currency];
                    $this->currency_code = $this->user->currency;
                } else {
                    $this->currency_symbol = $shop_currency[$this->set_shop->currency];
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

    public function fullinfo($id){
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $system = PaymentAsset::where('id', $id)->first();

            if (!$system) {
                throw new Exception('Платежная система не найдена.', 1);
            }

            $result = [
                'id' => $system->id,
                'title' => $system->title,
                'min' => $system->min,
                'max' => $system->max
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

    public function update($id, Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'min' => 'required|numeric|min:0|max:9999999',
                'max' => 'required|numeric|min:0|max:9999999',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Магазин не найден.');}

            $passet = PaymentAsset::where('id', $id)->first();
            if(!$passet){throw new Exception('Платежная система не найдена.');}

            $passet->min = $request->get('min');
            $passet->max = $request->get('max');
            $passet->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function active_update($id){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Магазин не найден.');}

            $passet = PaymentAsset::where('id', $id)->first();
            if(!$passet){throw new Exception('Платежная система не найдена.');}

            if($passet->active == 0){$active = 1;}else{$active = 0;}

            $passet->active = $active;
            $passet->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

}
