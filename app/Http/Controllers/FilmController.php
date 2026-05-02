<?php

namespace App\Http\Controllers;

class FilmController
{
    public function index($id){

        $hash = base64_encode(urlencode('/iplayer/videodb.php?kp='.$id));

//        $result =  file_get_contents('https://6mar.lostfilma.net/iplayer/videodb.php?kp='.$id, false, $context);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://6mar.lostfilma.net/iplayer/videodb.php?kp='.$id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_PROXY, '141.95.91.119:64630');
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, '9pXra6nD:zXJnKUAF');
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);

        $headers = array();
        $headers[] = 'Host: 6mar.lostfilma.net';
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.3 Safari/605.1.15';
        $headers[] = 'Referer: https://6mar.lostfilma.net/iplayer/player.php?id='.$hash;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

}
