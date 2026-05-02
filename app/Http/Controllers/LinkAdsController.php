<?php

namespace App\Http\Controllers;

use App\Models\LinkAd;
use App\Models\LinkAdUser;
use App\Models\RolePermission;
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

class LinkAdsController extends Controller
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

    public function create(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'ads_links.add')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:2|max:100',
                'code' => 'nullable|string|min:3|max:50',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $check_link = LinkAd::where('sid', $shop->id)->where('code', $request->code)->first();
            if ($check_link) {throw new Exception('Такой код уже существует.', 1);}

            $sql = [
                'sid' => $shop->id,
                'title' => $request->title,
                'code' => mb_strtolower($request->code),
                'visits_total' => 0,
                'visits_unique' => 0,
                'created_at' => $date_now,
            ];

            $link_id = LinkAd::insertGetId($sql);

            if ($link_id) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $link_id
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
            $access = RolePermission::getByPermission($this->user->role_id, 'ads_links.all')->allow;
            if (!$access) {
                throw new Exception('Access Denied');
            }

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Shop not found!');
            }

            $links = LinkAd::where('sid', $shop->id)->orderByDesc('created_at')->get();

            $all = [];

            foreach ($links as $l) {
                $all[] = [
                    'id' => $l->id,
                    'icon' => '<i class="far fa-link mr-1"></i>',
                    'title' => $l->title,
                    'code' => 'ad_' . mb_strtolower($l->code),
                    'visits_total' => $l->visits_total,
                    'visits_unique' => $l->visits_unique,
                    'block_copy' => '<a id="copy_link_share" onclick="copy(\'https://t.me/' . $shop->username . '?start=ad_' . $l->code . '\', 0);" title="Скопировать ссылку" href="javascript:;"><i class="far fa-copy fa-xl"></i></a>',
                    'block_delete' => '<a href="#" onclick="deleteLink(\'' . $l->id . '\');return false;" title="Удалить" class="text-danger"><i class="far fa-trash fa-xl"></i></a>',
                    'created_at' => date('d.m.Y в H:i', $l->created_at),
                ];
            }

            return datatables($all)
                ->rawColumns(['icon', 'block_copy', 'block_delete'])
                ->make(true);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public function delete($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'ads_links.delete')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $link_ad = LinkAd::where('sid', $shop->id)->where('id', $id)->first();
            if (!$link_ad) {throw new Exception('Ссылка не найдена.', 1);}

            $delete = LinkAd::where('id', $id)->delete();
            if($delete) {
                LinkAdUser::where('link_id', $id)->delete();
                return response()->json(['ok' => true, 'description' => 'Удалено']);
            }

        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()]);
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
