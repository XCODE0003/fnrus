<?php

namespace App\Http\Controllers;

use App\Models\Attach;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Shop;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class AttachmentController extends Controller
{

    public $user;

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
        } catch (Exception $e){
            return response([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function convertMb($kilobytes, $precision = 0) {
        $megabytes = $kilobytes / 1024;
        $format = number_format($megabytes, $precision, '', '.');
        return substr($format, 0, -2);
    }

    public function all()
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'files.all')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $attachments = Attach::where('type', 1)->orderBy('uploaded_at', 'DESC')->get();

            $all = [];

            foreach ($attachments as $a) {

                $all[] = [
                    'id' => $a->id,
                    'icon' => '<i class="far fa-file"></i>',
                    'title' => $a->title,
                    'ext' => $a->ext,
                    'size' => $this->convertMb($a->size).' MB',
                    'uploaded_at' => date('d.m.Y H:i', $a->uploaded_at),
                    'link_share' => config('app.url').'/file/'.$a->id
                ];
            }

            return datatables($all)
                ->addColumn('block_link_share', function ($row) {return '<a data-id="'.$row['id'].'" data-link="'.$row['link_share'].'" id="copy_link_share" onclick="copy(\''.$row['link_share'].'\', 0);" title="Скопировать ссылку" href="javascript:;"><i class="far fa-copy fa-xl"></i></a>';})
                ->addColumn('block_delete', function ($row) {return '<a onclick="remove(\'files\',\''.$row['id'].'\');return false;" title="Удалить" class="text-danger" href="javascript:;"> <i class="far fa-trash fa-xl"></i></a><input type="hidden" name="sort[]" value="'.$row['id'].'">';})
                ->rawColumns(['icon', 'block_link_share', 'block_delete'])
                ->make(true);

        } catch (Exception $e){
            return response([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'file' => 'required|mimes:gif,jpeg,jpg,png|max:5048',
        ]);

        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }


        if ($file = $request->file('file')) {
            $file->store('public/covers/');

            $filename = explode('.', $file->hashName())[0];

            $date_now = Carbon::now()->timestamp;

            $sql = [
                'id' => $filename,
                'title' => '',
                'uid' => 0,
                'ext' => $file->extension(),
                'size' => $file->getSize(),
                'type' => 0,
                'uploaded_at' => $date_now,
            ];

            DB::table('attachments')->insert($sql);

            return response()->json([
                "ok" => true,
                "description" => "Изображение загружено",
                "result" => ['id' => 'i'.$filename]
            ]);

        }

    }

    public function uploadTxt(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'file' => 'required|mimes:txt|max:2048',
        ]);

        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }


        if ($file = $request->file('file')) {

            $dir = str_replace('app/Http/Controllers', '', __dir__);
            $file->store('public/files/');

            $filename = explode('.', $file->hashName())[0];

            $date_now = Carbon::now()->timestamp;

            $sql = [
                'id' => $filename,
                'title' => '',
                'uid' => 0,
                'ext' => $file->extension(),
                'size' => $file->getSize(),
                'type' => 0,
                'uploaded_at' => $date_now,
            ];

            $count = count(file($dir.'storage/app/public/files/'.$filename.'.'.$file->extension()));

            DB::table('attachments')->insert($sql);

            return response()->json([
                "ok" => true,
                "description" => "File successfully uploaded",
                "result" => ['count' => $count, 'id' => 't'.$filename]
            ]);

        }


    }

    public function uploadJson(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'file' => 'required|mimes:json|max:500000',
        ]);

        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }


        if ($file = $request->file('file')) {

            $dir = str_replace('app/Http/Controllers', '', __dir__);
            $file->store('public/files/');

            $filename = explode('.', $file->hashName())[0];

            $date_now = Carbon::now()->timestamp;

            $sql = [
                'id' => $filename,
                'title' => '',
                'uid' => 0,
                'ext' => $file->extension(),
                'size' => $file->getSize(),
                'type' => 0,
                'uploaded_at' => $date_now,
            ];

            DB::table('attachments')->insert($sql);

            return response()->json([
                "ok" => true,
                "description" => "File successfully uploaded",
                "result" => ['id' => $filename]
            ]);

        }


    }

    public function uploadFiles(Request $request)
    {

        $access = RolePermission::getByPermission($this->user->role_id, 'files.upload')->allow;
        if(!$access){throw new Exception('Access Denied');}

        $validator = Validator::make($request->all(),[
            'file' => 'required|mimes:mp3,wav,mp4,flv,zip,rar,pdf,doc,docx,txt,jpg,jpeg,png,gif,webp|max:500000', // Be sure to include mimes if you want to restrict file types
        ]);

        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);
        }

        if ($file = $request->file('file')) {


            // var_dump($file);
            // Generate a random filename
            $filename = Str::random(10);

            // Define the original file name
            $originalName = $file->getClientOriginalName();

            // Get the file extension
            $extension = $file->getClientOriginalExtension();

            // Concatenate the random filename and the extension
            $newName = $filename . '.' . $extension;

            // Save the file with the new name
            $file->storeAs('public/files/', $newName);

            // Current timestamp
            $date_now = strtotime('NOW');

            // Create the array to insert into the database
            $sql = [
                'id' => $filename,
                'title' => $originalName, // Or you could use $newName if you want to store the random name
                'uid' => 0,
                'ext' => $extension,
                'size' => $file->getSize(),
                'type' => 1,
                'uploaded_at' => $date_now,
            ];

            // Insert the file information into the database
            DB::table('attachments')->insert($sql);

            // Return a JSON response
            return response()->json([
                "ok" => true,
                "result" => [
                    'id' => $filename,
                    'extension' => $extension
                ]
            ]);
        }
    }

    public function delete($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'files.delete')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $attach = Attach::where('id', $id)->first();
            if (!$attach) {
                throw new Exception('Файл не найден.', 1);
            }

            $delete = Attach::where('id', $id)->delete();
            Storage::disk('public')->delete('files/' . $attach->id.'.'.$attach->ext);

            if($delete) {
                return response([
                    'ok' => true,
                    'description' => 'File deleted'
                ]);
            }

        } catch (Exception $e){
            return response([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

}
