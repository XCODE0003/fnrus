<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RolePermission;
use App\Models\Shop;
use App\Models\Tariff;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\Datatables\Facades\Datatables;


class MaterialsExportController extends Controller
{
    public $user;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                if ($this->user == null) {
                    throw new Exception('Authorization key not passed', 1000);
                }
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

    public function formatDateTimeZone($date,$to)
    {

        $dateUTC = Carbon::now();
        $datePST = Carbon::now();
        $datePST->setTimezone($to);

        $start  = new Carbon($dateUTC);
        $end    = new Carbon($datePST);

        $seconds = strtotime($end) - strtotime($start);
        $hours = $seconds / 60 /  60;

        $carbon = Carbon::createFromDate(date('Y-m-d H:i:s',$date), $to);
        $carbon->addHours($hours);

        return $carbon->format('d.m.Y в H:i:s');
    }

    public function all()
    {

        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'history_exports.all')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $mexports = DB::table('materials_exports')
                ->where('sid', $shop->id)
                ->orderByDesc('created_at')
                ->get();

            $all = [];

            foreach ($mexports as $e) {

                $p = Product::where('id', $e->pid)->first();

                $block_title = $e->title;
                if($p){$block_title = $p->title;}

                if($e->is_stock == 0){$block_status = '<span class="badge rounded-pill bg-secondary px-2" style="color:#000">Не выгружено</span>';}
                if($e->is_stock == 1){$block_status = '<span class="badge rounded-pill bg-success px-2" style="color:#000">Выгружено</span>';}

                if($e->tid == 0){
                    $block_tariff = 'Неизвестно';
                } else {
                    $block_tariff = $e->title_tariff;
                    $t = Tariff::getByID($e->sid, $e->pid, $e->tid);
                    if($t){$block_tariff = Tariff::num_decline($t->title, ['день','дня','дней'] );}

                }

                $all[] = [
                    'id' => $e->id,
                    'tid' => $block_tariff,
                    'icon' => '<i class="far fa-file-download"></i>',
                    'title' => $block_title,
                    'count' => $e->count_all,
                    'status' => $block_status,
                    'created_at' => $this->formatDateTimeZone($e->created_at,$this->user->tz)
                ];
            }

            return datatables($all)
                ->addColumn('block_export', function ($row) {
                    return '<a onclick="downloadExport('.$row['id'].')" href="javascript:;" title="Скачать материал"> <i class="far fa-cloud-download fa-xl"></i></a>';
                })
                ->rawColumns(['icon', 'status','block_export'])
                ->make(true);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function download($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'history_exports.download')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $mexport = DB::table('materials_exports')
                ->where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$mexport) {
                throw new Exception('Export not found!', 1);
            }

            $materials = DB::table('materials')
                ->where('sid', $shop->id)
                ->where('eid', $id)
                ->get();

            $body = [];

            foreach ($materials as $m) {
                $body[] = $m->body;
            }

            $path = 'public/files/';
            $filename = Str::random(16);

            if(Storage::disk('local')->put($path.$filename.'.txt', implode("\n", $body))){
                return Storage::download($path.$filename.'.txt');
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function info($id)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'history_exports.info')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $mexport = DB::table('materials_exports')
                ->where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$mexport) {
                throw new Exception('Export not found!', 1);
            }

            $materials = DB::table('materials')
                ->where('sid', $shop->id)
                ->where('eid', $id)
                ->get();

            $body = [];

            foreach ($materials as $m) {
                $body[] = $m->body;
            }

            return response()->json([
                'ok' => true,
                'result' => [
                    'count' => $mexport->count_all,
                    'body' => implode("\n", $body)
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


//    public function create(Request $request)
//    {
//        try {
//
//            $validator = Validator::make($request->all(), [
//                'pid' => 'required|int|min:1',
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
//            $date_now = Carbon::now()->timestamp;
//
//            $sql = [
//                'sid' => $shop->id,
//                'pid' => $request->pid,
//                'title' => $product->title,
//                'created_at' => $date_now
//            ];
//
//            $mexport_id = DB::table('materials_exports')->insertGetId($sql);
//
//            if($mexport_id){
//                return response()->json(['ok' => true, 'result' => ['id' => $mexport_id]]);
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
