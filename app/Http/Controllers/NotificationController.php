<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,petugas');
    }
    
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        
        // Mark as read by current admin/petugas
        $notification->update([
            'is_read' => true,
            'read_by' => auth()->id(),
            'read_at' => now(),
        ]);
        
        // Redirect to jadwal with notification ID to highlight the booking
        return redirect()->route('peminjaman.jadwal', ['notif' => $notification->peminjaman_id])
            ->with('notif_booking_id', $notification->peminjaman_id);
    }
}
