<?php

namespace App\Http\Controllers;

use App\Models\ButtonSettings;
use App\Models\ChannelSub;
use App\Models\ChannelSubSettings;
use App\Models\CustomValid;
use App\Models\HackStatus;
use App\Models\StatusCheat;
use App\Models\Instruction;
use App\Models\Refil;
use App\Models\RolePermission;
use App\Models\ShopSettings;
use App\Models\Ticket;
use App\Models\Tariff;
use App\Models\Currency;
use App\Models\TicketMessage;
use App\Models\TicketSubject;
use Exception;
use App\Models\Attach;
use App\Models\Shop;
use App\Models\LinkAd;
use App\Models\LinkAdUser;
use App\Models\Member;
use App\Models\Button;
use App\Models\Category;
use App\Models\Product;
use App\Models\Material;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Text;
use App\Models\CouponUse;
use App\Models\ProductPurchase;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class BotController extends Controller
{
    public function keyb($type, $shop_id, $count){

        if($type == 'home'){$ki = Button::getMainByShopID($shop_id);}

        $kp = json_encode([
            "keyboard" => array_chunk($ki, $count),
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        return $kp;
    }


    public function main(Request $request, $sid)
    {
        try {

            $shop = Shop::getDefault();
            $shop_token = Crypt::decryptString($shop->token);
            $ch_sub_set = ChannelSubSettings::getByShopID($sid);
            $ch_sub_count = ChannelSub::getCountByShopID($sid);

            $set_shop = ShopSettings::getDefault();

            $shop_username = $shop->username;

            $count_products = $set_shop->count_products;
            $count_categories = $set_shop->count_categories;
            $count_buttons = ButtonSettings::getByShopID($sid)->count_columns;
            $count_buttons_profile = $set_shop->count_buttons_profile;

            $notify_target_id = $set_shop->notify_target_id;

            $orders_limit = $set_shop->orders_limit;
            $processing_time = $set_shop->booking_time;

            $ticket_auto_close_date = '+24 hours';

            $main_currency = $set_shop->currency;
            $shop_currency = ['RUB' => '₽', 'USD' => '$'];
            $main_currency_symbol = $shop_currency[$main_currency];

            $topup_sums = [1, 2.5, 5, 10];

            $tg = new Api($shop_token);
            $update = new Update(json_decode(file_get_contents('php://input'), true));

            if ($update->getCallbackQuery()) {
                $callbackQuery = $update->getCallbackQuery();

                $callback_id = $callbackQuery->getId();
                $cd = $callbackQuery->getData();

                $message = $callbackQuery->getMessage();
                // Callbacks from inline messages have no attached message/chat —
                // ignore them instead of crashing on getMessageId()/getChat().
                if (!$message || !$message->getChat()) {
                    return response()->json(['ok' => true]);
                }
                $msg_id = $message->getMessageId();
                $cid = $message->getChat()->getId();

                $first_name = $message->getChat()->getFirstName();

            } else {

                if($update->getMessage()) {
                    $message = $update->getMessage();
                    // Some updates (my_chat_member, channel posts, service
                    // messages) carry a message with no chat — bail cleanly so
                    // the webhook returns 200 instead of a 500 (getId() on null).
                    if (!$message->getChat()) {
                        return response()->json(['ok' => true]);
                    }
                    $cid = $message->getChat()->getId();
                    $first_name = $message->getChat()->getFirstName();
                    $username = $message->getChat()->getUsername();

                    if ($message->has('photo')) {
                        $photo = $message->get('photo');
                    }

                    if ($message->has('text')) {
                        $msg = $message->getText();
                    }

                    if ($message->has('document')) {
                        $document = $message->getDocument();
                    }

                    $key = 'rate_limit_' . $cid;
                    $maxAttempts = 2;
                    $decaySeconds = 1;

                    if (Cache::has($key) && Cache::get($key) >= $maxAttempts) {
                        $tg->sendMessage(['chat_id' => $cid, 'text' => 'Превышен лимит запросов.']);
                        exit;
                    }

                    if (Cache::has($key)) {
                        Cache::increment($key);
                    } else {
                        Cache::put($key, 1, $decaySeconds);
                    }

                }
            }

            // Диплинк-ВХОД на сайт: /start login_<token>
            if(!empty($msg) && strpos($msg, 'login_') !== false){
                $login_parts = explode('login_', $msg);
                $login_token = trim($login_parts[1] ?? '');
                if($login_token !== ''){
                    $user = \App\Models\User::where('tid', $cid)->first();
                    if(!$user){
                        Member::add($cid, $sid, 0, Str::random(8));
                        $user = \App\Models\User::where('tid', $cid)->first();
                    }
                    if($user && (int)($user->is_ban ?? 0) !== 1){
                        Cache::put('tglogin:' . $login_token, $user->id, 300);
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ <b>Вход подтверждён!</b>\r\nВернитесь на вкладку сайта — вы уже авторизованы.", "parse_mode" => "HTML"]);
                    } else {
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Не удалось войти. Попробуйте позже.", "parse_mode" => "HTML"]);
                    }
                    exit;
                }
            }

            if(!empty($msg) && strpos($msg, 'connect_') !== false){

                $param_code = explode('_', $msg);

                $member = Member::getByToken($param_code[1]);
                if ($member) {
                    $member_telegram = Member::getCheckConnectTid($cid);

                    if ($member_telegram){
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Данный Telegram-аккаунт уже подключен к другому аккаунту", "parse_mode" => "HTML"]);
                        exit;
                    }

                    Member::editByID($member->id, $sid, 'tid', $cid);
                    Member::changeTokenByID($member->id, $sid);
                    $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ Аккаунт привязан к пользователю: <b>" . $member->username . "</b>", "parse_mode" => "HTML"]);
                    exit;
                } else {
                    $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Переданы некорректные параметры", "parse_mode" => "HTML"]);
                    exit;
                }
            }


            if($cid != $notify_target_id) {

                $m = Member::getByTID($cid, $sid);

                if (!$m) {

                    $rid = 0;
                    $parts = explode(' ', $msg);
                    if (isset($parts[1])) {
                        $valid = CustomValid::ref_code($parts[1]);
                        if ($valid) {
                            $rid = Member::getByRefCode($parts[1])->id;
                        }
                    }

                    $user_password = Str::random(8);
                    Member::add($cid, $sid, $rid, $user_password);
                    $joined_at = strtotime('NOW');
                    $member_currency = 'RUB';
                    $m = Member::getByTID($cid, $sid);
                    if($set_shop->tg_notify_users == 1) {
                        $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "👤 Новый пользователь\r\n├ Имя: <b>" . $first_name . "</b>\r\n├ ID: <b>" . $cid . "</b>\r\n└ Юзернейм: <b>" . $username . "</b>", "parse_mode" => "HTML"]);
                    }
                } else {

                    if($m->is_ban == 1){
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Вы заблокированы", "parse_mode" => "HTML"]);
                        exit;
                    }

                    $joined_at = $m->created_at;
                    $user_password = '';

                    $member_currency = $m->currency;

                    $balance_main = Currency::convert($main_currency, $member_currency, $m->balance_main);
                    $balance_affiliate = Currency::convert($main_currency, $member_currency, $m->balance_affiliate);
                    $ref_percent = $m->ref_percent;
                    $ref_count = Member::where('rid', $m->id)->count();
                    $register_time = date('d.m.Y', $m->created_at);
                    $tstep = $m->tstep;

                    $currency = $shop_currency[$member_currency];

                    $tdata = json_decode($m->tdata);
                }

                $member_currency = $m->currency;
                $member_currency_symbol = $shop_currency[$member_currency];

                $min_sum_topup = Currency::convert($main_currency, $member_currency, $set_shop->min_sum_topup);
                $min_sum_withdrawal_card = Currency::convert($main_currency, $member_currency, $set_shop->min_sum_withdrawal_card);
                $min_sum_withdrawal_balance = Currency::convert($main_currency, $member_currency, $set_shop->min_sum_withdrawal_balance);

                $date_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

                $kp = $this->keyb('home', $sid, $count_buttons);
                $kp_home = $kp;

            }

            if ($update->getMessage()->has('photo')) {

                $count = count($message->getPhoto());
                $photo = $message->getPhoto()[$count-1]->getFileId();

                if ($tstep == 99 && !empty($tdata->subject_id)){

                    if(mb_strlen($message->getCaption()) < 4){
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Текст обращения не должен быть менее 4 символов", "parse_mode" => "HTML"]);
                        exit;
                    }


                    $date_now = Carbon::now()->timestamp;
                    $expired_at = strtotime(date('Y-m-d H:i', strtotime($ticket_auto_close_date)));

                    $sql = [
                        'user_id' => $m->id,
                        'operator_id' => 0,
                        'subject_id' => $tdata->subject_id,
                        'status' => 0,
                        'last_answer_at' => $date_now,
                        'expired_at' => $expired_at,
                        'created_at' => $date_now,
                    ];

                    $insert_id = Ticket::insertGetId($sql);

                    if ($insert_id) {

                        $ticket = Ticket::getByID($insert_id);
                        $ticket_subject = TicketSubject::getByID($ticket->subject_id);

                        $admins = Member::getByRole();
                        foreach ($admins as $a) {
                            try {
                                $role = RolePermission::getByPermission($a->role_id, 'tickets.all');
                                if ($role) {
                                    if ($a->tid > 0) {
                                        $ki_ticket[$a->id][] = ["text" => 'Ответить на тикет', "url" => config('app.url') . "/admin/tickets"];
                                        $kp = json_encode(["inline_keyboard" => array_chunk($ki_ticket[$a->id], 1)]);
                                        $tg->sendMessage(['chat_id' => $a->tid, 'text' => "🎟 Новый тикет\r\n├ Тема: <b>".$ticket_subject->title."</b>\r\n├ Источник: <b>Бот</b>\r\n└ Пользователь: <b>".$m->username."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                                    }
                                }
                            } catch (Exception $e){
                                continue;
                            }
                        }

                        $file = $tg->getFile(['file_id' => $photo]);
                        $filePath = $file->getFilePath();

                        $filename = Str::random(10);

                        $contents = file_get_contents("https://api.telegram.org/file/bot" . $shop_token . "/$filePath");
                        Storage::disk('public')->put("covers/" . $filename .'.jpg', $contents);

                        $date_now = Carbon::now()->timestamp;

                        $sql = [
                            'id' => $filename,
                            'title' => '',
                            'uid' => 0,
                            'ext' => 'jpg',
                            'size' => 0,
                            'type' => 0,
                            'uploaded_at' => $date_now,
                        ];

                        Attach::insertGetId($sql);

                        $block_message = '';
                        if ($message->getCaption()) {
                            $block_message = e($message->getCaption());
                        }

                        $msg_id = TicketMessage::insertGetId([
                            'user_id' => $m->id,
                            'operator_id' => 0,
                            'ticket_id' => $insert_id,
                            'message' => $block_message,
                            'image' => $filename,
                            'is_read' => 0,
                            'created_at' => $date_now,
                        ]);

                        $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ <b>Обращение отправлено</b>\r\n\r\nВам придет уведомление как только служба поддержки ответит на Ваше обращение.", "parse_mode" => "HTML", "reply_markup" => $kp_home]);

                        Member::edit($m->id, $sid, 'tdata', '{}');
                        Member::edit($m->id, $sid, 'tstep', 0);
                    }

                    exit;
                }

                if ($tstep == 100 && !empty($tdata->ticket_id)) {

                    $file = $tg->getFile(['file_id' => $photo]);
                    $filePath = $file->getFilePath();

                    $filename = Str::random(10);

                    $contents = file_get_contents("https://api.telegram.org/file/bot" . $shop_token . "/$filePath");
                    Storage::disk('public')->put("covers/" . $filename .'.jpg', $contents);

                    $date_now = Carbon::now()->timestamp;

                    $sql = [
                        'id' => $filename,
                        'title' => '',
                        'uid' => 0,
                        'ext' => 'jpg',
                        'size' => 0,
                        'type' => 0,
                        'uploaded_at' => $date_now,
                    ];

                    DB::table('attachments')->insertGetId($sql);

                    $block_message = '';
                    if ($message->getCaption()) {
                        $block_message = e($message->getCaption());
                    }

                    $msg_id = TicketMessage::insertGetId([
                        'user_id' => $m->id,
                        'operator_id' => 0,
                        'ticket_id' => $tdata->ticket_id,
                        'message' => $block_message,
                        'image' => $filename,
                        'is_read' => 0,
                        'created_at' => $date_now,
                    ]);

                    if ($msg_id) {

                        $ticket = Ticket::getByID($tdata->ticket_id);
                        $ticket_subject = TicketSubject::getByID($ticket->subject_id);

                        $admins = Member::getByRole();
                        foreach ($admins as $a) {
                            try {
                                $role = RolePermission::getByPermission($a->role_id, 'tickets.all');
                                if ($role) {
                                    if ($a->tid > 0) {
                                        $ki_ticket[$a->id][] = ["text" => 'Ответить на тикет', "url" => config('app.url') . "/admin/tickets"];
                                        $kp = json_encode(["inline_keyboard" => array_chunk($ki_ticket[$a->id], 1)]);
                                        $tg->sendMessage(['chat_id' => $a->tid, 'text' => "🎟 Новый ответ в тикете\r\n├ Тема: <b>".$ticket_subject->title."</b>\r\n├ Источник: <b>Бот</b>\r\n└ Пользователь: <b>".$m->username."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                                    }
                                }
                            } catch (Exception $e){
                                continue;
                            }
                        }

                        $ki[] = ["text" => 'Мои обращения', "callback_data" => "tickets/all"];

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ <b>Ответ отправлен</b>", "parse_mode" => "HTML", "reply_markup" => $kp]);

                        Member::edit($m->id, $sid, 'tdata', '{}');
                        Member::edit($m->id, $sid, 'tstep', 0);

                        $expired_at = strtotime(date('Y-m-d H:i', strtotime('+24 hours')));
                        Ticket::where('id', $tdata->ticket_id)->update(['last_answer_at' => $date_now, 'expired_at' => $expired_at, 'status' => 0]);
                    }
                }

//                $tg->sendMessage(['chat_id' => $cid, 'text' => 'i'.$filename]);
            }
//
//
//            if(!empty($photo)) {
//                switch ($photo) {
//                    case $photo:
//                        $photo = end($updates->getMessage()->getPhoto());
//                        $tg->sendMessage(['chat_id' => $cid, 'text' => $photo]);
//                        break;
//                }
//            }

            if(!empty($msg)) {
                switch ($msg) {

                    case strpos($msg, '/start ad_') !== false:
                        $param_code = explode('_', str_replace('/start ad_', '', $msg));

                        if (count($param_code) > 1) {
                            $code = implode('_', $param_code);
                            $link = LinkAd::getByCode($sid, $code);

                            if (!$link) {
                                $alert_text = __('bot.alert_link_not_found');
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, "reply_markup" => $kp]);
                                exit;
                            }

                            LinkAd::where('id', $link->id)->where('sid', $sid)->increment('visits_total', 1);

                            if (!LinkAdUser::getByLinkID($cid, $link->id)) {
                                LinkAd::where('id', $link->id)->where('sid', $sid)->increment('visits_unique', 1);
                                LinkAdUser::create(['id' => $cid, 'link_id' => $link->id, 'created_at' => strtotime('NOW')]);
                            }

                            $t = Text::getByType($sid, 'welcome');
                            $message_text = strip_tags(str_replace('{first_name}', $first_name, $t->text), config('app.tg_allowed_tags'));

                            if ($t->image != '') {
                                $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($t->image)), 'has_spoiler' => $t->is_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $t->disable_web_page_preview, "reply_markup" => $kp]);
                            } else {
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            }
                        }
                        break;

                    case strpos($msg, '/start auth') !== false:

                        $token = $m->remember_token;

                        $ki[] = ["text" => 'Быстрая авторизация', "url" => config('app.url') . "/login/" . $token];
                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if ($user_password != '') {
                            $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ <b>Успешная регистрация</b>\r\n\r\nТеперь вы можете зайти через сайт по ссылке ниже или остаться в боте.\r\n\r\nДанные для входа:\r\nЛогин: <code>t" . $cid . "</code>\r\nПароль: <code>" . $user_password . "</code>", "parse_mode" => "HTML", "reply_markup" => $kp]);
                        } else {
                            $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ <b>Авторизация на сайте</b>", "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }
                        break;

                    case strpos($msg, '/start support') !== false:
                        $b = Button::getByType($sid, 3);

                        $button_title = $b->title;

                        $message_title = "";
                        $message_text = strip_tags($b->text, config('app.tg_allowed_tags'));
                        $ki[] = ["text" => 'Создать обращение', "callback_data" => "tickets/create"];
                        $ki[] = ["text" => 'Мои обращения', "callback_data" => "tickets/all"];
                        $ki[] = ["text" => __('bot.btn_main'), "callback_data" => "menu/main"];

                        $message = "<b>".$button_title.$message_title."</b>\r\n\r\n".$message_text;

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        $tg->sendMessage(['chat_id' => $cid, 'text' => $message, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        break;

                    // case strpos($msg, '/start connect_') !== false:

                    //     $param_code = explode('_', $msg);

                    //     $member = Member::getByToken($param_code[1]);
                    //     if ($member) {
                    //         $member_telegram = Member::getCheckConnectTid($cid);

                    //         if ($member_telegram){
                    //             $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Данный Telegram-аккаунт уже подключен к другому аккаунту", "parse_mode" => "HTML", "reply_markup" => $kp]);
                    //             exit;
                    //         }

                    //         Member::editByID($member->id, $sid, 'tid', $cid);
                    //         Member::changeTokenByID($member->id, $sid);
                    //         $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ Аккаунт привязан к пользователю: <b>" . $member->username . "</b>", "parse_mode" => "HTML", "reply_markup" => $kp]);
                    //     } else {
                    //         $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Переданы некорректные параметры", "parse_mode" => "HTML", "reply_markup" => $kp]);
                    //     }
                    //     break;

                    case strpos($msg, '/start product') !== false:

                        $id = preg_replace('/[^0-9]/', '', $msg);

                        $product = Product::getByID($sid, $id);
                        if(!$product){
                            $alert_text = __('bot.alert_product_not_found');

                            $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, "reply_markup" => $kp]);
                            exit;
                        }

                        $count = $product->count_min;

                        $cat = Category::getByID($sid, $product->cid);

                        $tariffs = Tariff::getListByPid($sid, $product->id);

                        $category_title = $cat->title;
                        $title = $product->title;
                        $count_all = $product->count_all;
                        $count_min = $product->count_min;
                        $price = Currency::convert($main_currency, $member_currency, $tariffs[0]['price']);
                        $image = $product->image;
                        $has_spoiler = $product->image_spoiler;
                        $sum = $price;
                        $c2 = Category::getByID($product->sid, $product->cid);
                        $c1 = Category::getByID($product->sid, $c2->cid);
                        $product_link = config('app.url').'/'.$c1->alias.'/'.$c2->alias. '/' .$product->alias;

                        $status = StatusCheat::getByID($product->status_id);

                        if ($status->status == 0) {$status_title = 'Неизвестно';}
                        if ($status->status == 1) {$status_title = 'Рекомендуем';}
                        if ($status->status == 2) {$status_title = 'Не рекомендуем';}
                        if ($status->status == 3) {$status_title = 'На обновлении';}
                        if ($status->status == 4) {$status_title = 'На страх и риск';}

                        $hack_status = HackStatus::getByID($product->hack_status);

                        $status = '';
                        if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                        $type_msg = '';
                        if($image != '') {
                            $type_msg = '/with_photo';
                        }

                        $description = '';
                        if(strip_tags($product->description, config('app.tg_allowed_tags')) != '') {
                            $description = strip_tags($product->description, config('app.tg_allowed_tags'));
                        }

                        $message_text = str_replace(':title', $title.' '.$status, __('bot.text_product_endless'));
                        $message_text = str_replace(':count_min', $count_min, $message_text);
                        $message_text = str_replace(':count', $count_all, $message_text);
                        $message_text = str_replace(':status', $status_title, $message_text);
                        $message_text = str_replace(':category', $category_title, $message_text);
                        $message_text = str_replace(':price', 'от '.$price.''.$currency, $message_text);
                        $message_text = str_replace(':description', $description, $message_text);

                        $btn_buy = str_replace(':sum', $sum.''.$currency, __('bot.btn_buy'));

                        $ki[] = ["text" => '📃 Подробнее о товаре', "url" => $product_link];

//                        if($count_all > 0) {
                            $ki[] = ["text" => $btn_buy, "callback_data" => "products/" . $id . "/" . $count . "/tariffs" . $type_msg];
//                        }

                        if($sum > 0) {
                            $ki[] = ["text" => __('bot.btn_write_promocode'), "callback_data" => "products/" . $id . "/" . $count . "/promocode".$type_msg];
                        }

                        $ki[] = ["text" => __('bot.btn_share_link'), "callback_data" => "products/".$id."/".$count."/share".$type_msg];

                        $kp = array_chunk($ki, 1);


                        $back[] = ["text" => __('bot.btn_back'), "callback_data" => "categories/".$product->cid.$type_msg];
                        array_push($kp, $back);


                        $kp = json_encode(["inline_keyboard" => $kp]);

                        if($image != '') {
                            $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        } else {
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "reply_markup" => $kp, "parse_mode" => "HTML"]);
                        }
                        break;

                    case strpos($msg, '/start') !== false:

                        $t = Text::getByType($sid, 'welcome');

                        $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                        $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                        $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                        $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                        if(strpos($message_text, '{first_name}') !== false) {
                            $message_text = str_replace('{first_name}', $first_name, $message_text);
                        }

                        if($t->buttons != '{}'){
                            foreach (json_decode($t->buttons, true) as $item) {
                                $keyboard_inline[] = [$item];
                            }
                            $keyboard_params = ["inline_keyboard"=>$keyboard_inline];
                            $kp = json_encode($keyboard_params);
                        }

                        if($t->image != '') {
                            $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($t->image)), 'has_spoiler' => $t->is_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $t->disable_web_page_preview, "reply_markup" => $kp]);
                        } else {
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }
                        break;

                    case $msg:

                        $agreement = Text::getByType($sid, 'agreement');

                        if($ch_sub_set->is_active == 1 && $ch_sub_count >= 1) {
                            $channel_set = ChannelSubSettings::getByShopID($sid);
                            $channels = ChannelSub::getAllActive($sid);

                            $count_columns = $channel_set->count_columns;
                            $button_check = $channel_set->button_check;

                            $message_text = str_replace(['</p><p>'], "\r\n", $channel_set->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                            if(strpos($message_text, '{first_name}') !== false) {
                                $message_text = str_replace('{first_name}', $first_name, $message_text);
                            }

                            $keyboard_inline = [];
                            $count_resources = 0;

                            foreach ($channels as $c) {
                                $check = Http::get('https://api.telegram.org/bot'.$shop_token.'/getChatMember', ['chat_id' => '-100'.$c->cid, 'user_id' => $cid])->json();
                                if ($check['result']['status'] != 'member' and $check['result']['status'] != 'creator' and $check['result']['status'] != 'administrator') {
                                    $count_resources++;
                                    $keyboard_inline[] = ['text' => $c->title, 'url' => $c->link];
                                }
                            }

                            if ($count_resources > 0) {
                                $kp = array_chunk($keyboard_inline, $count_columns);

                                $keyboard_check[] = ['text' => $button_check, 'callback_data' => 'channels/sub/check'];

                                array_push($kp, $keyboard_check);

                                $keyboard = json_encode(["inline_keyboard" => $kp]);

                                $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $keyboard]);
                                exit;
                            }
                        }

                        if($agreement->is_active == 1 && $m->is_agreement == 0){
                            $ki[] = ["text" => 'Принять и продолжить', "callback_data" => "agreement/check"];
                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                            $message_text = str_replace(['</p><p>'], "\r\n", $agreement->text);
                            $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                            $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                            $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));

                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            exit;
                        }

                        if ($tstep == 10){

                            if(!preg_match('/^[0-9]/', $msg) ? true : false){
                                $alert_text = __('bot.alert_sum_invalid_format');
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile"];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            $main_sum = Currency::convert($member_currency, $main_currency, $msg);

                            if ($msg < $min_sum_topup) {
                                $alert_text = str_replace(':sum', $min_sum_topup.''.$currency, __('bot.alert_min_sum_topup'));
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, "parse_mode" => "HTML"]);
                                exit;
                            }

                            $expired_at = strtotime(date('Y-m-d H:i', strtotime('+'.$processing_time.' minutes')));
                            $order_hash = mb_strtoupper(Str::random(config('app.order_hash_line')));

                            $sql = [
                                'sid' => $sid,
                                'pid' => 0,
                                'tid' => 0,
                                'bid' => $m->id,
                                'oid' => '0',
                                'title' => 'Пополнение баланса',
                                'amount' => $main_sum,
                                'currency' => $main_currency,
                                'amount_rub' => 0,
                                'count_all' => 1,
                                'promo_id' => 0,
                                'is_sent' => 0,
                                'msg_id' => 0,
                                'method_payment' => '',
                                'status' => 1,
                                'type' => 0,
                                'delivery_hash' => '',
                                'hash' => $order_hash,
                                'payment_at' => 0,
                                'expired_at' => $expired_at,
                                'created_at' => strtotime('NOW')
                            ];


                            if(Order::create($sql)) {

                                $ki[] = ["text" => __('bot.btn_payment'), "web_app" => ['url' => config('app.url_invoice'). $order_hash]];
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/balance/topup"];

                                $message_text = str_replace(':sum', $msg.''.$currency, __('bot.text_topup_sum'));

                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "reply_markup" => $kp, "parse_mode" => "HTML"]);

                                Member::edit($m->id, $sid, 'tstep', 0);
                            }
                            exit;
                        }
                        if ($tstep == 11){

                            if(!preg_match('/^[a-zA-Z0-9]/', $msg) ? true : false){
                                $alert_text = __('bot.alert_card_invalid_format');
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/affiliate/withdrawal"];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            $card_number = preg_replace('/\s+/', '', $msg);

                            if(strlen($card_number) < 16){
                                $alert_text = __('bot.alert_card_number_len');
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, "parse_mode" => "HTML"]);
                                exit;
                            }

                            $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];

                            $message_text = str_replace(':card_number', $card_number, __('bot.alert_success_withdrawal_card'));
                            $message_text = str_replace(':sum', $balance_affiliate.$member_currency_symbol, $message_text);

                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "reply_markup" => $kp, "parse_mode" => "HTML"]);

                            $id = Withdrawal::add($m->id, $sid, $m->balance_affiliate, $msg, $first_name, 2, 1);

                            Member::edit($m->id, $sid, 'balance_affiliate', 0);
                            Member::edit($m->id, $sid, 'tstep', 0);

                            $ki_adm[] = ["text" => "Подтвердить перевод", "callback_data" => "adm/withdrawal/accept/".$id];

                            $member_block = $first_name." (ID: <code>".$cid."</code>)";

                            $message_text = str_replace(':sum', $m->balance_affiliate.''.$main_currency_symbol, __('bot.text_adm_withdrawal'));
                            $message_text = str_replace(':card_number', $card_number, $message_text);
                            $message_text = str_replace(':member_block', $member_block, $message_text);

                            $kp_adm = json_encode(["inline_keyboard" => array_chunk($ki_adm, 1)]);
                            $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => $message_text, "reply_markup" => $kp_adm, "parse_mode" => "HTML"]);

                            exit;
                        }
                        if ($tstep == 12 && !empty($tdata->id)){

                            $id = $tdata->id;
                            $count = $tdata->count;

                            if(!preg_match('/^[a-zA-Z0-9_]/', $msg) ? true : false){
                                $alert_text = __('bot.alert_promocode_invalid_format');
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            $product = Product::getByID($sid, $id);
                            if(!$product){
                                $alert_text = __('bot.alert_product_not_found');
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text]);
                                exit;
                            }

                            $coupon = Coupon::getByCode($sid, $id, $msg);
                            if(!$coupon){
                                $alert_text = __('bot.alert_promocode_not_found');
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            $count_uses_user = CouponUse::where('promo_id', $coupon->id)
                                ->where('chat_id', $cid)
                                ->where('shop_id', $sid)
                                ->count();

                            if($coupon->is_new_users == 1 && $date_ago > $joined_at){
                                $alert_text = str_replace(':promo', $coupon->code, __('bot.alert_not_new_user'));
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            if($coupon->is_one_time == 1 && $count_uses_user >= 1){
                                $alert_text = str_replace(':promo', $coupon->code, __('bot.alert_max_uses_one_time'));
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            if($coupon->count_uses_max == 0){
                                $alert_text = str_replace(':promo', $coupon->code, __('bot.alert_promocode_not_limit'));
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            $sale = $coupon->sale;
                            $promocode = $coupon->code;
                            $sale_type = $coupon->sale_type;

                            if($sale_type == 0) {$coupon_sale = "-".$sale.'%';}
                            if($sale_type == 1) {$coupon_sale = "-".$sale.$currency;}

                            $cat = Category::getByID($sid, $product->cid);

                            $tariffs = Tariff::getListByPid($sid, $product->id);

                            $category_title = $cat->title;
                            $title = $product->title;
                            $count_all = $product->count_all;
                            $count_min = $product->count_min;
                            $price = Currency::convert($main_currency, $member_currency, $tariffs[0]['price']);
                            $image = $product->image;
                            $has_spoiler = $product->image_spoiler;
                            $sum = $price;
                            $c2 = Category::getByID($product->sid, $product->cid);
                            $c1 = Category::getByID($product->sid, $c2->cid);
                            $product_link = config('app.url').'/'.$c1->alias.'/'.$c2->alias. '/' .$product->alias;

                            $status = StatusCheat::getByID($product->status_id);

                            if ($status->status == 0) {$status_title = 'Неизвестно';}
                            if ($status->status == 1) {$status_title = 'Рекомендуем';}
                            if ($status->status == 2) {$status_title = 'Не рекомендуем';}
                            if ($status->status == 3) {$status_title = 'На обновлении';}
                            if ($status->status == 4) {$status_title = 'На страх и риск';}
                            
                            $hack_status = HackStatus::getByID($product->hack_status);

                            $status = '';
                            if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                            if($coupon->min_sum > $sum){
                                $alert_text = str_replace(':sum', $coupon->min_sum.''.$currency, __('bot.alert_promocode_min_sum'));
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            if($sale_type == 0) {
                                if($sale > 100){
                                    $alert_text = __('bot.alert_sale_type_0');
                                    $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                    $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                    $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                    exit;
                                }
                            }
                            if($sale_type == 1) {
                                if($sale > $sum){
                                    $alert_text = __('bot.alert_sale_type_1');
                                    $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id."/".$count];
                                    $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                    $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                    exit;
                                }
                            }

                            $type_msg = '';
                            if($image != '') {
                                $type_msg = '/with_photo';
                            }

                            if($sale_type == 0) {$sum = $sum - ($sum * $sale / 100);}
                            if($sale_type == 1) {$sum = $sum - $sale;}

                            Member::edit($m->id, $sid, 'tdata', json_encode(["id" => $id, "count" => $count, "sum" => $sum, 'promo_id' => $coupon->id]));

                            $description = '';
                            if(strip_tags($product->description, config('app.tg_allowed_tags')) != '') {
                                $description = strip_tags($product->description, config('app.tg_allowed_tags'));
                            }


                            $message_text = str_replace(':title', $title.' '.$status, __('bot.text_product_endless'));
                            $message_text = str_replace(':count_min', $count_min, $message_text);
                            $message_text = str_replace(':count', $count_all, $message_text);
                            $message_text = str_replace(':status', $status_title, $message_text);
                            $message_text = str_replace(':category', $category_title, $message_text);
                            $message_text = str_replace(':price', 'от '.$price.''.$currency, $message_text);
                            $message_text = str_replace(':description', $description, $message_text);

                            $btn_buy = str_replace(':sum', $sum.''.$currency, __('bot.btn_buy'));

                            $ki[] = ["text" => '📃 Подробнее о товаре', "url" => $product_link];

//                            if($count_all > 0 && $count >= $product->count_min) {
                                $ki[] = ["text" => $btn_buy, "callback_data" => "products/" . $id . "/".$count."/tariffs".$type_msg];
                                $ki[] = ["text" => "🎁 ".$promocode.": ".$coupon_sale, "callback_data" => "products/".$id."/".$count."/promocode".$type_msg];
//                            }

                            $ki[] = ["text" => __('bot.btn_share_link'), "callback_data" => "products/".$id."/".$count."/share".$type_msg];

                            $kp = array_chunk($ki, 1);

                            $back[] = ["text" => __('bot.btn_back'), "callback_data" => "categories/".$product->cid.$type_msg];
                            array_push($kp, $back);

                            $kp = json_encode(["inline_keyboard" => $kp]);

                            if($image != '') {
                                $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            } else {
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            }

                            Member::edit($m->id, $sid, 'tstep', 0);
                            exit;
                        }
                        if ($tstep == 99 && !empty($tdata->subject_id)){

                            if(mb_strlen($msg) < 4){
                                $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Текст обращения не должен быть менее 4 символов", "parse_mode" => "HTML"]);
                                exit;
                            }

                            if(Button::getBySearchTitle($msg)){
                                $ki[] = ["text" => __('bot.btn_cancel'), "callback_data" => "act/cancel"];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => '⚠️ Прежде чем перейти в меню, отмените шаг с созданием <b>тикета</b>.', 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            $b = Button::getByTitle($sid, $msg);

                            if($b){
                                $ki[] = ["text" => 'Завершить обращение', "callback_data" => "act/cancel"];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => '⚠️ Неверный текст обращения', "reply_markup" => $kp, "parse_mode" => "HTML"]);
                                exit;
                            }

                            $date_now = Carbon::now()->timestamp;
                            $expired_at = strtotime(date('Y-m-d H:i', strtotime($ticket_auto_close_date)));

                            $sql = [
                                'user_id' => $m->id,
                                'operator_id' => 0,
                                'subject_id' => $tdata->subject_id,
                                'status' => 0,
                                'last_answer_at' => $date_now,
                                'expired_at' => $expired_at,
                                'created_at' => $date_now,
                            ];

                            $insert_id = Ticket::insertGetId($sql);

                            if ($insert_id) {

                                $ticket = Ticket::getByID($insert_id);
                                $ticket_subject = TicketSubject::getByID($ticket->subject_id);

                                $admins = Member::getByRole();
                                foreach ($admins as $a) {
                                    try {
                                        $role = RolePermission::getByPermission($a->role_id, 'tickets.all');
                                        if ($role) {
                                            if ($a->tid > 0) {
                                                $ki_ticket[$a->id][] = ["text" => 'Ответить на тикет', "url" => config('app.url') . "/admin/tickets"];
                                                $kp = json_encode(["inline_keyboard" => array_chunk($ki_ticket[$a->id], 1)]);
                                                $tg->sendMessage(['chat_id' => $a->tid, 'text' => "🎟 Новый тикет\r\n├ Тема: <b>".$ticket_subject->title."</b>\r\n├ Источник: <b>Бот</b>\r\n└ Пользователь: <b>".$m->username."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                                            }
                                        }
                                    } catch (Exception $e){
                                        continue;
                                    }
                                }

                                $msg_id = TicketMessage::insertGetId([
                                    'user_id' => $m->id,
                                    'operator_id' => 0,
                                    'ticket_id' => $insert_id,
                                    'message' => e($msg),
                                    'image' => '',
                                    'is_read' => 0,
                                    'created_at' => $date_now,
                                ]);

                                $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ <b>Обращение отправлено</b>\r\n\r\nВам придет уведомление как только служба поддержки ответит на Ваше обращение.", "parse_mode" => "HTML", "reply_markup" => $kp_home]);

                                Member::edit($m->id, $sid, 'tdata', '{}');
                                Member::edit($m->id, $sid, 'tstep', 0);
                            }

                            exit;
                        }
                        if ($tstep == 100 && !empty($tdata->ticket_id)){

                            if(mb_strlen($msg) < 4){
                                $tg->sendMessage(['chat_id' => $cid, 'text' => "⚠️ Текст обращения не должен быть менее 4 символов", "parse_mode" => "HTML"]);
                                exit;
                            }

                            if(Button::getBySearchTitle($msg)){
                                $ki[] = ["text" => __('bot.btn_cancel'), "callback_data" => "act/cancel"];
                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => '⚠️ Прежде чем перейти в меню, отмените шаг с вводом <b>ответа в тикет</b>.', 'reply_markup' => $kp, 'parse_mode' => "HTML"]);
                                exit;
                            }

                            $date_now = Carbon::now()->timestamp;

                            $msg_id = TicketMessage::insertGetId([
                                'user_id' => $m->id,
                                'operator_id' => 0,
                                'ticket_id' => $tdata->ticket_id,
                                'message' => e($msg),
                                'image' => '',
                                'is_read' => 0,
                                'created_at' => $date_now,
                            ]);

                            if ($msg_id) {

                                $ticket = Ticket::getByID($tdata->ticket_id);
                                $ticket_subject = TicketSubject::getByID($ticket->subject_id);

                                $admins = Member::getByRole();
                                foreach ($admins as $a) {
                                    try {
                                        $role = RolePermission::getByPermission($a->role_id, 'tickets.all');
                                        if ($role) {
                                            if ($a->tid > 0) {
                                                $ki_ticket[$a->id][] = ["text" => 'Ответить на тикет', "url" => config('app.url') . "/admin/tickets"];
                                                $kp = json_encode(["inline_keyboard" => array_chunk($ki_ticket[$a->id], 1)]);
                                                $tg->sendMessage(['chat_id' => $a->tid, 'text' => "🎟 Новый ответ в тикете\r\n├ Тема: <b>".$ticket_subject->title."</b>\r\n├ Источник: <b>Бот</b>\r\n└ Пользователь: <b>".$m->username."</b>", 'parse_mode' => 'HTML', 'reply_markup' => $kp]);
                                            }
                                        }
                                    } catch (Exception $e){
                                        continue;
                                    }
                                }

                                $ki[] = ["text" => 'Мои обращения', "callback_data" => "tickets/all"];

                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => "✅ <b>Ответ отправлен</b>", "parse_mode" => "HTML", "reply_markup" => $kp]);

                                Member::edit($m->id, $sid, 'tdata', '{}');
                                Member::edit($m->id, $sid, 'tstep', 0);

                                $expired_at = strtotime(date('Y-m-d H:i', strtotime('+24 hours')));
                                Ticket::where('id', $tdata->ticket_id)->update(['last_answer_at' => $date_now, 'expired_at' => $expired_at, 'status' => 0]);
                            }

                            exit;
                        }

                        Member::edit($m->id, $sid, 'tstep', 0);

                        $b = Button::getByTitle($sid, $msg);
                        if(!$b){
                            $tg->sendMessage(['chat_id' => $cid, 'text' => __('bot.alert_command_not_found'), "reply_markup" => $kp, "parse_mode" => "HTML"]);
                            exit;
                        }

                        $message_text = str_replace(['</p><p>'], "\r\n", $b->text);
                        $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                        $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                        $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));
                        $button_title = $b->title;
                        $button_image = $b->image;
                        $has_spoiler = $b->image_spoiler;
                        $count_buys = Order::where('bid', $m->id)->where('sid', $shop->id)->where('status', 2)->count();

                        if($b->type == 0){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_products'));
                            $message_text = str_replace(':button_text', strip_tags($b->text, config('app.tg_allowed_tags')), $message_text);

                            $kp = Button::getCategoriesByShopID($sid, $count_products);
                        }
                        if($b->type == 1){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_profile'));
                            $message_text = str_replace(':my_id', $cid, $message_text);
                            $message_text = str_replace(':register_time', $register_time, $message_text);
                            $message_text = str_replace(':balance_main', $balance_main.''.$currency, $message_text);
                            $message_text = str_replace(':balance_affiliate', $balance_affiliate.''.$currency, $message_text);
                            $message_text = str_replace(':count_buys', $count_buys, $message_text);

                            $kp = Button::getProfileByShopID($sid, $count_buttons_profile);
                        }
                        if($b->type == 2){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_products'));
                            $message_text = str_replace(':button_text', strip_tags($b->text, config('app.tg_allowed_tags')), $message_text);

                            $kp = Button::getButtonsByID($b->id);
                        }
                        if($b->type == 3){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_products'));
                            $message_text = str_replace(':button_text', strip_tags($b->text, config('app.tg_allowed_tags')), $message_text);

                            $ki[] = ["text" => 'Создать обращение', "callback_data" => "tickets/create"];
                            $ki[] = ["text" => 'Мои обращения', "callback_data" => "tickets/all"];
                            $ki[] = ["text" => __('bot.btn_main'), "callback_data" => "menu/main"];

                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        }

                        if($button_image != '') {
                            $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($button_image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        } else {
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "reply_markup" => $kp, "parse_mode" => "HTML"]);
                        }
                        break;
                }
            }

            if(!empty($cd)) {
                switch ($cd) {
//
//                    case $cd:
//                        $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $cd, 'show_alert' => true]);
//                        break;

                    case preg_match("/^agreement\/check/", $cd) ? true : false:
                        $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);

                        $alert_text = __('bot.alert_agreement_success');
                        $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp]);

                        Member::edit($m->id, $sid, 'is_agreement', 1);
                        break;

                    case preg_match('/^channels\/sub\/check/', $cd) ? true : false:
                        $channel_set = ChannelSubSettings::getByShopID($sid);
                        $channels = ChannelSub::getAllActive($sid);

                        $count_columns = $channel_set->count_columns;
                        $button_check = $channel_set->button_check;

                        $keyboard_inline = [];
                        $count_resources = 0;

                        foreach ($channels as $c) {
                            $check = Http::get('https://api.telegram.org/bot'.$shop_token.'/getChatMember', ['chat_id' => '-100'.$c->cid, 'user_id' => $cid])->json();
                            if ($check['result']['status'] != 'member' and $check['result']['status'] != 'creator' and $check['result']['status'] != 'administrator') {
                                $count_resources++;
                                $keyboard_inline[] = ['text' => $c->title, 'url' => $c->link];
                            }
                        }

                        if ($count_resources > 0) {
                            $alert_text = __('bot.alert_sub_not_found');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);

                            $kp = array_chunk($keyboard_inline, $count_columns);

                            $keyboard_check[] = ['text' => $button_check, 'callback_data' => 'channels/sub/check'];

                            array_push($kp, $keyboard_check);

                            $keyboard = json_encode(["inline_keyboard" => $kp]);

                            $tg->editMessageReplyMarkup(['chat_id' => $cid, 'message_id' => $msg_id, "reply_markup" => $keyboard]);
                        } else {
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);

                            $alert_text = __('bot.alert_sub_success');
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $alert_text, 'reply_markup' => $kp]);
                        }

                        break;

                    case preg_match('/^tickets\/(show|main|create|reply|subject|all)(\/[0-9]+)?/', $cd, $matches) ? true : false:

                        $block_key = 'blocked_user_' . $m->id;
                        if (Cache::has($block_key) && Cache::get($block_key)) {
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => 'Доступ к тикетам закрыт на 24 часа.', 'show_alert' => true]);
                            exit;
                        }

                        $pageNumber = isset($matches[2]) ? (int)substr($matches[2], 1) : 1;

                        $b = Button::getByType($sid, 3);

                        $button_title = $b->title;

                        $type_msg = '';
                        $action = '';
                        $id = 0;
                        if (!empty(explode('/', $cd)[1])) {
                            $type_msg = explode('/', $cd)[1];
                        }
                        if (!empty(explode('/', $cd)[2])) {
                            $id = explode('/', $cd)[2];
                        }
                        if (!empty(explode('/', $cd)[3])) {
                            $action = explode('/', $cd)[3];
                        }

                        if ($type_msg == 'main') {
                            $message_title = "";
                            $message_text = strip_tags($b->text, config('app.tg_allowed_tags'));
                            $ki[] = ["text" => 'Создать обращение', "callback_data" => "tickets/create"];
                            $ki[] = ["text" => 'Мои обращения', "callback_data" => "tickets/all"];
                            $ki[] = ["text" => __('bot.btn_main'), "callback_data" => "menu/main"];
                        }

                        if ($type_msg == 'create') {
                            $message_title = "  ›  <b>Выбор темы</b>";
                            $message_text = 'Выберите тему обращения:';
                            $ticket_subjects = TicketSubject::getList();

                            foreach ($ticket_subjects as $s){
                                $ki[] = ["text" => $s->title, "callback_data" => "tickets/subject/".$s->id."/create"];
                            }
                            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "tickets/main"];
                        }

                        if ($type_msg == 'subject' && $id != 0 && $action == 'create') {
                            Member::edit($m->id, $sid, 'tdata', json_encode(['subject_id' => $id]));
                            Member::edit($m->id, $sid, 'tstep', 99);

                            $subject_title = TicketSubject::getByID($id)->title;

                            $message_title = "  ›  <b>Создание обращения</b>";
                            $message_text = "Тема: <b>".$subject_title."</b>\r\n\r\nПришлите в ответ текст вашего обращения:";
                            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "tickets/create"];
                        }

                        if ($type_msg == 'reply' && $id != 0 && $action == '') {
                            Member::edit($m->id, $sid, 'tdata', json_encode(['ticket_id' => $id]));
                            Member::edit($m->id, $sid, 'tstep', 100);

                            $message_title = "  ›  <b>Создание обращения</b>";
                            $message_text = "Пришлите в ответ текст Вашего ответа:";
                            $ki[] = ["text" => __('bot.btn_cancel'), "callback_data" => "act/cancel"];
                        }


                        if ($type_msg == 'show' && $id != 0 && $action == '') {
                            $ticket = Ticket::getByID($id);
                            $subject_title = TicketSubject::getByID($ticket->subject_id)->title;

                            if ($ticket->status == 0){
                                $status = 'Ожидает';
                                $last_answered = TicketMessage::getLastMsgByTicketID($ticket->id, 'user');
                                $block_image = '';
                                if($last_answered->image != ''){$block_image = '<a href="'.config('app.url').'/i'.$last_answered->image.'">🌇</a> ';}
                                $answered = "\r\n\r\nВаше сообщение: ".$block_image."<code>".$last_answered->message."</code>";
                            }
                            if ($ticket->status == 1){
                                $status = 'Решено';
                                $last_answered = TicketMessage::getLastMsgByTicketID($ticket->id, 'support');
                                $block_image = '';
                                if($last_answered->image != ''){$block_image = '<a href="'.config('app.url').'/i'.$last_answered->image.'">🌇</a> ';}
                                $answered = "\r\n\r\nОтвет поддержки: ".$block_image."<code>".$last_answered->message."</code>";
                            }
                            if ($ticket->status == 2){
                                $status = 'Отвечен';
                                $last_answered = TicketMessage::getLastMsgByTicketID($ticket->id, 'support');
                                $block_image = '';
                                if($last_answered->image != ''){$block_image = '<a href="'.config('app.url').'/i'.$last_answered->image.'">🌇</a> ';}
                                $answered = "\r\n\r\nОтвет поддержки: ".$block_image."<code>".$last_answered->message."</code>";
                                $ki[] = ["text" => 'Ответить', "callback_data" => "tickets/reply/".$id];
                            }

                            $message_title = "  ›  <b>Обращение</b>";
                            $message_text = "Тема: <b>".$subject_title."</b>\r\n\r\nСтатус: <b>".$status."</b>".$answered;
                            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "tickets/all"];
                        }

                        if ($type_msg == 'all') {
                            $message_title = "  ›  <b>Все обращения</b>";
                            $message_text = 'Здесь вы можете управлять обращениями.';

                            $itemsPerPage = 5;
                            $offset = ($pageNumber - 1) * $itemsPerPage;

                            $tickets = Ticket::getListByOffset($m->id, $offset, $itemsPerPage);

                            foreach ($tickets as $t) {

                                $subject_title = TicketSubject::getByID($t->subject_id)->title;

                                if ($t->status == 0){$status = '🕘 ';}
                                if ($t->status == 1){$status = '✅ ';}
                                if ($t->status == 2){$status = '📩 ';}

                                $last_answer_at = date('d.m.Y H:i', $t->last_answer_at);

                                $ki[] = [["text" => $status.$subject_title.'  •  '.$last_answer_at, "callback_data" => "tickets/show/".$t->id]];
                            }

                            $totalTickets = Ticket::where('user_id', $m->id)->count();
                            $totalPages = ceil($totalTickets / $itemsPerPage);

                            $navigationButtons = [];

                            if($pageNumber == 1) {
                                $page_number_prev = $totalPages;
                            } else {
                                $page_number_prev = ($pageNumber - 1);
                            }

                            if($pageNumber == $totalPages) {
                                $page_number_next = 1;
                            } else {
                                $page_number_next = ($pageNumber + 1);
                            }


                            $navigationButtons[] = ["text" => "‹", "callback_data" => "tickets/all/" . $page_number_prev];
                            $navigationButtons[] = ["text" => $pageNumber."/".$totalPages, "callback_data" => "tickets/all/" . ($pageNumber - 1)];
                            $navigationButtons[] = ["text" => "›", "callback_data" => "tickets/all/" . $page_number_next];

                            if (!empty($navigationButtons)) {
                                $ki[] = $navigationButtons;
                            }

                            $ki[] = [["text" => __('bot.btn_back'), "callback_data" => "tickets/main"]];
                        }

                        $message = "<b>".$button_title.$message_title."</b>\r\n\r\n".$message_text;

                        if($type_msg == 'all'){
                            $kp = json_encode(["inline_keyboard" => $ki]);
                        } else {
                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        }
                        $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        break;

                    case preg_match('/^products\/(with_photo|all)/', $cd) ? true : false:

                        $type_msg = '';
                        if(!empty(explode('/', $cd)[1])) {
                            $type_msg = explode('/', $cd)[1];
                        }

                        $b = Button::getByType($sid, 0);

                        $message_text = str_replace(['</p><p>'], "\r\n", $b->text);
                        $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                        $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                        $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));
                        $button_title = $b->title;
                        $button_image = $b->image;
                        $has_spoiler = $b->image_spoiler;
                        $count_buys = Order::where('bid', $m->id)->where('sid', $shop->id)->where('status', 2)->count();
//                        $count_sum = Order::where('bid', $m->id)->where('sid', $shop->id)->where('status', 2)->sum('amount');

                        if($b->type == 0){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_products'));
                            $message_text = str_replace(':button_text', strip_tags($b->text, config('app.tg_allowed_tags')), $message_text);

                            $kp = Button::getCategoriesByShopID($sid, $count_categories);
                        }
                        if($b->type == 1){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_profile'));
                            $message_text = str_replace(':my_id', $cid, $message_text);
                            $message_text = str_replace(':register_time', $register_time, $message_text);
                            $message_text = str_replace(':balance_main', $balance_main.''.$currency, $message_text);
                            $message_text = str_replace(':balance_affiliate', $balance_affiliate.''.$currency, $message_text);
                            $message_text = str_replace(':count_buys', $count_buys, $message_text);
//                            $message_text = str_replace(':count_sum', $count_sum, $message_text);

                            $kp = Button::getProfileByShopID($sid, $count_buttons_profile);
                        }

                        if($type_msg != ''){
                            if($type_msg == 'with_photo') {
                                if ($button_image != '') {
                                    $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                    $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($button_image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                                } else {
                                    $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                    $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                                }
                            }
                        } else {
                            if($button_image != '') {
                                $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($button_image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            } else {
                                $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            }
                        }
                        break;

                    case preg_match('/^categories\/[0-9]/', $cd) ? true : false:

                        $id = explode('/', $cd)[1];


                        $type = '';
                        if(!empty(explode('/', $cd)[2])) {
                            $type = explode('/', $cd)[2];
                        }

                        $cat = Category::getByID($sid, $id);
                        if(!$cat){
                            $alert_text = __('bot.alert_category_not_found');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $button_title = $cat->title;
                        $disable_web_page_preview = $cat->disable_web_page_preview;
                        $image = $cat->image;
                        $has_spoiler = $cat->image_spoiler;
                        $button_description = strip_tags($cat->description, config('app.tg_allowed_tags'));


                        $button_title_two = '';
                        if($cat->cid != 0){
                            $cat_two = Category::getByID($sid, $cat->cid);
                            $button_title_two = $cat_two->title.'  ›  ';
                        }


                        $message_text = str_replace(':button_title', $button_title_two.$button_title, __('bot.text_category'));
                        $message_text = str_replace(':button_text', $button_description, $message_text);

                        $count_sub = Category::getCountSubcatByCatID($sid, $id);

                        if($count_sub > 0){
                            $kp = Button::getCategoriesByCatID($sid, $id, $cat->count_column);
                        } else {
                            $kp = Button::getProductsByCatID($sid, $id, $cat->count_column);
                        }

                        if($type != ''){
                            if($type == 'with_photo') {
                                if ($image != '') {
                                    $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                    $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $disable_web_page_preview, "reply_markup" => $kp]);
                                } else {
                                    $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                    $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $disable_web_page_preview, "reply_markup" => $kp]);
                                }
                            }
                        } else {
                            if($image != '') {
                                $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "disable_web_page_preview" => $disable_web_page_preview, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            } else {
                                $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "disable_web_page_preview" => $disable_web_page_preview, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            }
                        }

                        Member::edit($m->id, $sid, 'tstep', 0);
                        Member::edit($m->id, $sid, 'tdata', "{}");
                        Category::addView($id, $sid);
                        break;

                    case preg_match('/^orders\/[a-zA-Z0-9]/', $cd) ? true : false:

                        $hash = explode('/', $cd)[1];
                        $action = explode('/', $cd)[2];

                        $order = Order::getByHash($sid, $hash);
                        if(!$order){
                            $alert_text = __('bot.alert_order_not_found');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $product = Product::getByID($sid, $order->pid);
                        if(!$product){
                            $alert_text = __('bot.alert_product_not_found');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $cat = Category::getByID($sid, $product->cid);
                        $oid = $order->id;
                        $pid = $order->pid;
                        $category_title = $cat->title;
                        $expired_at = $order->expired_at;
                        $order_hash = $order->hash;
                        $title = $order->title;
                        $count = $order->count_all;
                        $sum = Currency::convert($main_currency, $member_currency, $order->amount);
                        $promo_id = $order->promo_id;
                        $funds = $sum - $balance_main;

                        if(strpos($action,'pay') !== false){

                            if($action == 'pay_from_balance'){

                                $date_now = Carbon::now()->timestamp;

                                if($expired_at < $date_now){
                                    $alert_text = __('bot.alert_payment_time_expired');
                                    $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                    $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                    $tg->sendMessage(['chat_id' => $cid, 'text' => "Главное меню", "reply_markup" => $kp]);

                                    exit;
                                }

                                if($sum > $balance_main){
                                    $alert_text = str_replace(':sum', $funds.''.$currency, __('bot.alert_insufficient_funds'));
                                    $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                    exit;
                                }

                                Product::where('id', $pid)->increment('count_sales', $count);

                                Material::markSoldForOrder($pid, $order->tid, $oid, $count);

                                Order::editByHash($hash, $sid, 'method_payment', 'bc');
                                Order::editByHash($hash, $sid, 'status', 2);
                                Order::editByHash($hash, $sid, 'payment_at', $date_now);
                                Member::where('id', $m->id)->where('sid', $sid)->decrement('balance_main', $order->amount);


                                $source = 'Бот';
                                $tg->sendMessage(['chat_id' => $notify_target_id, 'text' => "💸 Новая покупка\r\n├ Номер заказа: <code>" . $order_hash . "</code>\r\n├ Товар: <b>" . $product->title . "</b>\r\n├ Сумма: <b>" . $order->amount. $main_currency_symbol . "</b>\r\n├ Способ оплаты: <b>Баланс</b>\r\n├ Покупатель: <b>" . $first_name . " (ID: ".$cid.")</b>\r\n└ Источник: <b>".$source."</b>", "parse_mode" => "HTML"]);

                                if($m->rid > 0){
                                    $ref_id = $m->rid;
                                    $m_ref = Member::getByID($ref_id, $sid);
                                    if(isset($m_ref)) {
                                        $percent = $m_ref->ref_percent / 100;
                                        $balance = $order->amount * $percent;
                                        $ref_currency = $shop_currency[$m_ref->currency];
                                        $sum_currency = Currency::convert($main_currency, $m_ref->currency, $balance);
                                        $ref_balance = Currency::convert($main_currency, $m_ref->currency, $balance + $m_ref->balance_affiliate);

                                        Member::where('id', $ref_id)->where('sid', $sid)->increment('balance_affiliate', $balance);
                                        Refil::insertGetId(['sid' => $sid, 'owner_id' => $m->id, 'user_id' => $ref_id, 'sum' => $balance, 'created_at' => strtotime('NOW')]);

                                        if ($m_ref->tid > 0) {
                                            $tg->sendMessage(['chat_id' => $m_ref->tid, 'text' => "🤑 Реферальное вознаграждение\r\n├ Ваш доход: <b>" . $sum_currency . $ref_currency . " (" . $m_ref->ref_percent . "%)</b>\r\n└ Доступно для вывода: <b>" . $ref_balance . $ref_currency . "</b>", "reply_markup" => $kp, "parse_mode" => "HTML"]);
                                        }
                                    }
                                }

                                if($promo_id != 0) {
                                    Coupon::where('id', $promo_id)->where('sid', $sid)->decrement('count_uses_max', 1);
                                    CouponUse::create(['promo_id' => $promo_id, 'chat_id' => $cid, 'shop_id' => $sid, 'created_at' => $date_now]);
                                }

                                if($m->email_notify_orders == 1 && $m->email != '') {
                                    Mail::send('emails.order-receipt', ['product_title' => $product->title, 'product_sum' => $order->amount.''.$currency, 'order_link' => config('app.url_delivery').$order->delivery_hash], function ($message) use ($m) {
                                        $message->to($m->email, $m->username)
                                            ->subject('Новая покупка в боте');
                                    });
                                }

                                $message_text = str_replace(':order_hash', $hash, __('bot.alert_order_paid'));
                                $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $oid . "/get"];
                                $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $pid . "/" . $product->count_min];
                            }

                            if($action == 'pay_from_orders' || $action == 'pay') {
                                $message_text = str_replace(':title', $title, __('bot.text_product_buy'));
                                $message_text = str_replace(':order_hash', $order_hash, $message_text);
                                $message_text = str_replace(':time_paid', $processing_time, $message_text);
                                $message_text = str_replace(':time_date_paid', date('d.m.Y H:i', $expired_at) . " (МСК)", $message_text);
                                $message_text = str_replace(':category', $category_title, $message_text);
                                $message_text = str_replace(':count', $count, $message_text);
                                $message_text = str_replace(':sum', $sum.''.$currency, $message_text);

                                $ki[] = ["text" => __('bot.btn_payment_from_balance'), "callback_data" => "orders/" . $order_hash . "/pay_from_balance"];
//                                $ki[] = ["text" => __('bot.btn_payment'), "url" => config('app.url_invoice') . $order_hash];
                                $ki[] = ["text" => __('bot.btn_payment'), "web_app" => ['url' => config('app.url_invoice'). $order_hash]];
                                $ki[] = ["text" => __('bot.btn_order_cancel'), "callback_data" => "orders/" . $order_hash . "/confirm_cancel"];
                            }

                            if($action == 'pay_from_orders') {
                                $ki[] = ["text" => __('bot.btn_back_to_orders'), "callback_data" => "profile/orders/page/1"];
                            }

                            Member::edit($m->id, $sid, 'tdata', '{}');
                            Member::edit($m->id, $sid, 'tstep', 0);
                        }

                        if($action == 'confirm_cancel'){
                            $message_text = str_replace(':order_hash', $hash,__('bot.alert_order_confirm_cancel'));
                            $ki[] = ["text" => __('bot.btn_precisely_not_cancel'), "callback_data" => "orders/".$hash."/pay"];
                            $ki[] = ["text" => __('bot.btn_precisely_cancel'), "callback_data" => "orders/".$hash."/cancel"];
                        }

                        if($action == 'cancel'){
                            Order::editByHash($hash, $sid, 'status', 3);
                            Member::edit($m->id, $sid, 'tdata', '{}');
                            Product::where('id', $pid)->increment('count_all', $count);
                            Material::releaseFromOrder($order->id);
                            $message_text = str_replace(':order_hash', $hash,__('bot.alert_order_canceled'));
                            $ki[] = ["text" => __('bot.btn_back_to_product'), "callback_data" => "products/" . $order->pid . "/".$product->count_min];
                        }

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);

                        if($action == 'pay_from_balance'){
                            $t = Text::getByType($sid, 'after_payment');
                            if($t->is_active == 1) {
                                $message_text = str_replace(['</p><p>'], "\r\n", $t->text);
                                $message_text = str_replace(['<p>', '</p>'], '', $message_text);
                                $message_text = preg_replace('/<p[^>]*>(.*?)<\/p>/', "$1\r\n", $message_text);
                                $message_text = strip_tags($message_text, config('app.tg_allowed_tags'));
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML"]);
                            }
                        }

                        break;

                    case preg_match('/^products\/[0-9]/', $cd) ? true : false:

//                        $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $cd, 'show_alert' => false]);

                        $id = explode('/', $cd)[1];
                        $count = explode('/', $cd)[2];

                        $product = Product::getByID($sid, $id);
                        if(!$product){
                            $alert_text = __('bot.alert_product_not_found');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        if($count < $product->count_min){
                            $alert_text = str_replace(':count', $product->count_min, __('bot.alert_product_count_min'));
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        if(!empty($tdata->promo_id)) {
                            $coupon = Coupon::getByID($sid, $tdata->promo_id);
                            if (!$coupon) {
                                $alert_text = __('bot.alert_promocode_not_found');
                                $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                exit;
                            }

                            $sale = $coupon->sale;
                            $promocode = $coupon->code;
                            $sale_type = $coupon->sale_type;

                            if ($sale_type == 0) {$coupon_sale = "-" . $sale . '%';}
                            if ($sale_type == 1) {$coupon_sale = "-" . $sale . $currency;}
                        } else {
                            Member::edit($m->id, $sid, 'tdata', json_encode(['id' => $id, 'count' => $count]));
                        }

                        if($count < 0){
                            $alert_text = __('bot.alert_count_invalid');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $cat = Category::getByID($sid, $product->cid);

                        $tariffs = Tariff::getListByPid($sid, $product->id);

                        $disable_web_page_preview = $cat->disable_web_page_preview;
                        $category_image = $cat->image;
                        $category_title = $cat->title;
                        $pid = $product->id;
                        $title = $product->title;
                        $count_all = $product->count_all;
                        $count_min = $product->count_min;
                        $price = Currency::convert($main_currency, $member_currency, $tariffs[0]['price']);
                        $image = $product->image;
                        $has_spoiler = $product->image_spoiler;
                        $sum = $price;
                        $promo_id = 0;
                        $c2 = Category::getByID($product->sid, $product->cid);
                        $c1 = Category::getByID($product->sid, $c2->cid);
                        $product_link = config('app.url').'/'.$c1->alias.'/'.$c2->alias. '/' .$product->alias;

                        $status = StatusCheat::getByID($product->status_id);

                        if ($status->status == 0) {$status_title = 'Неизвестно';}
                        if ($status->status == 1) {$status_title = 'Рекомендуем';}
                        if ($status->status == 2) {$status_title = 'Не рекомендуем';}
                        if ($status->status == 3) {$status_title = 'На обновлении';}
                        if ($status->status == 4) {$status_title = 'На страх и риск';}

                        $hack_status = HackStatus::getByID($product->hack_status);

                        $status = '';
                        if($hack_status->title_pub != '') {$status = '(' . $hack_status->title_pub . ')';}

                        $type_msg = '';
                        if($image != '') {
                            $type_msg = '/with_photo';
                        }

                        if(!empty(explode('/', $cd)[3])){

                            $type = explode('/', $cd)[3];

                            $type_msg = '';
                            if(!empty(explode('/', $cd)[4])){
                                $type_msg = explode('/', $cd)[4];
                            }

                            // Тарифы
                            if($type == 'tariffs') {

                                $tariffs = Tariff::getListByPid($sid, $product->id);

                                foreach ($tariffs as $t) {

                                    $sum = Currency::convert($main_currency, $member_currency, $t['price']);

                                    if(!empty($tdata->promo_id)) {
                                        $promo_id = $tdata->promo_id;
                                        if ($sale_type == 0) {$sum = $sum - ($sum * $sale / 100);}
                                        if ($sale_type == 1) {$sum = $sum - $sale;}
                                    }

                                    $ki[] = ["text" => $sum.''.$currency.' за '.$t['title'], "callback_data" => "products/" . $id . "/".$count."/buy/".$t['id']];
                                }

                                $kp = array_chunk($ki, 1);

                                $back[] = ["text" => __('bot.btn_back'), "callback_data" => "products/".$id.'/'.$count];
                                array_push($kp, $back);

                                $keyboard = json_encode(["inline_keyboard" => $kp]);
                                $tg->editMessageReplyMarkup(['chat_id' => $cid, 'message_id' => $msg_id, "reply_markup" => $keyboard]);
                                exit;

                            }
                            // Создание заказа
                            if($type == 'buy') {

                                $tariff_id = '';
                                if(!empty(explode('/', $cd)[4])){
                                    $tariff_id = explode('/', $cd)[4];
                                }

                                $tariff = Tariff::getByID($sid, $id, $tariff_id);

                                $sum_main = $tariff->price;
                                $sum = Currency::convert($main_currency, $member_currency, $tariff->price);

                                if(!empty($tdata->promo_id)) {
                                    $promo_id = $tdata->promo_id;
                                    if ($sale_type == 0) {
                                        $sum = $sum - ($sum * $sale / 100);
                                        $sum_main = $sum_main - ($sum_main * $sale / 100);
                                    }
                                    if ($sale_type == 1) {
                                        $sum = $sum - $sale;
                                        $sum_main = $sum_main - $sale;
                                    }
                                }

                                $count_mat = Material::where('pid', $product->id)->where('tid', $tariff_id)->where('status', 1)->count();

                                if($count > $count_mat) {
                                    $alert_text = __('bot.alert_count_more');
                                    $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                    exit;
                                }

                                if($product->count_max == 1){
                                    $check = ProductPurchase::check($sid, $pid, $cid);
                                    if($check == 0){
                                        ProductPurchase::create(['product_id' => $pid, 'chat_id' => $cid, 'shop_id' => $sid, 'created_at' => strtotime('NOW')]);
                                    } else {
                                        $alert_text = __('bot.alert_product_count_max');
                                        $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                        exit;
                                    }
                                }

                                $expired_at = strtotime(date('Y-m-d H:i', strtotime('+'.$processing_time.' minutes')));
                                $order_hash = mb_strtoupper(Str::random(config('app.order_hash_line')));
                                $delivery_hash = Str::random(config('app.delivery_hash_line'));

                                $sql = [
                                    'sid' => $sid,
                                    'pid' => $pid,
                                    'bid' => $m->id,
                                    'tid' => $tariff_id,
                                    'oid' => '0',
                                    'title' => $title.' '.$status,
                                    'amount' => $sum_main,
                                    'currency' => $main_currency,
                                    'amount_rub' => $sum,
                                    'count_all' => $count,
                                    'promo_id' => $promo_id,
                                    'is_sent' => 0,
                                    'msg_id' => 0,
                                    'method_payment' => '',
                                    'status' => 1,
                                    'type' => 0,
                                    'delivery_hash' => $delivery_hash,
                                    'hash' => $order_hash,
                                    'payment_at' => 0,
                                    'expired_at' => $expired_at,
                                    'created_at' => strtotime('NOW')
                                ];

                                $order_id = Order::insertGetId($sql);

                                if($order_id) {

                                    if($sum > 0) {

                                        Product::where('id', $pid)->decrement('count_all', $count);
                                        Material::reserveForOrder($pid, $tariff_id, $order_id, $m->id, $count);

                                        $message_text = str_replace(':title', $title, __('bot.text_product_buy'));
                                        $message_text = str_replace(':order_hash', $order_hash, $message_text);
                                        $message_text = str_replace(':time_paid', $processing_time, $message_text);
                                        $message_text = str_replace(':time_date_paid', date('d.m.Y H:i', $expired_at)." (МСК)", $message_text);
                                        $message_text = str_replace(':category', $category_title, $message_text);
                                        $message_text = str_replace(':count', $count, $message_text);
                                        $message_text = str_replace(':sum', $sum.''.$currency, $message_text);

                                        $ki[] = ["text" => __('bot.btn_payment_from_balance'), "callback_data" => "orders/" . $order_hash . "/pay_from_balance"];
                                        $ki[] = ["text" => __('bot.btn_payment'), "web_app" => ['url' => config('app.url_invoice') . $order_hash]];
                                        $ki[] = ["text" => __('bot.btn_order_cancel'), "callback_data" => "orders/" . $order_hash . "/confirm_cancel"];

                                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                                        $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                        $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $disable_web_page_preview, "reply_markup" => $kp]);
//
                                        if($type == 'buy' and $order_id) {
                                            Order::editById($order_id, $sid, 'msg_id', $msg_id);
                                        }

                                        exit;

                                    } else {

                                        Product::where('id', $pid)->increment('count_sales', $count);
                                        $o = Order::getByHash($sid, $order_hash);

                                        Product::where('id', $pid)->decrement('count_all', $count);

                                        Material::markSoldForOrder($pid, $o->tid, $o->id, $count);

                                        Order::editByHash($order_hash, $sid, 'method_payment', 'bc');
                                        Order::editByHash($order_hash, $sid, 'status', 2);
                                        Order::editByHash($order_hash, $sid, 'payment_at', strtotime('NOW'));

                                        if ($promo_id != 0) {
                                            Coupon::where('id', $promo_id)->where('sid', $sid)->decrement('count_uses_max', 1);
                                            CouponUse::create(['promo_id' => $promo_id, 'chat_id' => $cid, 'shop_id' => $sid, 'created_at' => strtotime('NOW')]);
                                        }

                                        if($m->email_notify_orders == 1 && $m->email != '') {
                                            Mail::send('emails.order-receipt', ['product_title' => $product->title, 'product_sum' => $o->amount.''.$currency, 'order_link' => config('app.url_delivery').$o->delivery_hash], function ($message) use ($m) {
                                                $message->to($m->email, $m->username)
                                                    ->subject('Новая покупка в боте');
                                            });
                                        }

                                        $message_text = str_replace(':order_hash', $order_hash, __('bot.alert_order_paid'));

                                        $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $o->id . "/get"];
                                        $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $pid . "/".$product->count_min];

                                    }

                                    Member::edit($m->id, $sid, 'tdata', '{}');
                                    Member::edit($m->id, $sid, 'tstep', 0);

                                }
                            }
                            // Ссылка для шейринга
                            if($type == 'share') {
                                $message_text = str_replace(':title', $title, __('bot.text_share'));
                                $message_text = str_replace(':username', $shop_username, $message_text);
                                $message_text = str_replace(':id', $id, $message_text);

                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/" . $id . "/".$count];
                            }
                            // Ввод промокода
                            if($type == 'promocode') {
                                if($sum == 0){
                                    $alert_text = __('bot.alert_sum_invalid');
                                    $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                    exit;
                                }

                                $message_text = str_replace(':title', $title, __('bot.text_write_promocode'));

                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "products/" . $id . "/".$count];
                                Member::edit($m->id, $sid, 'tstep', 12);
                            }

                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                            if($type_msg != '' && $type_msg == 'with_photo'){
                                $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                                $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $disable_web_page_preview, "reply_markup" => $kp]);
                                $msg_id = $msg_id + 1;
                            } else {
                                $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $disable_web_page_preview, "reply_markup" => $kp]);
                            }

                            if($type == 'buy' and $order_id) {
                                Order::editById($order_id, $sid, 'msg_id', $msg_id);
                            }
                            exit;
                        }

//                        if($count > $count_all){
//                            $alert_text = __('bot.alert_count_more');
//                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
//                            exit;
//                        }

                        $description = '';
                        if(strip_tags($product->description, config('app.tg_allowed_tags')) != '') {
                            $description = strip_tags($product->description, config('app.tg_allowed_tags'));
                        }

                        $message_text = str_replace(':title', $title.' '.$status, __('bot.text_product_endless'));
                        $message_text = str_replace(':count_min', $count_min, $message_text);
                        $message_text = str_replace(':count', $count_all, $message_text);
                        $message_text = str_replace(':status', $status_title, $message_text);
                        $message_text = str_replace(':category', $category_title, $message_text);
                        $message_text = str_replace(':price', 'от '.$price.''.$currency, $message_text);
                        $message_text = str_replace(':description', $description, $message_text);

                        $btn_buy = str_replace(':sum', $sum.''.$currency, __('bot.btn_buy'));

                        $ki[] = ["text" => '📃 Подробнее о товаре', "url" => $product_link];

//                        if($count_all > 0 && $count >= $product->count_min) {
                            $ki[] = ["text" => $btn_buy, "callback_data" => "products/" . $id . "/".$count."/tariffs".$type_msg];
//                        }

                        if($sum > 0) {
                            if(!empty($tdata->promo_id)) {
                                $ki[] = ["text" => "🎁 " . $promocode . ": " . $coupon_sale, "callback_data" => "products/" . $id . "/" . $count . "/promocode".$type_msg];
                            } else {
                                $ki[] = ["text" => __('bot.btn_write_promocode'), "callback_data" => "products/" . $id . "/" . $count . "/promocode".$type_msg];
                            }
                        }

                        $ki[] = ["text" => __('bot.btn_share_link'), "callback_data" => "products/".$id."/".$count."/share".$type_msg];

                        $kp = array_chunk($ki, 1);


                        $back[] = ["text" => __('bot.btn_back'), "callback_data" => "categories/".$product->cid.$type_msg];
                        array_push($kp, $back);

                        $kp = json_encode(["inline_keyboard" => $kp]);

                        if($category_image == '' && $image == ''){
                            $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "disable_web_page_preview" => $disable_web_page_preview, "reply_markup" => $kp]);
                        }

                        if($category_image != '' && $image == ''){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }

                        if($category_image != '' && $image != ''){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "disable_web_page_preview" => $disable_web_page_preview, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }

                        if($category_image == '' && $image != ''){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "disable_web_page_preview" => $disable_web_page_preview, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }

                        Member::edit($m->id, $sid, 'tstep', 0);
                        Product::addView($pid, $sid);
                        break;

                    case preg_match('/^profile\/orders\/[0-9]/', $cd) ? true : false:

                        $id = explode('/', $cd)[2];

                        $order = Order::getByID($sid, $id);
                        if(!$order){
                            $alert_text = __('bot.alert_order_not_found');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $product = Product::getByID($sid, $order->pid);

                        if(!empty(explode('/', $cd)[3]) && explode('/', $cd)[3] == 'get'){
                            $body = Order::getMaterialsByID($m->id, $sid, $id);
                            if(mb_strlen($body) == 0){
                                $alert_text = __('bot.alert_failed_get_materials');
                                $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                exit;
                            }

                            $instruction = Instruction::getByPID($product->id);

                            $response = "✅ <b>Заказ <code>".$order->hash."</code></b>\r\n\r\n<code>".$body."</code>";
                            if(mb_strlen($response) > 4096){
                                $file_name = "files/".$order->bid."/Товар.txt";
                                Storage::disk('public')->put($file_name, $body);
                                $file_path = Storage::disk('public')->path($file_name);

                                $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);

                                if($instruction) {
                                    $ki[] = ["text" => 'Инструкция к читу', "url" => config('app.url') . '/instruction/'.$instruction->alias];
                                }
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/orders/".$order->id."/from_doc"];

                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->sendDocument(['chat_id' => $cid, 'document' => new InputFile($file_path), 'caption' => "✅ <b>Заказ <code>".$order->hash."</code></b>", "parse_mode" => "HTML", "reply_markup" => $kp]);
                                Storage::disk('public')->delete($file_name);
                            } else {

                                if($instruction) {
                                    $ki[] = ["text" => 'Инструкция к читу', "url" => config('app.url') . '/instruction/'.$instruction->alias];
                                }
                                $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/orders/".$order->id];

                                $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                                $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $response, "parse_mode" => "HTML", "reply_markup" => $kp]);
                            }
                            exit;
                        }

                        $sum = Currency::convert($main_currency, $member_currency, $order->amount);

                        if ($order->status == 1) {
                            $message_text = str_replace(':order_hash', $order->hash, __('bot.text_my_order_no_paid'));
                            $message_text = str_replace(':title', $order->title, $message_text);
                            $message_text = str_replace(':count', $order->count_all, $message_text);
                            $message_text = str_replace(':sum', $sum.''.$currency, $message_text);
                            $ki[] = ["text" => __('bot.btn_payment_from_balance'), "callback_data" => "orders/" . $order->hash . "/pay_from_balance"];
//                            $ki[] = ["text" => __('bot.btn_payment'), "url" => config('app.url_invoice').$order->hash];
                            $ki[] = ["text" => __('bot.btn_payment'), "web_app" => ['url' => config('app.url_invoice'). $order->hash]];
                        }

                        if ($order->status == 2) {
                            $message_text = str_replace(':order_hash', $order->hash, __('bot.text_my_order_paid'));
                            $message_text = str_replace(':title', $order->title, $message_text);
                            $message_text = str_replace(':count', $order->count_all, $message_text);
                            $message_text = str_replace(':sum', $sum.''.$currency, $message_text);
                            $message_text = str_replace(':payment_at', date('d.m.Y в H:i', $order->payment_at), $message_text);

                            $ki[] = ["text" => __('bot.btn_get_order'), "callback_data" => "profile/orders/" . $id . "/get"];
                            $ki[] = ["text" => __('bot.btn_buy_repeat'), "callback_data" => "products/" . $order->pid . "/".$product->count_min];
                        }

                        if ($order->status == 3) {
                            $message_text = str_replace(':order_hash', $order->hash, __('bot.text_my_order_canceled'));
                            $message_text = str_replace(':title', $order->title, $message_text);
                            $message_text = str_replace(':count', $order->count_all, $message_text);
                            $message_text = str_replace(':sum', $sum.''.$currency, $message_text);
                        }

                        if ($order->status == 4) {
                            $message_text = str_replace(':order_hash', $order->hash, __('bot.text_my_order_expired'));
                            $message_text = str_replace(':title', $order->title, $message_text);
                            $message_text = str_replace(':count', $order->count_all, $message_text);
                            $message_text = str_replace(':sum', $sum.''.$currency, $message_text);
                        }

                        $ki[] = ["text" => __('bot.btn_back_to_orders'), "callback_data" => "profile/orders/page/1"];

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if(!empty(explode('/', $cd)[3]) && explode('/', $cd)[3] == 'from_doc'){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        } else {
                            $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }
                        break;

                    case preg_match('/^profile\/orders\/page\/[0-9]/', $cd) ? true : false:

                        $page = explode('/', $cd)[3];

                        $b = Button::getByType($sid, 1);
                        $button_image = $b->image;

                        $count = Order::getCountByCid($m->id, $sid);
                        if($count == 0){
                            $alert_text = __('bot.alert_no_orders');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }


                        $pages = ceil($count / $orders_limit);
                        $orders = Order::getListByPage($m->id, $sid, $page, $orders_limit);

                        $ki = [];

                        foreach ($orders as $order) {

                            $tariff = Tariff::getByID($shop->id, $order->pid, $order->tid);

                            $block_tariff = 'Неизвестно';
                            if(isset($tariff)) {
                                $block_tariff = Tariff::num_decline($tariff->title, ['день', 'дня', 'дней']);
                            }

                            if($order->status == 1){
                                $title = "🟡 ".$order->title."  ·  ".$block_tariff;
                                $callback_data = "orders/".$order->hash."/pay_from_orders";
                            }
                            if($order->status == 2){
                                $title = "✅ ".$order->title."  ·  ".$block_tariff;
                                $callback_data = "profile/orders/".$order->id;
                            }
                            if($order->status == 3){
                                $title = "🔴 ".$order->title."  ·  ".$block_tariff;
                                $callback_data = "profile/orders/".$order->id;
                            }
                            if($order->status == 4){
                                $title = "🟠 ".$order->title."  ·  ".$block_tariff;
                                $callback_data = "profile/orders/".$order->id;
                            }

                            $ki[] = ["text" => $title, "callback_data" => $callback_data];
                        }

                        $kp = array_chunk($ki, 1);

                        $prev = $page - 1;
                        $next = $page + 1;

                        if($page == 1){
                            $prev = $pages;
                        }

                        if($page == $pages){
                            $next = 1;
                        }

                        if($pages > 1){
                        $str[] = ["text" => "‹", "callback_data" => "profile/orders/page/".$prev];
                        $str[] = ["text" => $page."/".$pages, "callback_data" => "profile/orders/pages"];
                        $str[] = ["text" => "›", "callback_data" => "profile/orders/page/".$next];
                        array_push($kp, $str);
                        }

                        $back[] = ["text" => __('bot.btn_back'), "callback_data" => "profile"];
                        array_push($kp, $back);

                        $message_text = __('bot.text_my_orders');

                        $kp = json_encode(["inline_keyboard" => $kp]);

                        if($button_image != ''){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        } else {
                            $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }
                        break;

                    case preg_match('/^profile\/affiliate\/withdrawal\/(card|balance)/', $cd) ? true : false:

                        $method = explode('/', $cd)[3];

                        if($method == 'balance') {
                            if($balance_affiliate < $min_sum_withdrawal_balance){
                                $alert_text = str_replace(':sum', $min_sum_withdrawal_balance.''.$currency, __('bot.alert_min_sum_withdrawal'));
                                $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                exit;
                            }
                            $ki[] = ["text" => __('bot.btn_profile'), "callback_data" => "profile"];
                            $message_text = __('bot.alert_success_withdrawal_balance');

                            Member::transferBalance($m->id, $sid);
                            Withdrawal::add($m->id, $sid, $m->balance_affiliate, '', '', 3, 1);
                        }

                        if($method == 'card') {
                            if($balance_affiliate < $min_sum_withdrawal_card){
                                $alert_text = str_replace(':sum', $min_sum_withdrawal_card.''.$currency, __('bot.alert_min_sum_withdrawal'));
                                $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                                exit;
                            }
                            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/affiliate"];
                            $message_text = __('bot.text_withdrawal_card');
                            Member::edit($m->id, $sid, 'tstep', 11);
                        }

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                        $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);

                        break;

                    case preg_match('/^profile\/balance\/topup\/sum\/[0-9]/', $cd) ? true : false:

                        $sum = explode('/', $cd)[4];

                        if($sum < $min_sum_topup){
                            $alert_text = str_replace(':sum', $min_sum_topup.''.$currency, __('bot.alert_min_sum_topup'));
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $main_sum = Currency::convert($member_currency, $main_currency, $sum);

                        $expired_at = strtotime(date('Y-m-d H:i', strtotime('+'.$processing_time.' minutes')));
                        $order_hash = mb_strtoupper(Str::random(config('app.order_hash_line')));
                        $delivery_hash = '';

                        $sql = [
                            'sid' => $sid,
                            'pid' => 0,
                            'bid' => $m->id,
                            'tid' => 0,
                            'oid' => '0',
                            'title' => 'Пополнение баланса',
                            'amount' => $main_sum,
                            'currency' => $main_currency,
                            'amount_rub' => 0,
                            'count_all' => 1,
                            'promo_id' => 0,
                            'is_sent' => 0,
                            'msg_id' => 0,
                            'method_payment' => '',
                            'status' => 1,
                            'type' => 0,
                            'delivery_hash' => $delivery_hash,
                            'hash' => $order_hash,
                            'payment_at' => 0,
                            'expired_at' => $expired_at,
                            'created_at' => strtotime('NOW')
                        ];


                        if(Order::create($sql)) {

                            $ki[] = ["text" => __('bot.btn_payment'), "web_app" => ['url' => config('app.url_invoice'). $order_hash]];
                            $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/balance/topup"];

                            $message_text = str_replace(':sum', $sum.''.$currency, __('bot.text_topup_sum'));

                            $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);
                            $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);

                            Member::edit($m->id, $sid, 'tdata', '{}');
                            Member::edit($m->id, $sid, 'tstep', 0);
                        }
                        break;

                    case preg_match('/^adm\/withdrawal\/accept\/[0-9]/', $cd) ? true : false:

                        $id = explode('/', $cd)[3];

                        $withdrawal = Withdrawal::getByID($id);
                        if(!$withdrawal){
                            $alert_text = __('bot.alert_withdrawal_not_found');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $m_ref = Member::getByID($withdrawal->user_id, $sid);

                        $member_currency = $m_ref->currency;
                        $member_currency_symbol = $shop_currency[$member_currency];

                        $sum_currency = Currency::convert($main_currency, $member_currency, $withdrawal->sum);

                        if($withdrawal->status == 2){
                            $alert_text = __('bot.alert_already_withdrawal_accepted');
                            $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                            exit;
                        }

                        $member_block = $m_ref->username . " (ID: <code>" . $m_ref->id . "</code>)";

                        $message_text = str_replace(':sum', $withdrawal->sum.''.$main_currency_symbol, __('bot.text_adm_withdrawal'));
                        $message_text = str_replace(':card_number', $withdrawal->card_number, $message_text);
                        $message_text = str_replace(':member_block', $member_block, $message_text);

                        $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text . __('bot.text_after_success'), "parse_mode" => "HTML"]);

                        if ($m_ref->tid != 0) {
                            $alert_text = str_replace(':sum', $sum_currency.''.$member_currency_symbol, __('bot.alert_withdrawal_accepted'));
                            $alert_text = str_replace(':card_number', $withdrawal->card_number, $alert_text);
                            $tg->sendMessage(['chat_id' => $m_ref->tid, 'text' => $alert_text, 'parse_mode' => 'HTML']);
                        }

                        Withdrawal::edit($withdrawal->id, $m_ref->id, 'status', 2);
                        Member::edit($m_ref->id, $sid, 'tstep', 0);

                        break;

                    case "profile":

                        $b = Button::getByType($sid, 1);

                        $message_text = $b->text;
                        $button_title = $b->title;
                        $button_image = $b->image;
                        $has_spoiler = $b->image_spoiler;
                        $count_buys = Order::where('bid', $m->id)->where('sid', $shop->id)->where('status', 2)->count();
//                        $count_sum = Order::where('bid', $m->id)->where('sid', $shop->id)->where('status', 2)->sum('sum');

                        if($b->type == 0){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_products'));
                            $message_text = str_replace(':button_text', strip_tags($b->text, config('app.tg_allowed_tags')), $message_text);


                            $kp = Button::getCategoriesByShopID($sid, $count_categories);
                        }
                        if($b->type == 1){
                            $message_text = str_replace(':button_title', $button_title, __('bot.text_profile'));
                            $message_text = str_replace(':my_id', $cid, $message_text);
                            $message_text = str_replace(':register_time', $register_time, $message_text);
                            $message_text = str_replace(':balance_main', $balance_main.''.$currency, $message_text);
                            $message_text = str_replace(':balance_affiliate', $balance_affiliate.''.$currency, $message_text);
                            $message_text = str_replace(':count_buys', $count_buys, $message_text);
//                            $message_text = str_replace(':count_sum', $count_sum, $message_text);

                            $kp = Button::getProfileByShopID($sid, $count_buttons_profile);
                        }

                        if($button_image != ''){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendPhoto(['chat_id' => $cid, 'photo' => new InputFile(Attach::getPathById($button_image)), 'has_spoiler' => $has_spoiler, 'caption' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        } else {
                            $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }

                        Member::edit($m->id, $sid, 'tstep', 0);
                        break;

                    case "products/info":
                        $alert_text = __('bot.alert_info_count');
                        $tg->answerCallbackQuery(['callback_query_id' => $callback_id, 'text' => $alert_text, 'show_alert' => true]);
                        break;

                    case "profile/affiliate":

                        $b = Button::getByType($sid, 1);
                        $button_image = $b->image;

//                        $ki[] = ["text" => __('bot.btn_referrals'), "callback_data" => "profile/affiliate/referrals"];
                        $ki[] = ["text" => __('bot.btn_withdrawal'), "callback_data" => "profile/affiliate/withdrawal"];
                        $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile"];

                        $message_text = str_replace(':balance', $balance_affiliate.''.$currency, __('bot.text_affiliate'));
                        $message_text = str_replace(':ref_percent', $ref_percent, $message_text);
                        $message_text = str_replace(':ref_count', $ref_count, $message_text);
                        $message_text = str_replace(':shop_username', $shop_username, $message_text);
                        $message_text = str_replace(':ref_code', $m->ref_code, $message_text);
                        $message_text = str_replace(':site', config('app.url'), $message_text);

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 1)]);

                        if($button_image != ''){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "reply_markup" => $kp, "parse_mode" => "HTML"]);
                        } else {
                            $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }

                        Member::edit($m->id, $sid, 'tstep', 0);
                        break;

                    case "profile/affiliate/referrals":

                        $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/affiliate"];

                        $message_text = str_replace(':count', Member::getCountReferralsByMID($m->id, $sid),  __('bot.text_referrals'));

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 2)]);
                        $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        break;

                    case "profile/affiliate/withdrawal":

                        $ki[] = ["text" => __('bot.btn_card'), "callback_data" => "profile/affiliate/withdrawal/card"];
                        $ki[] = ["text" => __('bot.btn_balance'), "callback_data" => "profile/affiliate/withdrawal/balance"];
                        $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile/affiliate"];

                        $message_text = __('bot.text_withdrawal');

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 2)]);
                        $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);

                        Member::edit($m->id, $sid, 'tstep', 0);
                        break;

                    case "profile/balance/topup":

                        $b = Button::getByType($sid, 1);
                        $button_image = $b->image;

                        foreach ($topup_sums as $s) {
                            $sum = Currency::convert($main_currency, $member_currency, $s);
                            $ki[] = ["text" => $sum."".$currency, "callback_data" => "profile/balance/topup/sum/".$sum];
                        }

                        $ki[] = ["text" => __('bot.btn_back'), "callback_data" => "profile"];

                        $message_text = str_replace(':min_sum', $min_sum_topup.''.$currency, __('bot.text_topup'));

                        $kp = json_encode(["inline_keyboard" => array_chunk($ki, 2)]);

                        if($button_image != ''){
                            $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                            $tg->sendMessage(['chat_id' => $cid, 'text' => $message_text, "reply_markup" => $kp, "parse_mode" => "HTML"]);
                        } else {
                            $tg->editMessageText(['chat_id' => $cid, 'message_id' => $msg_id, 'text' => $message_text, "parse_mode" => "HTML", "reply_markup" => $kp]);
                        }

                        Member::edit($m->id, $sid, 'tstep', 10);
                        break;

                    case 'menu/main':
                        Member::edit($m->id, $sid, 'tstep', 0);
                        Member::edit($m->id, $sid, 'tdata', '{}');
                        $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "Главное меню", "reply_markup" => $kp]);
                        break;

                    case 'act/cancel':
                        Member::edit($m->id, $sid, 'tstep', 0);
                        Member::edit($m->id, $sid, 'tdata', '{}');
                        $tg->deleteMessage(['chat_id' => $cid, 'message_id' => $msg_id]);
                        $tg->sendMessage(['chat_id' => $cid, 'text' => "Действие отменено", "reply_markup" => $kp]);
                        break;
                }
            }

            return response()->json(['ok' => true], 200);

        } catch (Exception $e){
            $tg->sendMessage(['chat_id' => 5649294232, 'text' => "<b>ERROR</b>\r\n\r\n".$e->getMessage()."\r\n\r\n".$e->getFile()." - ".$e->getLine(), "parse_mode" => "HTML"]);
        }
    }
}

