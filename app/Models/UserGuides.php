<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\SearchableContent;

class UserGuides extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type_id',
        'parent_id',
        'title',
        'slug',
        'content',
        'is_active',
    ];

    protected $casts = [
        'document_type_id' => 'integer',
        'parent_id' => 'integer',
        'is_active' => 'integer',
        'title' => 'string',
        'slug' => 'string',
        'content' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // public function search(string $query): array // Implementing SearchableContent interface to search user guides content
    // {
    //     return [];
    // }

    public function document_type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->with('children');
    }
}
