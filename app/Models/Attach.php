<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attach extends Model
{
    public $timestamps = false;
    protected $table = 'attachments';
    public $incrementing = false;
    protected $id = 'string';
    protected $fillable = [
        'id',
        'uid',
        'title',
        'ext',
        'size',
        'type',
        'uploaded_at'
    ];

    public static function getById($id){
        return Attach::where('id', $id)->first();
    }

    public static function getPathById($id){
        $attach = Attach::where('id', $id)->first();
        $file_path = Storage::disk('public')->path('covers/'.$id.'.'.$attach->ext);
        return $file_path;
    }

}
