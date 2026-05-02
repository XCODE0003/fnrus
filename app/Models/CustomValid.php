<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomValid extends Model
{
    public static function ref_code($code) {
        // Задаем паттерн для кода: состоит из 10 символов, включая заглавные буквы и цифры
        $pattern = '/^[a-zA-Z0-9]{10}$/';

        if (preg_match($pattern, $code)) {
            return true;
        }
        return false;
    }

}
