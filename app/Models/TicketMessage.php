<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    public $timestamps = false;
    protected $table = 'tickets_messages';
    protected $fillable = [
        'id',
        'user_id',
        'operator_id',
        'ticket_id',
        'message',
        'image',
        'is_read',
        'created_at'
    ];

    public static function getByID($id){
        return TicketMessage::where('id', $id)->first();
    }

    public static function getLastMsgByTicketID($ticket_id, $type){
        if ($type == 'support'){
            $result = TicketMessage::where('ticket_id', $ticket_id)->where('user_id', 0)->where('operator_id', '!=', 0)->orderByDesc('created_at')->limit(1)->first();
        }

        if ($type == 'user'){
            $result = TicketMessage::where('ticket_id', $ticket_id)->where('user_id', '!=', 0)->orderByDesc('created_at')->limit(1)->first();
        }

        return $result;
    }
    public static function getListByTicketID($ticket_id){
        return TicketMessage::where('ticket_id', $ticket_id)->get();
    }

    public static function getListByCreatedAt($ticket_id){
        return TicketMessage::where('is_read', 0)->where('ticket_id', $ticket_id)->where('user_id', '>', 0)->get();
    }

    public static function getTopResponderForTicket($ticket_id){
        return TicketMessage::where('ticket_id', $ticket_id)
            ->where('operator_id', '!=', 0)
            ->groupBy('operator_id')
            ->select('operator_id', DB::raw('count(*) as message_count'))
            ->orderBy('message_count', 'desc')
            ->first();
    }
}
