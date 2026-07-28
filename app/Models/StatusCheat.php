<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusCheat extends Model
{

    public $timestamps = false;
    protected $table = 'statuses';
    protected $fillable = [
        'id',
        'cid',
        'title',
        'status',
        'message_template',
        'image_path',
        'updated_at'
    ];

    public const STATUSES = [
        1 => 'Рекомендуем к использованию',
        2 => 'Не рекомендуем к использованию',
        3 => 'На обновлении',
        4 => 'На свой страх и риск',
    ];

    public static function statusLabel(int $status): string
    {
        return self::STATUSES[$status] ?? 'Не определён';
    }

    /**
     * ТЗ §10 — one canonical description of a cheat status.
     *
     * The status→class mapping used to be copy-pasted in five places with two
     * different vocabularies ('undetected' vs 'recommend'), so the same status
     * could render with a class that had no matching CSS. Everything goes
     * through this now.
     *
     * @return array{key:string,label:string,icon:string}
     */
    public static function statusMeta($status): array
    {
        $map = [
            1 => ['key' => 'recommend',     'icon' => 'check',   'lang' => 'site.section_statuses_status_1'],
            2 => ['key' => 'not-recommend', 'icon' => 'cross',   'lang' => 'site.section_statuses_status_2'],
            3 => ['key' => 'on-update',     'icon' => 'refresh', 'lang' => 'site.section_statuses_status_3'],
            4 => ['key' => 'risk',          'icon' => 'warning', 'lang' => 'site.section_statuses_status_4'],
        ];

        // Unknown / 0 has no lang key of its own — section_statuses_status_0 is
        // the filter dropdown's "Все статусы" label, not a status name.
        if (!isset($map[(int) $status])) {
            return ['key' => 'unknown', 'label' => self::statusLabel((int) $status), 'icon' => 'question'];
        }

        $meta = $map[(int) $status];

        $label = __($meta['lang']);
        if ($label === $meta['lang']) {            // missing translation — fall back
            $label = self::statusLabel((int) $status);
        }

        return ['key' => $meta['key'], 'label' => $label, 'icon' => $meta['icon']];
    }
    public static function getListByCID($cid){
        return StatusCheat::where('cid', $cid)->get();
    }
    public static function getByID($id){
        return StatusCheat::where('id', $id)->first();
    }
}
