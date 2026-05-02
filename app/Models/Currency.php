<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{

    public $timestamps = false;
    protected $table = 'currencies';

    public static function cron_convert(){

        $currencies = ['AZN', 'KZT', 'RUB', 'UAH', 'USD', 'UZS'];

        foreach($currencies as $cur) {

            foreach ($currencies as $c) {
                $value = Currency::convertOnline($c, $cur, 1);
                Currency::where('id', $c)->update([$cur => $value]);
                sleep(2);
            }

        }


    }


    public static function convertOnline($from, $to, $amount){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://currency-converter5.p.rapidapi.com/currency/convert?format=json&from='.$from.'&to='.$to.'&amount='.$amount,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-rapidapi-key: '.config('app.currency_api_key'),
                'x-rapidapi-host: currency-converter5.p.rapidapi.com',
                'useQueryString:  true'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $decode = json_decode($response, true);

        return $decode['rates'][$to]['rate_for_amount'];

    }

    public static function convertToRub($from, $to, $amount){
        if ($from == $to) {
            return round($amount);
        }
        $result = Currency::where('id', $from)->first();

        if ($from == 'USD' && $to == 'RUB'){
            $sum = round($result->rub * $amount/10)*10;
            return $sum;
        }
        if ($from == 'RUB' && $to == 'RUB'){
            return round($amount);
        }
    }

    public static function convert($from, $to, $amount){

        // USDT is treated 1:1 with USD for conversion purposes (stablecoin).
        if ($from == 'USDT') {
            return Currency::convert('USD', $to, $amount);
        }
        if ($to == 'USDT') {
            return Currency::convert($from, 'USD', $amount);
        }

        // KGS rates are not in the currencies table; bridge through USD using
        // approximate market rate 1 USD ≈ 87.5 KGS.
        if ($from == 'KGS' && $to == 'KGS') {
            return $amount;
        }
        if ($from == 'KGS') {
            $usd_amount = $amount / 87.5;
            return Currency::convert('USD', $to, $usd_amount);
        }
        if ($to == 'KGS') {
            $usd_amount = Currency::convert($from, 'USD', $amount);
            if ($usd_amount === null) return null;
            return $usd_amount * 87.5;
        }

        $result = Currency::where('id', $from)->first();

        if ($from == 'USD' && $to == 'RUB'){
            $sum = round($result->rub * $amount/10)*10;
            return $sum;
        }

        if ($from == 'RUB' && $to == 'USD'){
            $sum = $result->usd * $amount;
            return $sum;
        }

        if ($from == 'USD' && $to == 'USD'){
            $sum = $result->usd * $amount;
            return $sum;
        }

        if ($from == 'USD' && $to == 'AZN'){
            $sum = $result->azn * $amount;
            return $sum;
        }

        if ($from == 'AZN' && $to == 'USD'){
            $sum = $result->usd * $amount;
            return $sum;
        }


        if ($from == 'USD' && $to == 'UAH'){
            $sum = $result->uah * $amount;
            return $sum;
        }

        if ($from == 'UAH' && $to == 'USD'){
            $sum = $result->usd * $amount;
            return $sum;
        }

        if ($from == 'USD' && $to == 'KZT'){
            $sum = $result->kzt * $amount;
            return $sum;
        }

        if ($from == 'KZT' && $to == 'USD'){
            $sum = $result->usd * $amount;
            return $sum;
        }

        if ($from == 'USD' && $to == 'UZS'){
            $sum = $result->uzs * $amount;
            return $sum;
        }

        if ($from == 'UZS' && $to == 'USD'){
            $sum = $result->usd * $amount;
            return $sum;
        }

        // RUB conversions
        if ($from == 'RUB' && $to == 'RUB'){
            return round($amount);
        }

        if ($from == 'RUB' && $to == 'AZN'){
            $sum = $result->azn * $amount;
            return $sum;
        }

        if ($from == 'RUB' && $to == 'UAH'){
            $sum = $result->uah * $amount;
            return $sum;
        }

        if ($from == 'RUB' && $to == 'KZT'){
            $sum = $result->kzt * $amount;
            return $sum;
        }

        if ($from == 'RUB' && $to == 'UZS'){
            $sum = $result->uzs * $amount;
            return $sum;
        }

        if ($from == 'AZN' && $to == 'RUB'){
            return $result->rub * $amount;
        }
        if ($from == 'UAH' && $to == 'RUB'){
            return $result->rub * $amount;
        }
        if ($from == 'KZT' && $to == 'RUB'){
            return $result->rub * $amount;
        }
        if ($from == 'UZS' && $to == 'RUB'){
            return $result->rub * $amount;
        }

        // Fallback: same currency
        if ($from == $to){
            return $amount;
        }

    }

}
