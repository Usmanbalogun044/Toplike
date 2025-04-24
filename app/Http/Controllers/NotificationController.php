<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
/**
 * Get all notifications for the authenticated user.
 *
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
public function getUserNotifications(Request $request)
{
    $user = $request->user();
    $notifications = $user->notifications()->get(); // Fetch notifications using the relationship
    return response()->json($notifications);
}
//read
public function readnotification(Request $request){
    $user = $request->user();
    $user->unreadNotifications->markAsRead();
    return response()->json(['message' => 'Notifications marked as read']);
}

public function readnotification(Request $request,$id){
    $notification = $request->user()->notifications()->findOrFail($id);
    $notification->markAsRead();
    return response()->json(['message' => 'Notification marked as read']);
}
public function deleteNotification(Request $request, $id)
{
    $notification = $request->user()->notifications()->findOrFail($id);
    $notification->delete();
    return response()->json(['message' => 'Notification deleted successfully']);
}
