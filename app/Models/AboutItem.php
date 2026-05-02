<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutItem extends Model
{
    protected $table = 'about_items';
    public $timestamps = false;
    protected $fillable = [
        'icon',
        'label_ru',
        'label_en',
        'url',
        'url_text',
        'sort_order'
    ];

    public static function getAllSorted(){
        return AboutItem::orderBy('sort_order')->get();
    }

    public function getLocalizedLabelAttribute(){
        $locale = app()->getLocale();
        return $locale === 'en' ? ($this->label_en ?: $this->label_ru) : $this->label_ru;
    }
}
