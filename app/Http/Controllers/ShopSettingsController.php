<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Exception;
use App\Models\ShopSettings;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopSettingsController extends Controller
{

    public function __construct(Request $request)
    {
        try {
            $this->middleware(function ($request, $next) {
                $this->user = Auth::user();
                return $next($request);
            });
            $this->set_shop = ShopSettings::getDefault();
        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }
    public function info_notify() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.notify')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'notify_target_id' => $this->set_shop->notify_target_id,
                    'tg_notify_buys' => $this->set_shop->tg_notify_buys,
                    'tg_notify_balance' => $this->set_shop->tg_notify_balance,
                    'tg_notify_users' => $this->set_shop->tg_notify_users
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function info_display() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.display')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'display_products' => $this->set_shop->display_products,
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }
    public function info_refferal() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.ref_program')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'ref_percent' => $this->set_shop->ref_percent,
                    'min_sum_withdrawal_card' => $this->set_shop->min_sum_withdrawal_card,
                    'min_sum_withdrawal_balance' => $this->set_shop->min_sum_withdrawal_balance,
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }
    public function info_topup() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.topup_balance')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop = Shop::getDefault();
            if(!$shop){throw new Exception('Shop not found!');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'min_sum_topup' => $this->set_shop->min_sum_topup,
                ]
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }
    public function update_referral(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.ref_program')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'ref_percent' => 'required|int|between:1,100',
                'min_sum_withdrawal_card' => 'required|numeric|between:0,10000',
                'min_sum_withdrawal_balance' => 'required|numeric|between:0,10000',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) {
                throw new Exception('Настройки не найдены.', 1);
            }

            $shop_set->ref_percent = $request->ref_percent;
            $shop_set->min_sum_withdrawal_card = $request->min_sum_withdrawal_card;
            $shop_set->min_sum_withdrawal_balance = $request->min_sum_withdrawal_balance;
            $shop_set->save();

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update_display(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.display')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'display_products' => 'required|int|between:0,1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) {
                throw new Exception('Настройки не найдены.', 1);
            }

            $shop_set->display_products = $request->display_products;
            $shop_set->save();

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update_topup(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.topup_balance')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'min_sum_topup' => 'required|numeric|between:0,10000',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) {
                throw new Exception('Настройки не найдены.', 1);
            }

            $shop_set->min_sum_topup = $request->min_sum_topup;
            $shop_set->save();

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }
    // 2.1: Site buttons info
    public function info_buttons() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'site-buttons')->allow;
            if(!$access){throw new Exception('Access Denied');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'btn_tg_bot_url' => $this->set_shop->btn_tg_bot_url,
                    'btn_tg_bot_text' => $this->set_shop->btn_tg_bot_text,
                    'btn_tg_bot_icon' => $this->set_shop->btn_tg_bot_icon ?? 'telegram',
                    'btn_reviews_url' => $this->set_shop->btn_reviews_url,
                    'btn_reviews_text' => $this->set_shop->btn_reviews_text,
                    'btn_reviews_icon' => $this->set_shop->btn_reviews_icon ?? 'telegram',
                    'btn_buy_bot_url' => $this->set_shop->btn_buy_bot_url,
                    'btn_buy_bot_text' => $this->set_shop->btn_buy_bot_text,
                    'btn_buy_bot_icon' => $this->set_shop->btn_buy_bot_icon ?? 'telegram',
                ]
            ]);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    // 2.1: Save site buttons
    public function update_buttons(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'site-buttons')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'btn_tg_bot_url' => 'required|string|max:255',
                'btn_tg_bot_text' => 'required|string|max:255',
                'btn_tg_bot_icon' => 'required|string|max:50',
                'btn_reviews_url' => 'required|string|max:255',
                'btn_reviews_text' => 'required|string|max:255',
                'btn_reviews_icon' => 'required|string|max:50',
                'btn_buy_bot_url' => 'required|string|max:255',
                'btn_buy_bot_text' => 'required|string|max:255',
                'btn_buy_bot_icon' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) { throw new Exception('Настройки не найдены.'); }

            $shop_set->btn_tg_bot_url = $request->btn_tg_bot_url;
            $shop_set->btn_tg_bot_text = $request->btn_tg_bot_text;
            $shop_set->btn_tg_bot_icon = $request->btn_tg_bot_icon;
            $shop_set->btn_reviews_url = $request->btn_reviews_url;
            $shop_set->btn_reviews_text = $request->btn_reviews_text;
            $shop_set->btn_reviews_icon = $request->btn_reviews_icon;
            $shop_set->btn_buy_bot_url = $request->btn_buy_bot_url;
            $shop_set->btn_buy_bot_text = $request->btn_buy_bot_text;
            $shop_set->btn_buy_bot_icon = $request->btn_buy_bot_icon;
            $shop_set->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    // Build policy fallback HTML from translation files
    private function buildPolicyFallback($locale) {
        $sections = [
            ['title' => 'policy_1_title', 'items' => ['policy_1_1']],
            ['title' => 'policy_2_title', 'items' => ['policy_2_1','policy_2_2','policy_2_3','policy_2_4','policy_2_5']],
            ['title' => 'policy_3_title', 'items' => ['policy_3_1','policy_3_2','policy_3_3','policy_3_4','policy_3_5','policy_3_6','policy_3_7']],
            ['title' => 'policy_4_title', 'items' => ['policy_4_1','policy_4_2','policy_4_3','policy_4_4','policy_4_5','policy_4_6']],
            ['title' => 'policy_5_title', 'items' => ['policy_5_1']],
            ['title' => 'policy_6_title', 'items' => ['policy_6_1']],
            ['title' => 'policy_7_title', 'items' => ['policy_7_1']],
        ];

        $html = '';
        foreach ($sections as $section) {
            $html .= '<p class="policy__part-caption">' . __('site.' . $section['title'], [], $locale) . '</p>';
            foreach ($section['items'] as $key) {
                $text = __('site.' . $key, [], $locale);
                if ($key === 'policy_6_1') {
                    $text .= ' <a target="_blank" href="https://t.me/Fnrus_Keys">@Fnrus_Keys</a>';
                }
                if ($key === 'policy_7_1') {
                    $text .= ' <a href="mailto:Fnrus@proton.me">Fnrus@proton.me</a>';
                }
                $html .= '<p class="policy__text">' . $text . '</p>';
            }
        }
        return $html;
    }

    // 2.2: Policy content info
    public function info_policy() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'policy')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $ru = $this->set_shop->policy_content_ru;
            $en = $this->set_shop->policy_content_en;

            return response()->json([
                'ok' => true,
                'result' => [
                    'policy_content_ru' => !empty($ru) ? $ru : $this->buildPolicyFallback('ru'),
                    'policy_content_en' => !empty($en) ? $en : $this->buildPolicyFallback('en'),
                ]
            ]);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    // 2.2: Save policy content
    public function update_policy(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'policy')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) { throw new Exception('Настройки не найдены.'); }

            $shop_set->policy_content_ru = $request->policy_content_ru;
            $shop_set->policy_content_en = $request->policy_content_en;
            $shop_set->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    // 2.5: Delivery text info
    public function info_delivery_text() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'delivery-text')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $ru = $this->set_shop->delivery_text_ru;
            $en = $this->set_shop->delivery_text_en;

            return response()->json([
                'ok' => true,
                'result' => [
                    'delivery_text_ru' => !empty($ru) ? $ru : '<p>' . __('site.section_delivery_attention', [], 'ru') . '</p>',
                    'delivery_text_en' => !empty($en) ? $en : '<p>' . __('site.section_delivery_attention', [], 'en') . '</p>',
                ]
            ]);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    // 2.5: Save delivery text
    public function update_delivery_text(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'delivery-text')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) { throw new Exception('Настройки не найдены.'); }

            $shop_set->delivery_text_ru = $request->delivery_text_ru;
            $shop_set->delivery_text_en = $request->delivery_text_en;
            $shop_set->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    // 3.1-3.2: Support modal info
    public function info_support() {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'support-settings')->allow;
            if(!$access){throw new Exception('Access Denied');}

            return response()->json([
                'ok' => true,
                'result' => [
                    'support_text' => $this->set_shop->support_text ?? '',
                    'support_btn1_text' => $this->set_shop->support_btn1_text ?? '',
                    'support_btn1_url' => $this->set_shop->support_btn1_url ?? '',
                    'support_btn2_text' => $this->set_shop->support_btn2_text ?? '',
                    'support_btn2_url' => $this->set_shop->support_btn2_url ?? '',
                    'support_btn3_text' => $this->set_shop->support_btn3_text ?? '',
                    'support_btn3_url' => $this->set_shop->support_btn3_url ?? '',
                ]
            ]);
        } catch (Exception $e){
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    // 3.1-3.2: Save support modal settings
    public function update_support(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'support-settings')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'support_text' => 'nullable|string|max:1000',
                'support_btn1_text' => 'nullable|string|max:255',
                'support_btn1_url' => 'nullable|string|max:255',
                'support_btn2_text' => 'nullable|string|max:255',
                'support_btn2_url' => 'nullable|string|max:255',
                'support_btn3_text' => 'nullable|string|max:255',
                'support_btn3_url' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message);
                }
            }

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) { throw new Exception('Настройки не найдены.'); }

            $shop_set->support_text = $request->support_text;
            $shop_set->support_btn1_text = $request->support_btn1_text;
            $shop_set->support_btn1_url = $request->support_btn1_url;
            $shop_set->support_btn2_text = $request->support_btn2_text;
            $shop_set->support_btn2_url = $request->support_btn2_url;
            $shop_set->support_btn3_text = $request->support_btn3_text;
            $shop_set->support_btn3_url = $request->support_btn3_url;
            $shop_set->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update_notify(Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'settings.notify')->allow;
            if(!$access){throw new Exception('Access Denied');}

            $validator = Validator::make($request->all(), [
                'notify_target_id' => 'required|string',
                'tg_notify_buys' => 'required|int|between:0,1',
                'tg_notify_balance' => 'required|int|between:0,1',
                'tg_notify_users' => 'required|int|between:0,1',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) {
                throw new Exception('Настройки не найдены.', 1);
            }

            $shop_set->notify_target_id = $request->notify_target_id;
            $shop_set->tg_notify_buys = $request->tg_notify_buys;
            $shop_set->tg_notify_balance = $request->tg_notify_balance;
            $shop_set->tg_notify_users = $request->tg_notify_users;
            $shop_set->save();

            return response()->json([
                'ok' => true,
                'description' => 'Сохранено',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    /**
     * Global status-broadcast template (used as fallback when a status row
     * has no per-row template). Returns the current value plus an URL for
     * the attached image, if any.
     */
    public function info_status_broadcast()
    {
        try {
            $row = RolePermission::getByPermission($this->user->role_id, 'settings.notify');
            if (!$row || !$row->allow) { throw new Exception('Access Denied'); }

            $imagePath = $this->set_shop->status_broadcast_image_path;

            return response()->json([
                'ok' => true,
                'result' => [
                    'template'   => $this->set_shop->status_broadcast_template,
                    'image_path' => $imagePath,
                    'image_url'  => $imagePath ? asset('storage/' . ltrim($imagePath, '/')) : null,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function update_status_broadcast(\Illuminate\Http\Request $request)
    {
        try {
            $row = RolePermission::getByPermission($this->user->role_id, 'settings.notify');
            if (!$row || !$row->allow) { throw new Exception('Access Denied'); }

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'template'   => 'nullable|string|max:8000',
                'image_path' => 'nullable|string|max:500',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $shop_set = ShopSettings::getDefault();
            if (!$shop_set) { throw new Exception('Настройки не найдены.'); }

            $shop_set->status_broadcast_template = $request->input('template');
            $shop_set->status_broadcast_image_path = $request->input('image_path');
            $shop_set->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function upload_status_broadcast_image(\Illuminate\Http\Request $request)
    {
        try {
            $row = RolePermission::getByPermission($this->user->role_id, 'settings.notify');
            if (!$row || !$row->allow) { throw new Exception('Access Denied'); }

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $path = $request->file('image')->store('status-posts', 'public');

            return response()->json([
                'ok'     => true,
                'result' => ['path' => $path, 'url' => asset('storage/' . $path)],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
}
