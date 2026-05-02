<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tariff extends Model
{

    public $timestamps = false;
    protected $table = 'tariffs';
    protected $fillable = [
        'id',
        'sid',
        'pid',
        'title',
        'price'
    ];

    public static function num_decline( $number, $titles, $show_number = true ){

        if( is_string( $titles ) ){
            $titles = preg_split( '/, */', $titles );
        }

        if( empty( $titles[2] ) ){
            $titles[2] = $titles[1];
        }

        $cases = [ 2, 0, 1, 1, 1, 2 ];

        $intnum = abs( (int) strip_tags( $number ) );

        $title_index = ( $intnum % 100 > 4 && $intnum % 100 < 20 )
            ? 2
            : $cases[ min( $intnum % 10, 5 ) ];

        return ( $show_number ? "$number " : '' ) . $titles[ $title_index ];
    }


    public static function getByID($sid, $pid, $id){
        return Tariff::where('sid', $sid)->where('pid', $pid)->where('id', $id)->first();
    }

    public static function getByDays($pid, $days){
        return Tariff::where('pid', $pid)->where('title', $days)->first();
    }
    public static function getListByPid($sid, $pid){

        $tariffs = Tariff::where('sid', $sid)->where('pid', $pid)->orderBy('title')->get();

        $result = [];
        foreach ($tariffs as $tariff){
            $result[] = ['id' => $tariff->id, 'title' => Tariff::num_decline($tariff->title, ['день','дня','дней'] ), 'price' => $tariff->price];
        }

        return $result;
    }
    public static function getBySort($pid, $sort){
        return Tariff::where('pid', $pid)->where('sort', $sort)->first();
    }

}
