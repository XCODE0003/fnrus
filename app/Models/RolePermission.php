<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{

    public $timestamps = false;
    protected $table = 'role_permissions';
    protected $fillable = [
        'id',
        'role_id',
        'title',
        'permission',
        'allow',
    ];

    public static function getByID($id){
        return RolePermission::where('id', $id)->first();
    }

    public static function deleteByRoleID($role_id){
        return RolePermission::where('role_id', $role_id)->delete();
    }

    public static function getList($permission, $role_id){
        return RolePermission::where('permission', 'like', '%' . $permission . '%')->where('role_id', $role_id)->get();
    }

    public static function getByPermission($role_id, $permission)
    {
        return RolePermission::where('role_id', $role_id)
            ->where('permission', 'like', '%' . $permission . '%')
            ->first();
    }

    public static function getByPermissionActive($role_id)
    {
        return RolePermission::where('role_id', $role_id)
            ->where('allow', 1)
            ->first();
    }


    public static function getByPermissionList($role_id, $permission){
        return RolePermission::where('permission', 'like', $permission . '%')->where('role_id', $role_id)->get();
    }

}
