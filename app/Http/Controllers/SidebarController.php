<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Menu;
// use App\Models\Ticket; // disabled - ticket system removed
use App\Models\Withdrawal;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class SidebarController extends Controller
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

    public function index(){
        try {

            $role_id = $this->user->role_id;

            $result = Menu::getListBySort('');
            $menu = [];

            // $new_tickets = Ticket::where('status', 0)->count(); // disabled - ticket system removed
            $new_withdrawals = Withdrawal::where('status', 1)->count();

            foreach ($result as $item) {

                if ($item->alias == 'products_item' || $item->alias == 'settings_item') {

                    $result_sub = Menu::getListBySort($item->alias);

                    $menu_sub = [];

                    foreach ($result_sub as $i) {
                        $role = RolePermission::getByPermission($role_id, $i->alias);

                        if ($role && $role->allow == 1) {
                            if (strpos($i->link, '#') !== false && $i->icon == '') {
                                $menu_sub[] = '<a class="collapse-item" data-toggle="modal" data-target="' . $i->link . '" data-id="' . $i->alias . '" href="#">' . $i->title . '</a>';
                            } else {
                                $menu_sub[] = '<a class="collapse-item" data-id="' . $i->alias . '" href="' . $i->link . '">' . $i->title . '</a>';
                            }
                        }
                    }

                    $menu_sub_html = '';

                    $badge = '';

                    // Tickets badge disabled - ticket system removed
                    // if($item->alias == 'tickets' && $new_tickets > 0){
                    //     $badge = '<div class="badge badge-warning ml-2" id="badge-tickets" style="color: #000">'.$new_tickets.'</div>';
                    // }

                    if($item->alias == 'withdrawals' && $new_withdrawals > 0){
                        $badge = '<div class="badge badge-warning ml-2" id="badge-withdrawals" style="color: #000">'.$new_withdrawals.'</div>';
                    }

                    if (count($menu_sub) > 0) {
                        $menu_sub_html = '<div id="collapse_' . $item->alias . '" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar"><div class="py-3 mt-3 collapse-inner shadow">' . implode('', $menu_sub) . '</div></div>';
                        $menu[] = '<li class="nav-item" data-id="' . $item->alias . '"><a class="nav-link collapsed" href="' . $item->link . '" data-toggle="collapse" data-target="#collapse_' . $item->alias . '" aria-expanded="true" aria-controls="collapse_' . $item->alias . '"><i class="fal fa-fw fa-' . $item->icon . '"></i><span>' . $item->title . '</span>'.$badge.'</a>' . $menu_sub_html . '</li>';
                    }

                } else {
                    $role = RolePermission::getByPermission($role_id, $item->alias);
                    if ($role && $role->allow == 1) {

                        $result_sub = Menu::getListBySort($item->alias);

                        $menu_sub = [];

                        foreach ($result_sub as $i) {
                            $role = RolePermission::getByPermission($role_id, $i->alias);

                            if ($role && $role->allow == 1) {
                                if (strpos($i->link, '#') !== false && $i->icon == '') {
                                    $menu_sub[] = '<a class="collapse-item" data-toggle="modal" data-target="' . $i->link . '" data-id="' . $i->alias . '" href="#">' . $i->title . '</a>';
                                } else {
                                    $menu_sub[] = '<a class="collapse-item" data-id="' . $i->alias . '" href="' . $i->link . '">' . $i->title . '</a>';
                                }
                            }
                        }

                        $menu_sub_html = '';

                        $badge = '';

                        // Tickets badge disabled - ticket system removed
                        // if($item->alias == 'tickets' && $new_tickets > 0){
                        //     $badge = '<div class="badge badge-warning ml-2" id="badge-tickets" style="color: #000">'.$new_tickets.'</div>';
                        // }

                        if($item->alias == 'withdrawals' && $new_withdrawals > 0){
                            $badge = '<div class="badge badge-warning ml-2" id="badge-withdrawals" style="color: #000">'.$new_withdrawals.'</div>';
                        }


                        if (count($menu_sub) > 0) {
                            $menu_sub_html = '<div id="collapse_' . $item->alias . '" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar"><div class="py-3 mt-3 collapse-inner shadow">' . implode('', $menu_sub) . '</div></div>';
                            $menu[] = '<li class="nav-item" data-id="' . $item->alias . '"><a class="nav-link collapsed" href="' . $item->link . '" data-toggle="collapse" data-target="#collapse_' . $item->alias . '" aria-expanded="true" aria-controls="collapse_' . $item->alias . '"><i class="fal fa-fw fa-' . $item->icon . '"></i><span>' . $item->title . '</span></a>' . $menu_sub_html . '</li>';
                        } else {
                            $menu[] = '<li class="nav-item" data-id="' . $item->alias . '"><a class="nav-link" href="' . $item->link . '"><i class="fal fa-fw fa-' . $item->icon . '"></i><span>' . $item->title . '</span>' . $badge . '</a>' . $menu_sub_html . '</li>';
                        }
                    }
                }
            }

            return response()->json(['ok' => true, 'result' => $menu]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

}
