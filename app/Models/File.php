<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function document_type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id', 'id');
    }

    public function scopeFilesWithFilter($query, $filters, $name = null)
    {
        $query->when($filters['search'] ?? false, function ($query, $search) {
            $query->where('name', 'like', '%' . $search . '%');
        })->when($filters['type'] ?? false, function ($query, $type) {
            $query->where('extension', $type);
        })->when($name !== null, function ($query) use ($name) {
            $query->whereHas('document_type', function ($query) use ($name) {
                $query->where('name', $name);
            });
        }, function ($query) {
            $query->whereNull('document_type_id');
        });
    }
}
