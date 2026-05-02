<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Shop;
use App\Models\Tariff;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TariffController extends Controller
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
            return response([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function check($id){
        try {
            $materials_count = 0;
            $tariff = Tariff::where('id', $id)->first();
            if ($tariff) {
                $materials_count = Material::getCountAllByTID($tariff->id);
            }
            return response()->json(['ok' => true, 'count' => $materials_count]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function list_by_id($id){
        try {

            $shop = Shop::where('id', $this->user->sid)->first();
            if (!$shop) {throw new Exception('Магазин не найден.', 1);}

            $tariffs_json = [];

            $result = Tariff::getListByPid($shop->id, $id);
            if ($result){
                foreach ($result as $item) {
                    $tariffs_json[] = [
                        'id' => $item->id,
                        'title' => $item->title,
                        'price' => $item->price,
                    ];
                }
            }

            return response()->json(['ok' => true, 'result' => $tariffs_json]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

}
