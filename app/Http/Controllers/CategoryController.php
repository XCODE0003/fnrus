<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\ShopSettings;
use Exception;
use App\Models\User;
use App\Models\Shop;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attach;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class CategoryController extends Controller
{
    protected $user;
    protected $type;

    public function __construct()
    {
        try {
            $this->user = Auth::user();
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json(['ok' => false,  'description' => $e->getMessage()], 200);
        }
    }

    public function info($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.info')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $category = Category::where('sid', $shop->id)->where('id', $id)->first();
            if (!$category) {
                throw new Exception('Категория не найдена.', 1);
            }

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $category->id,
                    'title' => $category->title,
                    'alias' => $category->alias,
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function fullinfo($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.info')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $category = Category::where('sid', $shop->id)->where('id', $id)->first();
            if (!$category) {
                throw new Exception('Категория не найдена.', 1);
            }

            $cats = Category::where('sid', $shop->id)->get();

            $cats_all = [];

            foreach ($cats as $cat) {
                if($id != $cat->id){
                    $cats_all[] = [
                        'id' => $cat->id,
                        'title' => $cat->title
                    ];
                }
            }

            $image = '';
            if($category->image != ''){
                $image = 'i'.$category->image;
            }

            $image_site = '';
            if($category->image_site != ''){
                $image_site = 'i'.$category->image_site;
            }

            $result = [
                'cid' => $category->cid,
                'title' => $category->title,
                'seo_description' => $category->seo_description,
                'seo_keywords' => $category->seo_keywords,
                'description' => $category->description,
                'image' => $image,
                'image_site' => $image_site,
                'has_spoiler' => $category->image_spoiler,
                'disable_web_page_preview' => $category->disable_web_page_preview,
                'display_products' => $category->display_products,
                'count_views' => $category->count_views,
                'count_column' => $category->count_column,
                'visibility' => $category->visibility,
                'alias' => $category->alias,
                'categories' => $cats_all
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function visibility($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.visibility')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $cat = Category::find($id);

            if (!$cat) {
                throw new Exception('Категория не найдена.', 1);
            }

            if($cat->visibility == 0){$val = 1;}else{$val = 0;}

            $update = Category::where('id', $id)->update(['visibility' => $val]);

            if($update) {
                if($val == 1){$msg = 'Категория снова общедоступена';}
                if($val == 0){$msg = 'Категория скрыта из общего доступа';}

                return response()->json([
                    'ok' => true,
                    'description' => $msg
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function delete($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.delete')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $category = Category::where('sid', $shop->id)->where('id', $id)->first();
            if (!$category) {
                throw new Exception('Категория не найдена.', 1);
            }

            $delete = Category::where('id', $id)->delete();

            if($delete) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Category deleted'
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function sort(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'categories.sort')->allow;
            if(!$access){throw new Exception('Access Denied');}

            if($request->filled($request->sort)){
                throw new Exception('Сортировка не определена.');
            }

            foreach ($request->sort as $i => $row) {
                $sql = ['sort' => ++$i];
                Category::where('id', intval($row))->update($sql);
            }

            return response()->json([
                'ok' => true,
                'description' => 'Перемещено'
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function all()
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.all')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $categories = Category::where('sid', $shop->id)->orderBy('sort')->get();

            $all = [];

            foreach ($categories as $c) {

                $block_category = 'Без категории';
                $block_type = 'Категория';
                $count_products = 0;

                if($c->cid != 0) {
                    $category = Category::where('sid', $shop->id)->where('id', $c->cid)->first();
                    $block_category = $category->title;
                    $block_type = 'Подкатегория';

                    $count_products = Product::where('sid', $shop->id)->where('cid', $c->cid)->count();
                }


                $all[] = [
                    'id' => $c->id,
                    'icon' => '<i class="far fa-list-alt"></i>',
                    'title' => $c->title,
                    'category' => $block_category,
                    'count_products' => $count_products,
                    'count_views' => $c->count_views,
                    'sort' => $c->sort,
                    'visibility' => $c->visibility,
                    'link_share' => 'https://t.me/'.$shop->username.'?start=category-'.$c->id
                ];
            }

            return datatables($all)
                ->addColumn('block_move', function ($row) {return '<a href="javascript:;"><i class="fal fa-arrows handle"></i></a>';})
                ->addColumn('block_link_share', function ($row) {return '<a data-id="'.$row['id'].'" data-link="'.$row['link_share'].'" id="copy_link_share" onclick="copy(\''.$row['link_share'].'\', 0);" title="Скопировать ссылку" href="javascript:;"><i class="far fa-copy fa-xl"></i></a>';})
                ->addColumn('block_edit', function ($row) {return '<a data-id="'.$row['id'].'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editCategory"><i class="far fa-edit fa-xl"></i></a>';})
                ->addColumn('block_delete', function ($row) {return '<a onclick="remove(\'category\','.$row['id'].');return false;" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a><input type="hidden" name="sort[]" value="'.$row['id'].'">';})
                ->rawColumns(['icon', 'block_move', 'block_link_share', 'block_visibility', 'block_edit', 'block_delete'])
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

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.add')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'cid' => 'required|int|min:0',
                'title' => 'required|string|min:2|max:100',
                'seo_description' => 'nullable|string',
                'seo_keywords' => 'nullable|string',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'image_site' => 'required|string',
                'visibility' => 'required|int|between:0,3',
                'alias' => 'required|string|min:2|max:100',
                'count_column' => 'required|int|between:1,5',
                'display_products' => 'required|int|between:0,1',
                'has_spoiler' => 'required|int|between:0,1',
                'disable_web_page_preview' => 'required|int|between:0,1',
            ]);

            $validator->setAttributeNames([
                'cid' => 'категория',
                'title' => 'название',
                'image_site' => 'обложка для сайта',
                'visibility' => 'видимость',
                'alias' => 'короткий адрес',
                'count_column' => 'кол-во колонок',
                'display_products' => 'отображение товаров',
                'has_spoiler' => 'спойлер на изображение',
                'disable_web_page_preview' => 'предпросмотр ссылок',
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

            $seo_description = '';
            $seo_keywords = '';
            $description = '';
            $image_db = '';
            $image_site_db = '';

            if(!$request->filled($request->seo_description)){$seo_description = $request->seo_description;}
            if(!$request->filled($request->seo_keywords)){$seo_keywords = $request->seo_keywords;}
            if(!$request->filled($request->description)){$description = $request->description;}
            if(!$request->filled($request->image)){
                $image_db = substr($request->image, 1);
                $r = Attach::where('id', $image_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }
            if(!$request->filled($request->image_site)){
                $image_site_db = substr($request->image_site, 1);
                $r = Attach::where('id', $image_site_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }

            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            if($request->cid != 0) {
                $category = Category::where('sid', $shop->id)->where('id', $request->cid)->first();
                if (!$category) {
                    throw new Exception('Категория не найдена.', 1);
                }
            }

            $sql = [
                'cid' => $request->cid,
                'sid' => $shop->id,
                'title' => $request->title,
                'seo_description' => $seo_description,
                'seo_keywords' => $seo_keywords,
                'description' => $description,
                'image' => $image_db,
                'image_site' => $image_site_db,
                'image_spoiler' => $request->has_spoiler,
                'alias' => $request->alias,
                'disable_web_page_preview' => $request->disable_web_page_preview,
                'display_products' => $request->display_products,
                'count_views' => 0,
                'count_column' => $request->count_column,
                'sort' => 0,
                'visibility' => $request->visibility,
                'updated_at' => $date_now,
                'created_at' => $date_now,
            ];

            $insert_id = Category::insertGetId($sql);

            if ($insert_id) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $insert_id
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update($id, Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.edit')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'cid' => 'required|int|min:0',
                'title' => 'required|string|min:2|max:100',
                'seo_description' => 'nullable|string',
                'seo_keywords' => 'nullable|string',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'image_site' => 'required|string',
                'visibility' => 'required|int|between:0,3',
                'alias' => 'required|string|min:2|max:100',
                'display_products' => 'required|int|between:0,1',
                'count_column' => 'required|int|between:1,5',
                'has_spoiler' => 'required|int|between:0,1',
                'disable_web_page_preview' => 'required|int|between:0,1',
            ]);

            $validator->setAttributeNames([
                'cid' => 'категория',
                'title' => 'название',
                'image_site' => 'обложка для сайта',
                'visibility' => 'видимость',
                'alias' => 'короткий адрес',
                'display_products' => 'отображение товаров',
                'count_column' => 'кол-во колонок',
                'has_spoiler' => 'спойлер на изображение',
                'disable_web_page_preview' => 'предпросмотр ссылок',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $seo_description = '';
            $seo_keywords = '';
            $description = '';
            $image_db = '';
            $image_site_db = '';

            if(!$request->filled($request->seo_description)){$seo_description = $request->seo_description;}
            if(!$request->filled($request->seo_keywords)){$seo_keywords = $request->seo_keywords;}
            if(!$request->filled($request->description)){$description = $request->description;}
            if(!$request->filled($request->image)){
                $image_db = substr($request->image, 1);
                $r = Attach::where('id', $image_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }
            if(!$request->filled($request->image_site)){
                $image_site_db = substr($request->image_site, 1);
                $r = Attach::where('id', $image_site_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }

            if($request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_no_image')){
                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
            }

            if(!$request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_image')){
                throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_image').' символов.');
            }

            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            if($request->cid != 0) {
                $category = Category::where('sid', $shop->id)->where('id', $request->cid)->first();
                if (!$category) {
                    throw new Exception('Категория не найдена.', 1);
                }
            }

            $sql = [
                'cid' => $request->cid,
                'title' => $request->title,
                'seo_description' => $seo_description,
                'seo_keywords' => $seo_keywords,
                'description' => $description,
                'image' => $image_db,
                'image_site' => $image_site_db,
                'image_spoiler' => $request->has_spoiler,
                'disable_web_page_preview' => $request->disable_web_page_preview,
                'display_products' => $request->display_products,
                'visibility' => $request->visibility,
                'alias' => $request->alias,
                'count_column' => $request->count_column,
                'updated_at' => $date_now,
            ];

            $update = Category::where('sid', $shop->id)->where('id', $id)->update($sql);
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

    public function list_cats()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'categories.all')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $type = $this->type;

            if($type == 0){
                $result = Category::where('cid', '!=', 0)->get();
            } 

            if($type == 1){ 
                $result = Category::get();
            }   

            $all = [];

            foreach ($result as $s) {
                $sub = Category::where('id', $s->cid)->first();

                $all[] = [
                    'id' => $s->id,
                    'title' => $sub->title .' / '.$s->title
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public function selectAll()
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'categories.all')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $categories = Category::where('sid', $shop->id)->orderBy('sort')->get();

            $all = [];

            foreach ($categories as $c) {

                $title = $c->title;
                if($c->cid != 0){
                    $cat = Category::getByID($c->sid, $c->cid);
                    $title = $cat->title.' / '.$c->title;

                    $all[] = [
                        'id' => $c->id,
                        'title' => $title
                    ];
                }


            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e){
            return response()->json(['ok' => false,  'description' => $e->getMessage()], 200);
        }
    }

    public function test(){
        $h = Category::getLastSubcategory(6);

        foreach ($h as $c){
            $h = Category::getLastSubcategory($c->id);
            dd($h);
        }
    }
}
