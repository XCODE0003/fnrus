<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'categories';
    protected $fillable = [
        'cid',
        'sid',
        'title',
        'title_en',
        'seo_description',
        'seo_keywords',
        'description',
        'image',
        'image_site',
        'image_spoiler',
        'alias',
        'disable_web_page_preview',
        'display_products',
        'count_views',
        'count_column',
        'sort',
        'visibility',
        'updated_at',
        'created_at'
    ];

    public function getLocalizedTitleAttribute()
    {
        if (app()->getLocale() === 'en' && !empty($this->title_en)) {
            return $this->title_en;
        }
        return $this->title;
    }

    public static function getByID($sid, $id){
        return Category::where('sid', $sid)->where('id', $id)->first();
    }

    public static function getByAlias($sid, $alias){
        return Category::where('sid', $sid)->where('alias', $alias)->first();
    }

    public static function getByAliasWeb($sid, $cid, $alias){
        return Category::where('sid', $sid)->where('cid', $cid)->where('alias', $alias)->first();
    }

    public static function getByCID($sid, $cid){
        return Category::where('sid', $sid)->where('cid', $cid)->first();
    }

    public static function getLastSubcategory($cid) {
        return Category::where('id', $cid)->whereIn('visibility', [1, 2])->get();
    }

    public static function getCategoriesByCID($cid) {
        return Category::where('cid', $cid)->whereIn('visibility', [1, 2])->get();
    }
    public static function getCountSubcatByCatID($sid, $id){
        return Category::where('sid', $sid)->where('cid', $id)->whereIn('visibility', [1, 2])->count();
    }
    public static function addView($id, $sid){
        return Category::where('id', $id)->where('sid', $sid)->increment('count_views', 1);
    }

    public static function list_web_by_cid($cid)
    {
        try {

            $shop = Shop::getDefault();
            $shop_id = $shop->id;

            $categories = Category::where('sid', $shop_id)->where('cid', $cid)->whereIn('visibility', [1, 2])->orderBy('sort')->get();

            $all = [];

            foreach ($categories as $c) {

                $cats = Category::getCategoriesByCID($c->id);

                $count_products = 0;
                $hasPc = false;
                $hasMobile = false;

                foreach ($cats as $cs){
                    $count_products += Product::getCountByCatID($cs->id);

                    $platformName = mb_strtolower(trim($cs->title));
                    if (in_array($platformName, ['пк', 'pc', 'gameloop', 'эмуляторы'])) {
                        $hasPc = true;
                    } elseif (in_array($platformName, ['ios', 'android'])) {
                        $hasMobile = true;
                    }
                }

                $platforms = [];
                if ($hasPc) { $platforms[] = 'pc'; }
                if ($hasMobile) { $platforms[] = 'mobile'; }
                if (empty($platforms)) { $platforms = ['pc', 'mobile']; }

                $image = '';
                if($c->image != ''){
                    $image = 'i'.$c->image;
                }

                $image_site = '';
                if($c->image_site != ''){
                    $image_site = 'i'.$c->image_site;
                }

                $all[] = [
                    'id' => $c->id,
                    'cid' => $c->cid,
                    'title' => $c->localized_title,
                    'image' => $image,
                    'image_site' => $image_site,
                    'count_products' => $count_products,
                    'platforms' => implode(' ', $platforms),
                    'alias' => $c->alias,
                    'description' => $c->description,
                ];
            }

            return response()->json([
                'ok' => true,
                'result' => $all
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }

    }

    public static function list_web_by_title($cid, $title)
    {
        try {

            $shop = Shop::getDefault();
            $shop_id = $shop->id;

            $result = Category::where('sid', $shop_id)->where('cid', $cid)->where('title', $title)->whereIn('visibility', [1, 2])->orderBy('sort')->get();

            $all = [];

            foreach ($result as $c) {

                $all[] = [
                    'title' => $c->localized_title,
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

            $categories = Category::where('sid', $shop_id)->whereIn('visibility', [1, 2])->where('cid', 0)->where('id', '!=', $except_id)->inRandomOrder()->get();

            $all = [];

            foreach ($categories as $c) {

                $cats = Category::getCategoriesByCID($c->id);

                $count_products = 0;

                foreach ($cats as $cs){
                    $count_products += Product::getCountByCatID($cs->id);
                }

                $image = '';
                if($c->image != ''){
                    $image = 'i'.$c->image;
                }

                $image_site = '';
                if($c->image_site != ''){
                    $image_site = 'i'.$c->image_site;
                }

                $all[] = [
                    'id' => $c->id,
                    'cid' => $c->cid,
                    'title' => $c->localized_title,
                    'image' => $image,
                    'image_site' => $image_site,
                    'count_products' => $count_products,
                    'alias' => $c->alias,
                    'description' => $c->description,
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

}
