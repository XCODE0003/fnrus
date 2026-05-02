<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Role;
use App\Models\RolePermission;
use Exception;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class RoleController extends Controller
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
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function info($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $role = Role::getByID($id);
            if (!$role) {throw new Exception('Роль не найдена.', 1);}

            $result = [
                'title' => $role->title,
            ];

            return response()->json(['ok' => true, 'result' => $result]);

        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function select_all()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $roles = Role::orderBy('sort')->get();

            return response()->json(['ok' => true, 'result' => $roles]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function all()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $roles = Role::orderBy('id')->get();

            $all = [];

            foreach ($roles as $r) {

                $block_delete = '<a href="#" onclick="deleteRole(\''.$r->id.'\');return false;" title="Удалить" class="text-danger"><i class="far fa-trash fa-xl"></i></a>';

                if ($r->id === 1){
                    $block_delete = '<a href="javascript:;" title="Удалить" class="text-secondary"><i class="far fa-trash fa-xl"></i></a>';
                }


                $all[] = [
                    'id' => $r->id,
                    'icon' => '<i class="far fa-user-tag mr-1"></i>',
                    'title' => $r->title,
                    'block_edit' => '<a data-id="'.$r->id.'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#editRole"><i class="far fa-edit fa-xl"></i></a>',
                    'block_delete' => $block_delete,
                ];
            }

            return datatables($all)
                ->rawColumns(['icon', 'block_edit', 'block_delete'])
                ->make(true);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function update($id, Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:2|max:100',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $role = Role::find($id);

            if (!$role) {
                throw new Exception('Роль не найдена.', 1);
            }

            $role->title = $request->title;
            $role->save();

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }


    public function create(Request $request)
    {
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|min:4|max:255',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $end_sort = Role::getEndSort();

            $new_role_id = Role::insertGetId(['title' => $request->title, 'sort' => $end_sort]);
            $dataToCopy = RolePermission::where('role_id', 1)->get();

            foreach ($dataToCopy as $data) {
                RolePermission::insert([
                    'role_id' => $new_role_id,
                    'title' => $data->title,
                    'permission' => $data->permission,
                    'allow' => 1
                ]);
            }

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
                'result' => ['role_id' => $new_role_id],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function delete($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $role = Role::getByID($id);
            if (!$role) {throw new Exception('Роль не найдена.');}

            Member::where('role_id', $id)->update(['role_id' => 0]);
            RolePermission::deleteByRoleID($id);

            if($role->delete()) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Удалено'
                ]);
            }

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }


}
