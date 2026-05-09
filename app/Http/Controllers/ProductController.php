<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\HackStatus;
use App\Models\Material;
use App\Models\ShopSettings;
use App\Models\Tariff;
use Exception;
use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Attach;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Yajra\Datatables\Facades\Datatables;

class ProductController extends Controller
{
    public $user;
    private $set_shop;
    private $main_currency;
    private $currency_symbol;
    private $main_currency_symbol;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();

                $this->set_shop = ShopSettings::getDefault();
                $this->main_currency = $this->set_shop->currency;
                $shop_currency = ['RUB' => '₽', 'USD' => '$'];

                $this->main_currency_symbol = $shop_currency[$this->set_shop->currency];

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

    public function info($id){
        try {
            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            return response()->json([
                'ok' => true,
                'result' => [
                    'id' => $product->id,
                    'title' => $product->title
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function search(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'required|string|min:0|max:100',
            ]);

            $validator->setAttributeNames([
                'q' => 'поиск',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $categories = Category::where('title', 'like', '%' . $request->q . '%')->where('cid', 0)->where('visibility', 1)->get();
            $products = Product::where('title', 'like', '%' . $request->q . '%')->where('visibility', 1)->get();

            $results_categories_json = [];
            $results_products_json = [];

            foreach ($categories as $c){


//                var_dump($c->image_site);
                $results_categories_json[] = [
                    'title' => $c->title,
                    'alias' => '/'.$c->alias,
                    'image' => '/i'.$c->image,
                    'image_site' => '/i'.$c->image_site,
                ];
            }

            foreach ($products as $p){

                $hack_status = HackStatus::getByID($p->hack_status);

                $status = '';
                if($hack_status->title_pub != '') {$status = $hack_status->title_pub;}

                $alias = '/'.$p->alias;

                if($p->cid != 0){
                    $alias_two = Category::getByID($p->sid, $p->cid);
                    $alias_one = Category::getByID($p->sid, $alias_two->cid);
                    $alias = '/'.$alias_one->alias.'/'.$alias_two->alias.'/'.$p->alias;
                }

                $image_site = '/icheat';
                if($p->image_site != ''){
                    $image_site = '/i'.$p->image_site;
                }

                $image = '/icheat';
                if($p->image != ''){
                    $image = '/i'.$p->image;
                }

                $results_products_json[] = [
                    'title' => $p->title,
                    'alias' => $alias,
                    'image_site' => $image_site,
                    'image' => $image,
                    'status_hack' => $status
                ];
            }

            return response()->json([
                'ok' => true,
                'query' => $request->q,
                'result' => [
                    'categories' => $results_categories_json,
                    'products' => $results_products_json,
                ],
                'count_results' => count($results_categories_json)+count($results_products_json)
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function info_buy($id){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            return response()->json([
                'ok' => true,
                'result' => [
                    'title' => $product->title,
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

            $access = RolePermission::getByPermission($this->user->role_id, 'products.fullinfo')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            $image = '';
            if($product->image != ''){
                $image = 'i'.$product->image;
            }

            $image_site = '';
            if($product->image_site != ''){
                $image_site = 'i'.$product->image_site;
            }

            $tariffs = Tariff::where('pid', $product->id)->orderBy('title')->get();

            $tariffs_new = [];

            foreach ($tariffs as $t) {
                $tariffs_new[] = ['id' => $t->id, 'days_full' => Tariff::num_decline($t->title, ['день','дня','дней'] ), 'price_full' => $t->price.$this->main_currency_symbol, 'days' => $t->title, 'price' => $t->price];
            }

            $result = [
                'cid' => $product->cid,
                'title' => $product->title,
                'seo_description' => $product->seo_description,
                'seo_keywords' => $product->seo_keywords,
                'description' => $product->description,
                'advantages' => json_decode($product->advantages, true),
                'image' => $image,
                'image_site' => $image_site,
                'gallery' => json_decode($product->gallery, true),
                'functional' => json_decode($product->functional, true),
                'has_spoiler' => $product->image_spoiler,
                'disable_web_page_preview' => $product->disable_web_page_preview,
                'count_min' => $product->count_min,
                'count_max' => $product->count_max,
                'alias' => $product->alias,
                'link_video' => $product->link_video,
                'system_platforms' => $product->system_platforms,
                'system_versions' => $product->system_versions,
                'system_auth' => $product->system_auth,
                'currency' => $product->currency,
                'count_type' => $product->count_type,
                'hack_status' => $product->hack_status,
                'visibility' => $product->visibility,
                'status_id' => $product->status_id,
                'tariffs' => $tariffs_new
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function info_by_alias($alias){
        try {

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('sid', $shop->id)
                ->where('alias', $alias)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            $tariffs = Tariff::getListByPid($product->sid, $product->id);

            $tariffs_new = [];

            foreach ($tariffs as $t) {
                $tariffs_new[] = ['id' => $t['id'], 'days' => $t['title'], 'price' => Currency::convert($this->main_currency, $this->currency_code, $t['price']).$this->currency_symbol];
            }

            $prod_price = Currency::convert($this->main_currency, $this->currency_code, $tariffs[0]['price']) . $this->currency_symbol;
            $result = [
                'btn_price' => $prod_price,
                'tariffs' => $tariffs_new,
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function visibility($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'products.visibility')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $product = Product::find($id);
            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            if($product->visibility == 0){$val = 1;}else{$val = 0;}

            $update = Product::where('id', $id)->update(['visibility' => $val]);

            if($update) {
                if($val == 1){$msg = 'Товар снова общедоступен';}
                if($val == 0){$msg = 'Товар скрыт из общего доступа';}

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

            $access = RolePermission::getByPermission($this->user->role_id, 'products.delete')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::where('sid', $shop->id)
                ->where('id', $id)
                ->first();

            if (!$product) {
                throw new Exception('Товар не найден.', 1);
            }

            $prod = Product::where('id', $id)
                ->delete();
            $tariff = Tariff::where('pid', $id)
                ->delete();

            if($prod and $tariff) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Удалено'
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

            $access = RolePermission::getByPermission($this->user->role_id, 'products.sort')->allow;
            if(!$access){throw new Exception('Access Denied');}

            if(!$request->filled('sort')){
                throw new Exception('Сортировка не определена.');
            }

            foreach ($request->sort as $i => $row) {
                $sql = ['sort' => ++$i];
                DB::table('products')->where('id', intval($row))->update($sql);
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


    public function all(Request $request)
    {
        try {
            $start = $request->input('start');
            $length = $request->input('length');
            $search_term = $request->input('search.value');
            $order_column = $request->input('order.0.column');
            $order_dir = $request->input('order.0.dir'); // направление сортировки (asc или desc)
            $column_name = $request->input('columns.' . $order_column . '.data');
            $cat_id = $request->input('cat_id');

            $access = RolePermission::getByPermission($this->user->role_id, 'products.all')->allow;
            if (!$access) {throw new Exception('Доступ запрещен.');}

            $shop = Shop::getDefault();
            if (!$shop) {
                throw new Exception('Магазин не найден.');
            }

            $products = Product::query();
            
            $column_name = $column_name ?: 'sort';
            $order_dir = $order_dir ?: 'asc';

            $products = $products->orderBy($column_name, $order_dir);

            $total_count = Product::count();

            if($cat_id != null){
                $products = $products->where('cid', $cat_id);
            }

            if ($search_term) {
                $products = $products->where(function($query) use ($search_term) {
                    $query->where('title', 'LIKE', '%' . $search_term . '%');
                });
            }

            $filtered_count = $products->count();
            $products = $products->offset($start)->limit($length)->get();
            

            $all = [];

            foreach ($products as $p) {

                $tariff = Tariff::getBySort($p->id, 0);

                $block_price = '0';
                if ($tariff){
                    $block_price = 'от '.$tariff->price;
                }


                $cat = Category::where('id', $p->cid)->first();
                $block_cat = 'Без категории';
                if ($cat){
                    if($p->cid != 0){$block_cat = $cat->title;}
                }


                $tariffs = Tariff::getListByPid($shop->id, $p->id);
                $count_sales = [];
                $count_all = [];
                foreach ($tariffs as $t){
                    $count_sales_input = Material::getCountSales($t['id'], $p->id);
                    if(!$count_sales_input){$count_sales_input = 0;}

                    $count_all_input = Material::getCountAll($t['id'], $p->id);
                    if(!$count_all_input){$count_all_input = 0;}

                    if($count_sales_input > 0){
                        $count_sales[] = '<span class="text-success">'.$count_sales_input.'</span>';
                    } else {
                        $count_sales[] = '<span>'.$count_sales_input.'</span>';
                    }

                    if($count_all_input > 0) {
                        $count_all[] = '<span class="text-success">' . $count_all_input . '</span>';
                    } else {
                        $count_all[] = '<span class="text-danger">'.$count_all_input.'</span>';
                    }

                }

                $block_count_sales = implode(' / ', $count_sales);
                $block_count_all = implode(' / ', $count_all);

                if($p->cid != 0){
                    $cat_two = Category::getByID($p->sid, $p->cid);
                    if($cat_two) {
                        $cat_one = Category::getByID($p->sid, $cat_two->cid);
                    }
                }

                if(isset($cat_one) && isset($cat_two)){
                    $title_block = '<a target="_blank" href="/'.$cat_one->alias.'/'.$cat_two->alias.'/'.$p->alias.'">'.$p->title.'</a>';
                } else {
                    $title_block = $p->title;
                }

                $params_mat = 'data-toggle="modal" data-target="#addMaterial"';
                $params_exp = 'data-toggle="modal" data-target="#exportMaterials"';

                if($p->is_endless == 0){
                    $params_mat = 'data-toggle="modal" data-target="#addMaterial"';
                    $params_exp = 'data-toggle="modal" data-target="#exportMaterials"';
                }

                if($p->is_endless == 1){
                    $params_mat = 'class="text-gray-700"';
                    $params_exp = 'class="text-gray-700"';
                }

                $icon = 'fa-eye';
                if($p->visibility == 0){$icon = 'fa-eye';}
                if($p->visibility == 1){$icon = 'fa-eye-slash';}

                $all[] = [
                    'id' => $p->id,
                    'icon' => '<i class="far fa-shopping-bag"></i>',
                    'title' => $title_block,
                    'cid' => $block_cat,
                    'price' => $block_price.$this->main_currency_symbol,
                    'count_sales' => $block_count_sales,
                    'count_all' => $block_count_all,
                    'count_views' => $p->count_views,
                    'is_endless' => $p->is_endless,
                    'sort' => $p->sort,
                    'visibility' => $p->visibility,
                    'block_move' => '<a href="javascript:;"><i class="fal fa-arrows handle"></i></a>',
                    'block_add_material' => '<a data-id="'.$p->id.'" '.$params_mat.' href="javascript:;" title="Добавить" class=""><i class="fas fa-plus fa-xl"></i></a>',
                    'block_export' => '<a data-id="'.$p->id.'" '.$params_exp.' href="javascript:;" title="Выгрузить материал"> <i class="far fa-cloud-download fa-xl"></i></a>',
                    'block_link_share' => '<a data-id="'.$p->id.'" data-link="'.$p->link_share.'" id="copy_link_share" onclick="copy(\''.$p->link_share.'\', 0);" title="Скопировать ссылку" href="javascript:;"><i class="far fa-copy fa-xl"></i></a>',
                    'block_visibility' => '<a onclick="visibility(\'product\','.$p->id.');return false;" title="Скрыть товар" href="javascript:;"><i class="far '.$icon.' fa-xl"></i></a>',
                    'block_edit' => '<a data-id="'.$p->id.'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editProduct"><i class="far fa-edit fa-xl"></i></a>',
                    'block_delete' => '<a onclick="remove(\'product\','.$p->id.');return false;" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a><input type="hidden" name="sort[]" value="'.$p->id.'">'
                ];
            }
            return response()->json(['data' => $all, 'recordsTotal' => $total_count, 'recordsFiltered' => $filtered_count]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public function check_alias(Request $request){
        try {

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:0|max:100',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }


            $apiUrl = 'https://petal.playstone.org/api/v5/text?detect=bg&from=ru&q=' . urlencode($request->title) . '&to=en';

            $headers = [
                'x-playstone-tz: Europe/Moscow',
                'x-playstone-v: 2.9.9',
                'Accept: */*',
                'Accept-Language: en-RU;q=1.0, ru-RU;q=0.9',
                'User-Agent: Translator/2.9.9 (com.playstone.petal; build:414; macOS 13.4.1) Alamofire/5.6.4',
                'x-playstone-l: en',
                'Connection: close'
            ];

            $ch = curl_init($apiUrl);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            curl_close($ch);
            $decode = json_decode($response, true);

            $alias = mb_strtolower($decode['primary']['translation_result']);
            $alias = mb_ereg_replace('[^-0-9a-z]', '-', $alias);
            $alias = mb_ereg_replace('[-]+', '-', $alias);
            $alias = trim($alias, '-');

            $check = Product::checkAlias($alias);
            if($check) {throw new Exception('Такой короткий адрес уже существует.');}

            return response()->json([
                'ok' => true,
                'result' => $alias
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function create(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'products.add')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'cid' => 'required|int|min:0',
                'title' => 'required|string|min:2|max:100',
                'seo_description' => 'nullable|string|min:0|max:255',
                'seo_keywords' => 'nullable|string|min:0|max:255',
                'advantages' => 'required|string',
                'description' => 'nullable|string',
                'image_site' => 'required|string',
                'image' => 'nullable|string',
                'gallery' => 'required|array',
                'functional' => 'required|array',
                'tariffs' => 'required|array',
                'system_versions' => 'required|string|min:4|max:100',
                'system_auth' => 'required|string|min:4|max:100',
                'link_video' => 'nullable|string|min:1|max:100',
                'alias' => 'required|string|min:2|max:100',
                'status_id' => 'required|int',
                'hack_status' => 'required|int|between:0,6',
                'count_max' => 'required|int|between:0,1',
                'visibility' => 'required|int|between:0,3',
                'has_spoiler' => 'required|int|between:0,1',
                'disable_web_page_preview' => 'required|int|between:0,1',
            ]);

            $validator->setAttributeNames([
                'title' => 'название',
                'advantages' => 'приемущества',
                'image_site' => 'обложка для сайта',
                'gallery' => 'галерея изображений',
                'functional' => 'функционал',
                'tariffs' => 'тарифы',
                'system_versions' => 'поддерживаемые языки',
                'system_auth' => 'авторизация в чите',
                'alias' => 'короткий адрес',
                'status_id' => 'статус чита',
                'hack_status' => 'взлом',
                'count_max' => 'покупка только один раз',
                'visibility' => 'видимость',
                'has_spoiler' => 'спойлер на изображение',
                'disable_web_page_preview' => 'предпросмотр ссылок',
            ]);


            // if($request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_no_image')){
            //     throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
            // }

            // if(!$request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_image')){
            //     throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_image').' символов.');
            // }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $description = '';
            $image_db = '';
            $image_site_db = '';
            $seo_keywords = '';
            $seo_description = '';
            $link_video = '';

            if($request->filled('link_video')){
                $link_video = $request->link_video;
            }
            if($request->filled('seo_keywords')){
                $seo_keywords = $request->seo_keywords;
            }
            if($request->filled('seo_description')){
                $seo_description = $request->seo_description;
            }
            if($request->filled('description')){
                $description = $request->description;
            }
            if($request->filled('image')){
                $image_db = substr($request->image, 1);
                $r = Attach::where('id', $image_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }
            if($request->filled('image_site')){
                $image_site_db = substr($request->image_site, 1);
                $r = Attach::where('id', $image_site_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }

            $date_now = Carbon::now()->timestamp;

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            if($request->cid != 0) {
                $category = Category::where('sid', $shop->id)
                    ->where('id', $request->cid)
                    ->first();

                if (!$category) {
                    throw new Exception('Категория не найдена.', 1);
                }
            }

            $check = Product::checkAlias($request->alias);
            if($check) {throw new Exception('Такой короткий адрес уже существует.');}

            $lines = explode("\n", $request->advantages);
            $lines = array_filter($lines, 'strlen');
            $advantages = json_encode(array_values($lines));


            $jsonArray = [];

            $idValues = ['visuals', 'aimbot', 'misc'];

            foreach ($request->functional as $index => $inputString) {
                $stringArray = explode("\n", $inputString);
                $stringArray = array_filter($stringArray, 'strlen');

                // Определяем значение для ключа "id" с помощью зацикленного массива $idValues
                $itemId = $idValues[$index % count($idValues)];

                $jsonItem = [
                    "id" => $itemId,
                    "title" => array_shift($stringArray),
                    "lines" => array_values($stringArray)
                ];

                $jsonArray[] = $jsonItem;
            }

            $functional = json_encode($jsonArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


            $sql = [
                'cid' => $request->cid,
                'sid' => $shop->id,
                'title' => $request->title,
                'seo_description' => $seo_description,
                'seo_keywords' => $seo_keywords,
                'description' => $description,
                'advantages' => $advantages,
                'image' => $image_db,
                'image_site' => $image_site_db,
                'gallery' => json_encode($request->gallery),
                'functional' => $functional,
                'link_video' => $link_video,
                'system_platforms' => '[]',
                'system_versions' => $request->system_versions,
                'system_auth' => $request->system_auth,
                'alias' => $request->alias,
                'price' => 0,
                'price_type' => 0,
                'currency' => 0,
                'status' => 0,
                'count_views' => 0,
                'count_min' => 1,
                'count_max' => $request->count_max,
                'count_sales' => 0,
                'count_all' => 0,
                'count_type' => 0,
                'is_endless' => 0,
                'is_root' => 0,
                'is_jailbreak' => 0,
                'hack_status' => $request->hack_status,
                'image_spoiler' => $request->has_spoiler,
                'disable_web_page_preview' => $request->disable_web_page_preview,
                'sort' => 0,
                'status_id' => $request->status_id,
                'visibility' => $request->visibility,
                'updated_at' => $date_now,
                'created_at' => $date_now,
            ];

            $pid = Product::insertGetId($sql);

            if ($pid) {

                foreach ($request->tariffs as $key => $tariff) {
                    Tariff::insert([
                        'sid' => $shop->id,
                        'pid' => $pid,
                        'title' => $tariff['days'],
                        'price' => $tariff['price'],
                        'sort' => $key
                    ]);
                }

                return response()->json([
                    'ok' => true,
                    'description' => 'Сохранено',
                    'result' => [
                        'id' => $pid
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update($id, Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'products.edit')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'cid' => 'required|int|min:0',
                'title' => 'required|string|min:2|max:100',
                'seo_description' => 'nullable|string|min:0|max:255',
                'seo_keywords' => 'nullable|string|min:0|max:255',
                'advantages' => 'required|string',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'image_site' => 'required|string',
                'gallery' => 'required|array',
                'functional' => 'required|array',
                'tariffs' => 'required|array',
                'system_versions' => 'required|string|min:4|max:100',
                'system_auth' => 'required|string|min:4|max:100',
                'link_video' => 'nullable|string|min:1|max:100',
                'alias' => 'required|string|min:2|max:100',
                'status_id' => 'required|int',
                'hack_status' => 'required|int|between:0,6',
                'count_max' => 'required|int|between:0,1',
                'visibility' => 'required|int|between:0,3',
                'has_spoiler' => 'required|int|between:0,1',
                'disable_web_page_preview' => 'required|int|between:0,1',
            ]);

            $validator->setAttributeNames([
                'title' => 'название',
                'advantages' => 'приемущества',
                'image_site' => 'обложка для сайта',
                'gallery' => 'галерея изображений',
                'functional' => 'функционал',
                'tariffs' => 'тарифы',
                'system_versions' => 'поддерживаемые языки',
                'system_auth' => 'авторизация в чите',
                'alias' => 'короткий адрес',
                'status_id' => 'статус чита',
                'hack_status' => 'взлом',
                'count_max' => 'покупка только один раз',
                'visibility' => 'видимость',
                'has_spoiler' => 'спойлер на изображение',
                'disable_web_page_preview' => 'предпросмотр ссылок',
            ]);

            // if($request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_no_image')){
            //     throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_no_image').' символов.');
            // }

            // if(!$request->filled($request->image) && mb_strlen(strip_tags($request->description)) > config('app.tg_limit_text_image')){
            //     throw new Exception('Описание не должно превышать '.config('app.tg_limit_text_image').' символов.');
            // }

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $product = Product::getByID($shop->id, $id);
            if(!$product){throw new Exception("Данный товар не найден.");}

            $existingTariffs = Tariff::where('pid', $product->id)->pluck('id');

            $jsonTariffIds = collect($request->tariffs)->pluck('id');

            $tariffsToDelete = $existingTariffs->diff($jsonTariffIds);

            Tariff::whereIn('id', $tariffsToDelete)->delete();

            foreach ($request->tariffs as $key => $tariff) {
                if (isset($tariff['id']) && $tariff['id']) {
                    Tariff::where('id', $tariff['id'])->update([
                        'title' => $tariff['days'],
                        'price' => $tariff['price'],
                        'sort' => $key
                    ]);
                } else {
                    Tariff::insert([
                        'sid' => $shop->id,
                        'pid' => $product->id,
                        'title' => $tariff['days'],
                        'price' => $tariff['price'],
                        'sort' => $key
                    ]);
                }
            }


            $description = '';
            $image_db = '';
            $image_site_db = '';
            $seo_keywords = '';
            $seo_description = '';
            $link_video = '';

            \Log::info('EDIT_PRODUCT_DEBUG', [
                'request_link_video' => $request->link_video,
                'request_has' => $request->has('link_video'),
                'all_keys' => array_keys($request->all()),
                'product_id' => $request->id,
            ]);

            if($request->filled('link_video')){
                $link_video = $request->link_video;
            }

            \Log::info('EDIT_PRODUCT_RESULT', ['link_video' => $link_video]);

            if($request->filled('seo_keywords')){
                $seo_keywords = $request->seo_keywords;
            }
            if($request->filled('seo_description')){
                $seo_description = $request->seo_description;
            }
            if($request->filled('description')){
                $description = $request->description;
            }
            if($request->filled('image')){
                $image_db = substr($request->image, 1);
                $r = Attach::where('id', $image_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }
            if($request->filled('image_site')){
                $image_site_db = substr($request->image_site, 1);
                $r = Attach::where('id', $image_site_db)->first();
                if(!$r){throw new Exception('Изображение не найдено.', 1);}
            }

            $date_now = Carbon::now()->timestamp;

            if($request->cid != 0) {
                $category = Category::where('sid', $shop->id)
                    ->where('id', $request->cid)
                    ->first();

                if (!$category) {
                    throw new Exception('Категория не найдена.', 1);
                }
            }

            if($request->alias != $product->alias){
                $check = Product::checkAlias($request->alias);
                if($check) {throw new Exception('Такой короткий адрес уже существует.');}
            }

            $lines = explode("\n", $request->advantages);
            $lines = array_filter($lines, 'strlen');
            $advantages = json_encode(array_values($lines));

            $jsonArray = [];

            $idValues = ['visuals', 'aimbot', 'misc'];

            foreach ($request->functional as $index => $inputString) {
                $stringArray = explode("\n", $inputString);
                $stringArray = array_filter($stringArray, 'strlen');

                // Определяем значение для ключа "id" с помощью зацикленного массива $idValues
                $itemId = $idValues[$index % count($idValues)];

                $jsonItem = [
                    "id" => $itemId,
                    "title" => array_shift($stringArray),
                    "lines" => array_values($stringArray)
                ];

                $jsonArray[] = $jsonItem;
            }

            $functional = json_encode($jsonArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


            $sql = [
                'cid' => $request->cid,
                'sid' => $shop->id,
                'title' => $request->title,
                'seo_description' => $seo_description,
                'seo_keywords' => $seo_keywords,
                'description' => $description,
                'advantages' => $advantages,
                'image' => $image_db,
                'image_site' => $image_site_db,
                'gallery' => json_encode($request->gallery),
                'functional' => $functional,
                'link_video' => $link_video,
                'system_platforms' => '[]',
                'system_versions' => $request->system_versions,
                'system_auth' => $request->system_auth,
                'alias' => $request->alias,
                'count_max' => $request->count_max,
                'hack_status' => $request->hack_status,
                'image_spoiler' => $request->has_spoiler,
                'disable_web_page_preview' => $request->disable_web_page_preview,
                'status_id' => $request->status_id,
                'visibility' => $request->visibility,
                'updated_at' => $date_now,
            ];

            $update = Product::where('sid', $shop->id)
                ->where('id', $id)
                ->update($sql);

            return response()->json(['ok' => true, 'description' => 'Сохранено']);

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function selectAll()
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'products.all')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $products = Product::where('sid', $shop->id)->orderBy('sort')->get();

            $all = [];

            foreach ($products as $p) {

                $hack_status = HackStatus::getByID($p->hack_status);

                $status = '';
                if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                $all[] = [
                    'id' => $p->id,
                    'title' => $p->title.' '.$status
                ];
            }

            return response()->json(['ok' => true, 'result' => $all]);

        } catch (Exception $e){
            return response()->json(['ok' => false,  'description' => $e->getMessage()], 200);
        }
    }

    public function uploadVideo(Request $request)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'products.all')->allow;
            if (!$access) { throw new Exception('Access Denied'); }

            $validator = Validator::make($request->all(), [
                'video' => 'required|file|mimes:mp4,webm,mov,avi|max:524288',
            ]);

            if ($validator->fails()) {
                return response()->json(['ok' => false, 'description' => $validator->errors()->first()]);
            }

            $file = $request->file('video');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
            $file->move(public_path('uploads/videos'), $filename);

            $url = '/uploads/videos/' . $filename;

            return response()->json(['ok' => true, 'url' => $url]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

}
