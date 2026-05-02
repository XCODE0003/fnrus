<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LinkAdUser extends Model
{

    public $timestamps = false;
    protected $table = 'links_ads_users';
    protected $fillable = ['id','link_id','created_at'];

    public static function getByLinkID($id, $link_id){
        return LinkAdUser::where('id', $id)->where('link_id', $link_id)->first();
    }
}
