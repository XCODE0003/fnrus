<?php

namespace App\Models;

use App\Http\Controllers\SenderController;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class Sender extends Model
{

    public $timestamps = false;
    protected $table = 'senders';
    protected $fillable = ['id','sid','title','message','buttons','disable_web_page_preview','has_spoiler','forward_link','image','count_all','count_success','count_fail','type','status','started_at','updated_at','created_at'];

    public static function getByID($sid, $id){
        return Sender::where('sid', $sid)->where('id', $id)->first();
    }
    public static function deleteByID($sid, $id){
        return Sender::where('sid', $sid)->where('id', $id)->delete();
    }
    public static function getAllBySID($sid){
        return Sender::where('sid', $sid)->orderByDesc('created_at')->get();
    }
    /**
     * Все рассылки, которые УЖЕ должны были запуститься, но ещё в очереди.
     * Раньше тут было `where('started_at', $started_at)` (строгое равенство),
     * из-за чего если cron пропускал минуту по любой причине — рассылка
     * зависала в status=1 навсегда. Теперь сравниваем по `<=` и берём
     * первую, чтобы за один тик cron не пытался стартовать сразу
     * несколько тяжёлых задач параллельно.
     */
    public static function getByDate($started_at){
        return Sender::where('started_at', '<=', $started_at)
            ->where('status', 1)
            ->orderBy('started_at')
            ->orderBy('id')
            ->limit(1)
            ->get();
    }
    public static function changeByID($id, $sql){
        return Sender::where('id', $id)->update($sql);
    }

}
