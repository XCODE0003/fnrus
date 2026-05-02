<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\AboutItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AboutItemController extends Controller
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
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function all(){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'about')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $items = AboutItem::getAllSorted();
            return response()->json(['ok' => true, 'result' => $items]);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function create(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'about')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $count = AboutItem::count();
            if($count >= 5){
                throw new Exception('Максимум 5 элементов');
            }

            $validator = Validator::make($request->all(), [
                'icon' => 'required|string|max:20',
                'label_ru' => 'required|string|max:255',
                'label_en' => 'nullable|string|max:255',
                'url' => 'required|string|max:500',
                'url_text' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            $item = new AboutItem();
            $item->icon = $request->icon;
            $item->label_ru = $request->label_ru;
            $item->label_en = $request->label_en ?? '';
            $item->url = $request->url;
            $item->url_text = $request->url_text;
            $item->sort_order = $count;
            $item->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'about')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'icon' => 'required|string|max:20',
                'label_ru' => 'required|string|max:255',
                'label_en' => 'nullable|string|max:255',
                'url' => 'required|string|max:500',
                'url_text' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            $item = AboutItem::find($id);
            if(!$item){throw new Exception('Элемент не найден');}

            $item->icon = $request->icon;
            $item->label_ru = $request->label_ru;
            $item->label_en = $request->label_en ?? '';
            $item->url = $request->url;
            $item->url_text = $request->url_text;
            $item->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function delete($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'about')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $item = AboutItem::find($id);
            if(!$item){throw new Exception('Элемент не найден');}
            $item->delete();

            return response()->json(['ok' => true, 'description' => 'Удалено']);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'about')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $item = AboutItem::find($id);
            if(!$item){throw new Exception('Элемент не найден');}

            return response()->json(['ok' => true, 'result' => $item]);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
}
