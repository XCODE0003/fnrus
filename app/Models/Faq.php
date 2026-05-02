<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    public $timestamps = false;
    protected $table = 'faq';
    protected $fillable = [
        'id',
        'in_id',
        'question',
        'question_en',
        'answer',
        'answer_en',
        'sort',
        'visibility',
        'updated_at'
    ];

    public function getLocalizedQuestionAttribute()
    {
        if (app()->getLocale() === 'en' && !empty($this->question_en)) {
            return $this->question_en;
        }
        return $this->question;
    }

    public function getLocalizedAnswerAttribute()
    {
        if (app()->getLocale() === 'en' && !empty($this->answer_en)) {
            return $this->answer_en;
        }
        return $this->answer;
    }
    public static function getList(){
        return Faq::orderBy('sort')->get();
    }

    public static function getListByInstruction($id){
        return Faq::orderBy('sort')->where('in_id', $id)->get();
    }
}
