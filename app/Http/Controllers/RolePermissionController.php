<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\RolePermission;
use Exception;
use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RolePermissionController extends Controller
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

    public function list_by_id($id){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            $menu = [
                [
                    'title' => 'Аналитика',
                    'alias' => 'analytics'
                ],
                [
                    'title' => 'Заказы',
                    'alias' => 'orders'
                ],
                [
                    'title' => 'Тикеты',
                    'alias' => 'tickets'
                ],
                [
                    'title' => 'Выводы',
                    'alias' => 'withdrawals'
                ],
                [
                    'title' => 'Статусы',
                    'alias' => 'statuses'
                ],
                [
                    'title' => 'Товары',
                    'alias' => 'products'
                ],
                [
                    'title' => 'Категории',
                    'alias' => 'categories'
                ],
                [
                    'title' => 'Материалы',
                    'alias' => 'materials'
                ],
                [
                    'title' => 'Промокоды',
                    'alias' => 'coupons'
                ],
                [
                    'title' => 'История экспорта',
                    'alias' => 'history_exports'
                ],
                [
                    'title' => 'Пользователи',
                    'alias' => 'members'
                ],
                [
                    'title' => 'Рассылки',
                    'alias' => 'senders'
                ],
                [
                    'title' => 'Инструкции',
                    'alias' => 'instructions'
                ],
                [
                    'title' => 'Вопрос-ответ',
                    'alias' => 'faq'
                ],
                [
                    'title' => 'Рекламные ссылки',
                    'alias' => 'ads_links'
                ],
                [
                    'title' => 'Файлы',
                    'alias' => 'files'
                ],
                [
                    'title' => 'Настройки',
                    'alias' => 'settings'
                ]
            ];

            $menu_all = [];

            foreach ($menu as $m){

                $permissions = RolePermission::getList($m['alias'], $id);

                $permissions_all = [];

                foreach ($permissions as $r) {
                    $permissions_all[] = [
                        'title' => $r->title,
                        'permission' => $r->permission,
                        'allow' => $r->allow,
                    ];
                }

                $menu_all[] = ['title' => $m['title'], 'alias' => $m['alias'], 'permissions' => $permissions_all];
            }

            return response()->json(['ok' => true, 'result' => $menu_all]);

        } catch (Exception $e){
            return response([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function like_update($role_id, $permission){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $role_permission_list = RolePermission::getByPermissionList($role_id, $permission);

            if (!$role_permission_list) {
                throw new Exception('Резрешений не найдено.', 1);
            }

            foreach ($role_permission_list as $r){
                if($r->allow == 0){$allow = 1;}
                if($r->allow == 1){$allow = 0;}

                $r->allow = $allow;
                $r->save();
            }

            return response([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            return response(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function check_role($alias){
        $check = RolePermission::getByPermission($this->user->role_id, $alias);

        if($check && $check->allow == 1){
            return response()->json(['ok' => true]);
        } else {
            $check = RolePermission::getByPermissionActive($this->user->role_id);
            if(!$check) {
                return response()->json(['ok' => false, 'description' => 'Permission not found']);
            }
            if($check->permission == 'orders.all'){
                return response()->json(['ok' => true, 'redirect' => '/admin/orders']);
            }
            if($check->permission == 'tickets.all'){
                return response()->json(['ok' => true, 'redirect' => '/admin/tickets']);
            }
            if($check->permission == 'withdrawals.all'){
                return response()->json(['ok' => true, 'redirect' => '/admin/withdrawals']);
            }
            if($check->permission == 'statuses.all'){
                return response()->json(['ok' => true, 'redirect' => '/admin/statuses']);
            }
            if($check->permission == 'products.all'){
                return response()->json(['ok' => true, 'redirect' => '/admin/products']);
            }
        }
    }


    public function update($role_id, $permission){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'settings.roles')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $role_permission = RolePermission::where('role_id', $role_id)->where('permission', $permission)->first();

            if (!$role_permission) {
                throw new Exception('Резрешение не найдено.', 1);
            }

            if($role_permission->allow == 0){$allow = 1;}
            if($role_permission->allow == 1){$allow = 0;}

            $role_permission->allow = $allow;
            $role_permission->save();

            return response([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            return response(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

}
