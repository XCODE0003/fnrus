<?php

namespace App\Http\Controllers;

use App\Models\Attach;
use Exception;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class StorageController extends Controller
{
    public function image($hash)
    {
        try {
            $image = DB::table('attachments')->where('id', $hash)->first();
            if (!$image) {abort(404);}

            $exists = Storage::disk('public')->exists('covers/' . $image->id . '.' . $image->ext);

            if ($exists) {

                $content = Storage::get('public/covers/' . $image->id . '.' . $image->ext);
                $mime = Storage::mimeType('public/covers/' . $image->id . '.' . $image->ext);

                $response = Response::make($content, 200);
                $response->header("Content-Type", $mime);

                return $response;

            } else {
                abort(404);
            }
        } catch (Exception $e){
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }

    public function file($hash)
    {
        try {
            $file = Attach::where('id', $hash)->where('type', 1)->first();
            if (!$file) {
                abort(404);
            }

            $filePath = 'files/' . $file->id . '.' . $file->ext; // The 'public/' prefix is not needed here
            if (Storage::disk('public')->exists($filePath)) {
                $mime = Storage::disk('public')->mimeType($filePath);

                $inlineExtensions = ['mp3', 'wav', 'mp4', 'flv'];
                $contentDisposition = in_array($file->ext, $inlineExtensions) ? 'inline' : 'attachment';

                $headers = [
                    'Content-Type' => $mime,
                    'Content-Disposition' => $contentDisposition . '; filename="' . $file->title . '"'
                ];

                return response()->stream(function () use ($filePath) {
                    $stream = Storage::disk('public')->readStream($filePath);
                    fpassthru($stream);
                }, 200, $headers);
            } else {
                abort(404);
            }
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'error_code' => $e->getCode(), 'description' => $e->getMessage()], 200);
        }
    }



}
