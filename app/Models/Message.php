<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'from_user_id',
        'subject',
        'message',
        'is_read',
        'read_by',
        'read_at',
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
    
    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
    
    public function reader()
    {
        return $this->belongsTo(User::class, 'read_by');
    }
}
