<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\ShopSettings;
use Exception;
use App\Models\ButtonSettings;
use App\Models\Button;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ButtonSetController extends Controller
{
    public $user;
    public function __construct()
    {
        try {
            $this->user = Auth::user();
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }

    public function update(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.constructor')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $button_set = ButtonSettings::where('sid', $shop->id)->first();
            if (!$button_set) {throw new Exception('Настройки не найдены.', 1);}

            $validator = Validator::make($request->all(), [
                'count_columns' => 'required|int|min:1|max:3'
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $sql = ['count_columns' => $request->count_columns];

            ButtonSettings::where('sid', $shop->id)->update($sql);

            return response()->json(['ok' => true, 'description' => 'Сохранено']);

        } catch (Exception $e) {
            $error = $e->getMessage();
            return response()->json(['ok' => false, 'description' => $error], 200);
        }
    }
}
