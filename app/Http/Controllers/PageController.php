<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Exception;
use App\Models\Page;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Yajra\Datatables\Facades\Datatables;

class PageController extends Controller
{

    public $user;
    public function __construct()
    {
        $this->middleware('auth');
        $this->user = Auth::user();
    }

    public function all()
    {
        try {
            $shop = Shop::where('uid', $this->user->id)->first();
            if(!$shop){throw new Exception('Магазин не найден.', 1);}

            $pages = Page::where('sid', $shop->id)->orderByDesc('created_at')->get();

            $all = [];

            foreach ($pages as $p) {
                if($p->visibility == 0){$icon = 'fa-eye';}
                if($p->visibility == 1){$icon = 'fa-eye-slash';}

                $all[] = [
                    'id' => $p->id,
                    'icon' => '<i class="far fa-file mr-1"></i>',
                    'title' => $p->title,
                    'count_views' => $p->count_views,
                    'block_visibility' => '<a onclick="visibility(\'page\','.$p->id.');return false;" title="Скрыть страницу" href="javascript:;"><i class="far '.$icon.' fa-xl"></i></a>',
                    'block_edit' => '<a data-id="'.$p->id.'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editPage"><i class="far fa-edit fa-xl"></i></a>',
                    'block_delete' => '<a href="#" onclick="deletePage(\'' . $p->id . '\');return false;" title="Удалить" class="text-danger"><i class="far fa-trash fa-xl"></i></a>',
                    'created_at' => $p->created_at->format('d.m.Y в H:i')
                ];
            }

            return datatables($all)
                ->rawColumns(['icon', 'block_visibility', 'block_edit', 'block_delete'])
                ->make(true);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function fullinfo($id){
        try {
            $shop = Shop::where('uid', $this->user->id)->first();
            if (!$shop) {throw new Exception('Магазин не найден.', 1);}

            $page = Page::where('sid', $shop->id)->where('id', $id)->first();

            $image = '';
            if($page->image != ''){
                $image = 'i'.$page->image;
            }

            $result = [
                'title' => $page->title,
                'meta_description' => $page->meta_description,
                'meta_keywords' => $page->meta_keywords,
                'text' => $page->text,
                'image' => $image,
                'shortname' => $page->shortname,
                'visibility' => $page->visibility,
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }
}
