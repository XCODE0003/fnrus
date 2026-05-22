<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Review;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReviewController extends Controller
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
                'error_code' => $e->getCode(),
                'description' => $e->getMessage()
            ]);
        }
    }

    // Публичная отправка отзыва посетителем сайта — попадает на модерацию (status = 0).
    public function submit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'author' => 'required|string|min:2|max:255',
                'text' => 'required|string|min:10|max:2000',
                'link' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->errors()->first(), 1);
            }

            Review::create([
                'author' => trim($request->author),
                'author_link' => '',
                'text' => trim($request->text),
                'avatar' => '/assets/img/default_avatar.svg',
                'link' => $request->link ?? '',
                'created_at' => time(),
                'status' => 0,
            ]);

            return response()->json([
                'ok' => true,
                'description' => 'Отзыв отправлен на модерацию',
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function all()
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'reviews.all')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $reviews = Review::orderBy('id', 'desc')->get();

            $all = [];

            foreach ($reviews as $p) {

                $all[] = [
                    'id' => $p->id,
                    'avatar' => '<img src="'.$p->avatar.'" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">',
                    'author' => '<span style="max-width: 200px;display: block;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">'.$p->author.'</span>',
                    'text' => '<span style="max-width: 308px;display: block;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">'.$p->text.'</span>',
                    'link' => '<a href="'.$p->link.'" target="_blank" style="max-width: 200px;display: block;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">'.$p->link.'</a>',
                    'block_edit' => '<a data-id="'.$p->id.'" href="javascript:;" title="Редактировать" data-toggle="modal" data-target="#changeReview"><i class="far fa-edit fa-xl"></i></a>',
                    'block_delete' => '<a href="javascript:;" title="Удалить" class="text-danger" onclick="deleteReview('.$p->id.');return false;"><i class="far fa-trash fa-xl"></i></a>',
                ];
            }

            return datatables($all)
                ->rawColumns(['avatar', 'author', 'text', 'link', 'block_edit', 'block_delete'])
                ->make(true);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()]);
        }
    }

    public function fullinfo($id){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'reviews.info')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $review = Review::find($id);

            if (!$review) {
                throw new Exception('Отзыв не найден.', 1);
            }

            return response()->json([
                'ok' => true,
                'result' => $review,
            ]);

        } catch (Exception $e){
            return response()->json([
                'ok' => false,
                'description' => $e->getMessage()
            ]);
        }
    }

    public function create(Request $request){
        try {

            $access = RolePermission::getByPermission($this->user->role_id, 'reviews.add')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $hasFile = $request->hasFile('avatar_file');

            $validator = Validator::make($request->all(), [
                'author' => 'required|string|min:1|max:255',
                'author_link' => 'nullable|string|max:255',
                'text' => 'required|string|min:1',
                'avatar' => $hasFile ? 'nullable|string|max:255' : 'required|string|max:255',
                'avatar_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
                'link' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $avatarPath = $request->avatar;

            if ($hasFile) {
                $file = $request->file('avatar_file');
                $filename = 'review_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/img/reviews'), $filename);
                $avatarPath = '/assets/img/reviews/' . $filename;
            }

            $sql = [
                'author' => $request->author,
                'author_link' => $request->author_link ?? '',
                'text' => $request->text,
                'avatar' => $avatarPath,
                'link' => $request->link ?? '',
                'created_at' => time(),
            ];

            $review = Review::create($sql);

            if ($review) {
                return response()->json([
                    'ok' => true,
                    'description' => 'Отзыв создан',
                    'result' => [
                        'id' => $review->id
                    ]
                ]);
            }

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function update($id, Request $request){
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'reviews.edit')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $hasFile = $request->hasFile('avatar_file');

            $validator = Validator::make($request->all(), [
                'author' => 'required|string|min:1|max:255',
                'author_link' => 'nullable|string|max:255',
                'text' => 'required|string|min:1',
                'avatar' => $hasFile ? 'nullable|string|max:255' : 'required|string|max:255',
                'avatar_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:5120',
                'link' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    throw new Exception($message, 1);
                }
            }

            $review = Review::find($id);

            if (!$review) {
                throw new Exception('Отзыв не найден.', 1);
            }

            $avatarPath = $request->avatar;

            if ($hasFile) {
                $file = $request->file('avatar_file');
                $filename = 'review_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/img/reviews'), $filename);
                $avatarPath = '/assets/img/reviews/' . $filename;
            }

            $review->author = $request->author;
            $review->author_link = $request->author_link ?? '';
            $review->text = $request->text;
            $review->avatar = $avatarPath;
            $review->link = $request->link ?? '';
            $review->save();

            return response()->json([
                'ok' => true,
                'description' => 'Отзыв обновлён',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

    public function delete($id)
    {
        try {
            $access = RolePermission::getByPermission($this->user->role_id, 'reviews.delete')->allow;
            if (!$access) {throw new Exception('Access Denied');}

            $review = Review::find($id);

            if (!$review) {
                throw new Exception('Отзыв не найден.', 1);
            }

            $review->delete();

            return response()->json([
                'ok' => true,
                'description' => 'Отзыв удалён',
            ]);

        } catch (Exception $e) {
            return response()->json(['ok' => false, 'description' => $e->getMessage()], 200);
        }
    }

}
