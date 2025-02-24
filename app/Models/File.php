<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type_id',
        'path',
        'name',
        'encrypted_name',
        'size',
        'type',
        'extension',
    ];

    protected $with = ['document_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function document_type()
    {
        return $this->belongsTo(DocumentType::class);
    }
}
