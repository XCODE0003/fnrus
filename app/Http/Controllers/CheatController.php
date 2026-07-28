<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Shop;
use App\Models\StatusCheat;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class CheatController extends Controller
{
    public function search(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'query' => 'nullable|string|min:0|max:100',
                'status' => 'required|int|min:0|max:4',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $query = $request->get('query');
            $status = intval($request->get('status'));

            $categories = Category::list_web_by_cid(0);

            $categories_json = [];

            foreach ($categories->getData()->result as $c){

                $cheats_json = [];

                $cheatsQuery = StatusCheat::where('cid', $c->id);
                if ($query != '') {
                    $cheatsQuery->where('title', 'like', '%' . $query . '%');
                }
                if ($status > 0) {
                    $cheatsQuery->where('status', $status);
                }
                $cheats = $cheatsQuery->get();

                foreach ($cheats as $p) {

                    // $status_class used to leak from the previous row when
                    // status was 0 — statusMeta() always returns a class.
                    $meta = \App\Models\StatusCheat::statusMeta($p->status);

                    $cheats_json[] = [
                        'title' => $p->title,
                        'status' => $meta['key'],
                        'status_label' => $meta['label'],
                        'status_icon' => $meta['icon'],
                    ];
                }

                if($cheats_json){
                    $categories_json[] = [
                        'title' => $c->title,
                        'image' => $c->image_site,
                        'cheats' => $cheats_json,
                    ];
                }

            }

            return response()->json(['ok' => true, 'count' => count($categories_json), 'result' => $categories_json]);

        } catch (Exception $e) {
            return response(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
}
