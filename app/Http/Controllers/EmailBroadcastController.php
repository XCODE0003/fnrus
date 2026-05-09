<?php

namespace App\Http\Controllers;

use App\Models\EmailBroadcast;
use App\Models\EmailBroadcastRecipient;
use App\Models\RolePermission;
use App\Services\EmailBroadcastService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmailBroadcastController extends Controller
{
    public $user;

    public function __construct(private readonly EmailBroadcastService $svc)
    {
        $this->middleware(function (Request $request, $next) {
            $this->user = Auth::user();
            return $next($request);
        });
    }

    /** @throws Exception */
    private function canOrThrow(string $permission): void
    {
        $row = RolePermission::getByPermission($this->user->role_id, $permission);
        if (!$row || !$row->allow) {
            throw new Exception('Access Denied');
        }
    }

    public function index(): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.view');

            $rows = EmailBroadcast::orderByDesc('id')->limit(100)->get([
                'id', 'admin_id', 'subject', 'status',
                'recipients_total', 'recipients_sent', 'recipients_failed',
                'started_at', 'finished_at', 'created_at',
            ]);

            return response()->json(['ok' => true, 'result' => $rows]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.view');

            $broadcast = EmailBroadcast::find($id);
            if (!$broadcast) throw new Exception('Рассылка не найдена');

            $audience = (int) DB::table('users')
                ->where('email_notify_marketing', 1)
                ->whereRaw("email <> ''")
                ->count();

            return response()->json([
                'ok'     => true,
                'result' => [
                    'broadcast'        => $broadcast,
                    'eligible_audience'=> $audience,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.create');

            $validator = Validator::make($request->all(), [
                'subject'   => 'required|string|min:1|max:255',
                'body_html' => 'required|string|min:1',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $now = time();
            $broadcast = EmailBroadcast::create([
                'admin_id'   => (int) ($this->user->id ?? 0),
                'subject'    => $request->input('subject'),
                'body_html'  => $this->svc->sanitize($request->input('body_html')),
                'body_text'  => null,
                'status'     => EmailBroadcast::STATUS_DRAFT,
                'filters'    => ['opt_in' => true],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return response()->json([
                'ok'          => true,
                'description' => 'Черновик создан',
                'result'      => $broadcast,
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.create');

            $broadcast = EmailBroadcast::find($id);
            if (!$broadcast) throw new Exception('Рассылка не найдена');
            if ($broadcast->status !== EmailBroadcast::STATUS_DRAFT) {
                throw new Exception('Можно редактировать только черновик');
            }

            $validator = Validator::make($request->all(), [
                'subject'   => 'nullable|string|min:1|max:255',
                'body_html' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            if ($request->filled('subject')) {
                $broadcast->subject = $request->input('subject');
            }
            if ($request->filled('body_html')) {
                $broadcast->body_html = $this->svc->sanitize($request->input('body_html'));
            }
            $broadcast->updated_at = time();
            $broadcast->save();

            return response()->json(['ok' => true, 'description' => 'Сохранено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function send(int $id, Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.send');

            if ($request->input('confirm') !== 'ОТПРАВИТЬ') {
                throw new Exception('Введите подтверждение «ОТПРАВИТЬ»');
            }

            $broadcast = EmailBroadcast::find($id);
            if (!$broadcast) throw new Exception('Рассылка не найдена');

            $result = $this->svc->start($broadcast);

            return response()->json([
                'ok'          => true,
                'description' => 'Поставлено в очередь: ' . $result['queued'] . ', пропущено: ' . $result['skipped'],
                'result'      => $result,
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.delete');

            $broadcast = EmailBroadcast::find($id);
            if (!$broadcast) throw new Exception('Рассылка не найдена');

            if (in_array($broadcast->status, [EmailBroadcast::STATUS_SENDING, EmailBroadcast::STATUS_QUEUED], true)) {
                throw new Exception('Нельзя удалить рассылку, которая активно отправляется');
            }

            DB::transaction(function () use ($broadcast) {
                EmailBroadcastRecipient::where('broadcast_id', $broadcast->id)->delete();
                $broadcast->delete();
            });

            return response()->json(['ok' => true, 'description' => 'Удалено']);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function preview(Request $request): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.create');

            $validator = Validator::make($request->all(), [
                'body_html' => 'required|string',
            ]);
            if ($validator->fails()) {
                throw new Exception($validator->errors()->first());
            }

            $sanitized = $this->svc->sanitize($request->input('body_html'));
            $personalised = $this->svc->personalise($sanitized, [
                'email' => 'preview@example.com',
            ]);

            return response()->json([
                'ok'     => true,
                'result' => ['html' => $personalised],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function audience(): JsonResponse
    {
        try {
            $this->canOrThrow('email_broadcasts.view');

            $eligible = (int) DB::table('users')
                ->where('email_notify_marketing', 1)
                ->whereRaw("email <> ''")
                ->count();

            $totalWithEmail = (int) DB::table('users')
                ->whereRaw("email <> ''")
                ->count();

            return response()->json([
                'ok'     => true,
                'result' => [
                    'eligible'           => $eligible,
                    'total_with_email'   => $totalWithEmail,
                    'opted_out'          => $totalWithEmail - $eligible,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }
}
