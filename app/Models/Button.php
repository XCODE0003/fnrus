<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Button extends Model
{

    public $timestamps = false;
    protected $table = 'buttons';
    protected $fillable = [
        'sid',
        'title',
        'text',
        'image',
        'disable_web_page_preview',
        'image_spoiler',
        'buttons',
        'sort',
        'type',
        'visible',
        'updated_at',
        'created_at'
    ];

    public static function getBySearchTitle($search){
        return Button::where('title', 'like', '%' . $search . '%')->where('visible', 1)->first();
    }

    public static function getByTitle($sid, $title){
        return Button::where('sid', $sid)->where('title', $title)->where('visible', 1)->first();
    }

    public static function getByType($sid, $type){
        return Button::where('sid', $sid)->where('type', $type)->where('visible', 1)->first();
    }
    public static function getMainByShopID($id){
        $buttons = Button::where('sid', $id)->where('visible', 1)->orderBy('sort')->get();

        $all = [];

        foreach ($buttons as $b) {
            $all[] = $b->title;
        }

        return $all;
    }

    public static function getByShopID($id){
        $buttons = Button::where('sid', $id)->orderBy('sort')->get();

        $all = [];

        foreach ($buttons as $b) {
            $all[] = ["id" => $b->id, "title" => $b->title];
        }

        return $all;
    }


    public static function getProductsByShopID($id, $count_products){
        $results = Product::where('sid', $id)->whereIn('visibility', [1, 3])->orderBy('sort')->get();

        $all = [];

        foreach ($results as $item) {
            $all[] = ["text" => $item->title, "callback_data" => "products/".$item->id."/".$item->count_min];
        }

        $kp = array_chunk($all, $count_products);
        $back[] = ["text" => __('bot.btn_main'), "callback_data" => "menu/main"];
        array_push($kp, $back);

        return json_encode(["inline_keyboard" => $kp]);
    }


    public static function getProductsByCatID($sid, $cid, $count_products){
        $results = Product::where('sid', $sid)->where('cid', $cid)->whereIn('visibility', [1, 3])->orderBy('sort')->get();
        $result = Category::where('sid', $sid)->where('id', $cid)->whereIn('visibility', [1, 3])->first();

        $type_prod = '/all';
        $type_cats = '';
        if($result->image != ''){
            $type_prod = '/with_photo';
            $type_cats = $type_prod;
        }

        $all = [];

        foreach ($results as $item) {
            $hack_status = HackStatus::getByID($item->hack_status);

            $status = '';
            if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}


            $all[] = ["text" => $item->title.' '.$status, "callback_data" => "products/".$item->id."/".$item->count_min];
        }

        $kp = array_chunk($all, $count_products);

        if($result->cid == 0) {
            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products".$type_prod];
        } else {
            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "categories/".$result->cid.$type_cats];
        }
//        $ki[] = ["text" => __('bot.btn_share_link'), "callback_data" => "categories/".$result->cid."/share"];
        array_push($kp, $ki);

        return json_encode(["inline_keyboard" => $kp]);
    }


    public static function getButtonsByID($id){

        $result = Button::where('id', $id)->first();
        if(!$result){return false;}

        $buttons = json_decode($result->buttons, true);

        $all = [];

        foreach ($buttons as $b) {
            if(isset($b['url'])) {
                $all[] = ['text' => $b['text'], 'url' => $b['url']];
            } else {
                $all[] = ['text' => $b['text'], 'callback_data' => $b['callback_data']];
            }
        }

        $kp = array_chunk($all, 1);
        return json_encode(["inline_keyboard" => $kp]);
    }

    public static function getCategoriesByCatID($sid, $cid, $count_categories){
        $results = Category::where('sid', $sid)->where('cid', $cid)->whereIn('visibility', [1, 3])->orderBy('sort')->get();
        $result = Category::where('sid', $sid)->where('id', $cid)->whereIn('visibility', [1, 3])->first();

        $type_prod = '/all';
        $type_cats = '';
        if($result->image != ''){
            $type_prod = '/with_photo';
            $type_cats = $type_prod;
        }

        $all = [];

        foreach ($results as $item) {
            $all[] = ["text" => $item->title, "callback_data" => "categories/".$item->id.$type_cats];
        }

        $kp = array_chunk($all, $count_categories);

        if($result->cid == 0) {
            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products".$type_prod];
        } else {
            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "categories/".$result->cid.$type_cats];
        }

//        $ki[] = ["text" => __('bot.btn_share_link'), "callback_data" => "categories/".$result->cid."/share"];
        array_push($kp, $ki);

        return json_encode(["inline_keyboard" => $kp]);
    }

    public static function getCategoriesByShopID($id, $count_categories){
        $results = Category::where('sid', $id)->where('cid', 0)->whereIn('visibility', [1, 3])->orderBy('sort')->get();

        $all = [];

        foreach ($results as $item) {
            $all[] = ["text" => $item->title, "callback_data" => "categories/".$item->id];
        }

        $kp = array_chunk($all, $count_categories);
        $back[] = ["text" => __('bot.btn_main'), "callback_data" => "menu/main"];
        array_push($kp, $back);

        return json_encode(["inline_keyboard" => $kp]);
    }

    public static function getProfileByShopID($id, $count){

        $b = Button::getByType($id, 1);

        $all[] = ["text" => __('bot.btn_my_orders'), "callback_data" => "profile/orders/page/1"];
        $all[] = ["text" => __('bot.btn_topup'), "callback_data" => "profile/balance/topup"];
        $all[] = ["text" => __('bot.btn_affiliate'), "callback_data" => "profile/affiliate"];

        $kp = array_chunk($all, $count);
        $back[] = ["text" => __('bot.btn_main'), "callback_data" => "menu/main"];
        array_push($kp, $back);

        return json_encode(["inline_keyboard" => $kp]);
    }
}
