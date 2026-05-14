<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\StatusCheat;

class Product extends Model
{

    public $timestamps = false;
    protected $table = 'products';
    protected $fillable = [
        'cid',
        'sid',
        'title',
        'seo_description',
        'seo_keywords',
        'description',
        'advantages',
        'image_site',
        'image',
        'image_spoiler',
        'gallery',
        'functional',
        'alias',
        'link_video',
        'system_platforms',
        'system_versions',
        'system_auth',
        'status_id',
        'status',
        'hack_status',
        'is_root',
        'is_jailbreak',
        'price',
        'price_type',
        'currency',
        'disable_web_page_preview',
        'count_views',
        'count_min',
        'count_max',
        'count_sales',
        'count_all',
        'count_type',
        'delivery',
        'is_endless',
        'sort',
        'visibility',
        'created_at',
        'updated_at',
    ];


    public static function getByID($sid, $id){
        return Product::where('sid', $sid)->where('id', $id)->first();
    }

    public static function checkAlias($alias){
        return Product::where('alias', $alias)->first();
    }

    public static function getCountByCatID($id){
        return Product::where('cid', $id)->whereIn('visibility', [1, 2])->where('status_id', '>', 0)->count();
    }

    public static function getByAliasWeb($sid, $alias){
        return Product::where('sid', $sid)->where('alias', $alias)->whereIn('visibility', [1, 2])->first();
    }

    public static function addView($id, $sid){
        return Product::where('id', $id)->where('sid', $sid)->increment('count_views', 1);
    }

    public static function list_web_by_cid($cid)
    {
        try {

            $shop = Shop::getDefault();
            $shop_id = $shop->id;

            $result = Product::where('sid', $shop_id)->where('cid', $cid)->whereIn('visibility', [1, 2])->orderBy('sort')->get();

            $all = [];

            foreach ($result as $c) {

                $status_title = '';
                $status_class = '';

                $status = StatusCheat::getByID($c->status_id);

                if (!$status) {
                    continue;
                }

                if ($status->status == 0) {
                    $status_title = 'Неизвестно';
                    $status_class = 'undetected';
                }
                if ($status->status == 1) {
                    $status_title = 'Рекомендуем';
                    $status_class = 'undetected';
                }
                if ($status->status == 2) {
                    $status_title = 'Не рекомендуем';
                    $status_class = 'on-update';
                }
                if ($status->status == 3) {
                    $status_title = 'На обновлении';
                    $status_class = 'on-update';
                }
                if ($status->status == 4) {
                    $status_title = 'На страх и риск';
                    $status_class = 'risk';
                }

                // if($c->status == 0){$status_title = 'Не обнаружен';$status_class = 'undetected';}
                // if($c->status == 1){$status_title = 'На обновлении';$status_class = 'on-update';}
                // if($c->status == 2){$status_title = 'Обнаружен';$status_class = 'detected';}

                $image = $c->image;
                if($c->image == ''){
                    $image = '/icheat';
                }

                $image_site = $c->image_site;
                if($c->image_site == ''){
                    $image_site = '/icheat';
                }

                $all[] = [
                    'id' => $c->id,
                    'sid' => $c->sid,
                    'cid' => $c->cid,
                    'title' => $c->title,
                    'advantages' => $c->advantages,
                    'image' => $image,
                    'image_site' => $image_site,
                    'alias' => $c->alias,
                    'status' => $c->status,
                    'hack_status' => $c->hack_status,
                    'status_title' => $status_title,
                    'status_class' => $status_class,
                ];
            }

            return response()->json([
                'ok' => true,
                'result' => $all
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }

    }

    public static function list_web_by_title($cid, $title)
    {
        try {

            $shop = Shop::getDefault();
            $shop_id = $shop->id;

            $result = Product::where('sid', $shop_id)->where('cid', $cid)->where('title', $title)->whereIn('visibility', [1, 2])->orderBy('sort')->get();

            $all = [];

            foreach ($result as $c) {

                $all[] = [
                    'title' => $c->title,
                    'status' => $c->status,
                ];
            }

            return response()->json([
                'ok' => true,
                'result' => $all
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }

    }

    public static function list_web_recommendations($except_id = 0)
    {
        try {

            $shop = Shop::getDefault();
            $shop_id = $shop->id;

            $result = Product::where('sid', $shop_id)->whereIn('visibility', [1, 2])->where('id', '!=', $except_id)->inRandomOrder()->get();

            $all = [];

            foreach ($result as $c) {

                $status = StatusCheat::getByID($c->status_id);

                if (!$status) {
                    continue;
                }

                if ($status->status == 0) {
                    $status_title = 'Неизвестно';
                    $status_class = 'undetected';
                }
                if ($status->status == 1) {
                    $status_title = 'Рекомендуем';
                    $status_class = 'undetected';
                }
                if ($status->status == 2) {
                    $status_title = 'Не рекомендуем';
                    $status_class = 'on-update';
                }
                if ($status->status == 3) {
                    $status_title = 'На обновлении';
                    $status_class = 'on-update';
                }
                if ($status->status == 4) {
                    $status_title = 'На страх и риск';
                    $status_class = 'risk';
                }

                $image = $c->image;
                if($c->image == ''){
                    $image = '/icheat';
                }

                $image_site = $c->image_site;
                if($c->image == ''){
                    $image_site = '/icheat';
                }

                if($c->cid != 0){
                    $alias_two = Category::getByID($c->sid, $c->cid);
                    $alias_one = Category::getByID($c->sid, $alias_two->cid);
                    $alias = '/'.$alias_one->alias.'/'.$alias_two->alias.'/'.$c->alias;
                }

                $all[] = [
                    'id' => $c->id,
                    'cid' => $c->cid,
                    'title' => $c->title,
                    'advantages' => $c->advantages,
                    'image' => $image,
                    'image_site' => $image_site,
                    'alias' => $alias,
                    'hack_status' => $c->hack_status,
                    'status_title' => $status_title,
                    'status_class' => $status_class,
                ];
            }

            return response()->json([
                'ok' => true,
                'result' => $all
            ]);

        } catch (Exception $e){
            \Log::warning('Product::list_web_recommendations failed', [
                'except_id' => $except_id,
                'error'     => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'result' => [],
            ]);
        }

    }

}
