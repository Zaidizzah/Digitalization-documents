<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\SearchableContent;

class UserGuides extends Model implements SearchableContent
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

    /*
     * ------------------------------------------------------------------------
     * Implementing SearchableContent interface for search content function
     * ------------------------------------------------------------------------
     */
    public static function search(string $query): array
    {
        return self::where('title', 'like', "%{$query}%")
            ->get()
            ->whenNotEmpty(function ($collection) {
                return $collection->flatMap(function ($item) {
                    $PAGES = [
                        [
                            'type' => 'User Guides',
                            'title' => "User guide - with title: '{$item->title}'",
                            'url' => route('userguides.show.dynamic', $item->path),
                        ],
                    ];

                    return $PAGES;
                })->toArray();
            }, function () {
                return [];
            });
    }

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
