<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class Member extends Model
{

    public $timestamps = false;
    protected $table = 'users';
    protected $fillable = [
        'rid',
        'tid',
        'sid',
        'mid',
        'email',
        'username',
        'password',
        'tz',
        'locale',
        'currency',
        'remember_token',
        'remember_code',
        'balance_main',
        'balance_affiliate',
        'ref_percent',
        'ref_code',
        'role_id',
        'is_ban',
        'is_active',
        'is_agreement',
        'email_notify_tickets',
        'email_notify_orders',
        'email_notify_status_changed',
        'tstep',
        'tdata',
        'created_at'
    ];

    public static function getAll(){
        return Member::get();
    }

    public static function getTgAll(){
        return Member::where('tid', '!=', 0)->get();
    }
    public static function searchByTID($id){
        return Member::where('tid', $id)->first();
    }

    public static function getByID($id, $sid){
        return Member::where('id', $id)->where('sid', $sid)->first();
    }

    public static function getByRole(){
        return Member::where('role_id', '!=', 0)->get();
    }
    public static function getDefaultByID($id){
        return Member::where('id', $id)->first();
    }
    public static function getByIDFromTicket($id){
        return Member::where('id', $id)->first();
    }
    public static function getByCID($cid, $sid){
        return Member::where('tid', $cid)->where('sid', $sid)->first();
    }
    public static function getByRefCode($ref_code){
        return Member::where('ref_code', $ref_code)->first();
    }
    public static function getByEmail($email){
        return Member::where('email', $email)->first();
    }
    public static function getByTID($id, $sid){
        return Member::where('tid', $id)->where('sid', $sid)->first();
    }
    public static function getByToken($token){
        return Member::where('remember_token', $token)->first();
    }
    public static function getCheckConnectTid($tid){
        return Member::where('tid', $tid)->first();
    }

    public static function transferBalance($id, $sid){
        $member = Member::getByID($id, $sid);
        $balance_affiliate = $member->balance_affiliate;
        $balance_main = $member->balance_main;
        if(Member::where('id', $id)->where('sid', $sid)->update(['balance_affiliate' => 0])){
            return Member::where('id', $id)->where('sid', $sid)->update(['balance_main' => $balance_main+$balance_affiliate]);
        }
    }

    public static function getCountByActive($is_active){
        return Member::where('is_active', $is_active)->count();
    }

    public static function getCountAll(){
        return Member::where('tid', '!=', 0)->count();
    }

    public static function getCountTableAll(){
        return Member::count();
    }

    public static function getCountReferralsByMID($id, $sid){
        return Member::where('mid', $id)->where('sid', $sid)->count();
    }

    public static function getCountReferralsByUserID($id){
        return Member::where('rid', $id)->count();
    }

    public static function add($id, $sid, $rid, $password){
        $set_shop = ShopSettings::getDefault();
        $now = strtotime('NOW');
        return Member::create([
            'tid' => $id,
            'rid' => $rid,
            'sid' => $sid,
            'mid' => 0,
            'email' => '',
            'username' => 't'.$id,
            'password' => Hash::make($password),
            'tz' => 'Europe/Moscow',
            'locale' => 'RU',
            'currency' => 'RUB',
            'remember_token' => Str::random(32),
            'remember_code' => '',
            'balance_main' => 0,
            'balance_affiliate' => 0,
            'ref_percent' => $set_shop->ref_percent,
            'ref_code' => Str::random(10),
            'role_id' => 0,
            'is_ban' => 0,
            'is_active' => 1,
            'is_agreement' => 0,
            'email_notify_tickets' => 1,
            'email_notify_orders' => 1,
            'email_notify_status_changed' => 1,
            'tstep' => 0,
            'tdata' => '{}',
            'created_at' => $now
        ]);
    }

    public static function import_add($id, $ref_id, $ref_balance, $ref_percent, $created_at){

        $shop = Shop::getDefault();

        return Member::insertGetId([
            'tid' => $id,
            'rid' => $ref_id,
            'sid' => $shop->id,
            'mid' => 0,
            'email' => '',
            'username' => 't'.$id,
            'password' => Hash::make(Str::random(12)),
            'tz' => 'Europe/Moscow',
            'locale' => 'RU',
            'currency' => 'RUB',
            'remember_token' => Str::random(32),
            'remember_code' => '',
            'balance_main' => 0,
            'balance_affiliate' => $ref_balance,
            'ref_percent' => $ref_percent,
            'ref_code' => Str::random(10),
            'role_id' => 0,
            'is_ban' => 0,
            'is_active' => 1,
            'is_agreement' => 0,
            'email_notify_tickets' => 1,
            'email_notify_orders' => 1,
            'email_notify_status_changed' => 1,
            'tstep' => 0,
            'tdata' => '{}',
            'created_at' => $created_at,
        ]);
    }

    public static function edit($id, $sid, $key, $value){
        return Member::where('id', $id)->where('sid', $sid)->update([$key => $value]);
    }
    public static function changeTokenByID($id, $sid){
        $new_token = Str::random(32);
        Member::where('id', $id)->where('sid', $sid)->update(['remember_token' => $new_token]);
        return $new_token;
    }
    public static function editByID($id, $sid, $key, $value){
        return Member::where('id', $id)->where('sid', $sid)->update([$key => $value]);
    }

}
