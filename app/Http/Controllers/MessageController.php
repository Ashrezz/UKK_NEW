<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    // Show form to send message (for regular users)
    public function create()
    {
        return view('messages.create');
    }
    
    // Store message
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);
        
        Message::create([
            'from_user_id' => auth()->id(),
            'subject' => $request->subject,
            'message' => $request->message,
            'is_read' => false,
        ]);
        
        return redirect()->back()->with('success', 'Pesan berhasil dikirim ke admin!');
    }
    
    // List messages (for admin/petugas)
    public function index()
    {
        // Only admin and petugas can access
        if (!in_array(auth()->user()->role, ['admin', 'petugas'])) {
            abort(403, 'Unauthorized');
        }
        
        $messages = Message::with('sender', 'reader')
            ->orderBy('is_read', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('messages.index', compact('messages'));
    }
    
    // Mark as read
    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        
        $message->update([
            'is_read' => true,
            'read_by' => auth()->id(),
            'read_at' => now(),
        ]);
        
        return redirect()->back();
    }
    
    // Reply to message
    public function reply(Request $request, $id)
    {
        // Only admin and petugas can reply
        if (!in_array(auth()->user()->role, ['admin', 'petugas'])) {
            abort(403, 'Unauthorized');
        }
        
        $message = Message::findOrFail($id);
        
        $request->validate([
            'reply' => 'required|string|max:5000',
        ]);
        
        $message->update([
            'reply' => $request->reply,
            'replied_by' => auth()->id(),
            'replied_at' => now(),
            'is_read' => true,
            'read_by' => auth()->id(),
            'read_at' => now(),
        ]);
        
        return redirect()->back()->with('success', 'Balasan berhasil dikirim!');
    }
}
