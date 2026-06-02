<?php

namespace App\Http\Controllers\Api;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationApiController extends BaseApiController
{
    public function notifications(Request $request)
    {
        $user = auth()->user() ?: $request->auth_user;
        if (!$user) {
            return response()->json(['data' => []]);
        }

        $items = Notification::where('user_id', $user->username)
            ->orWhere('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function notificationRead(string $id)
    {
        $notif = Notification::find($id);
        if ($notif) {
            $notif->update(['is_read' => true]);
        }

        return response()->json(['success' => true, 'id' => $id]);
    }
}
