<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\SearchableContent;

class DocumentType extends Model implements SearchableContent
{
    use HasFactory;

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'long_name',
        'icon',
        'is_active',
        'schema_table',
        'table_name',
        'schema_form'
    ];

    /*
     * ------------------------------------------------------------------------
     * Implementing SearchableContent interface for search content function
     * ------------------------------------------------------------------------
     */
    public static function search(string $query): array
    {
        return self::where('is_active', 1)->where('name', 'like', "%{$query}%")
            ->orWhere('long_name', 'like', "%{$query}%")
            ->get()
            ->whenNotEmpty(function ($collection) {
                return $collection->flatMap(function ($item) {
                    $PAGES = [
                        [
                            'type' => 'Document Types',
                            'title' => "Browse {$item->name} type document data",
                            'url' => route('documents.browse', $item->name),
                        ],
                        [
                            'type' => 'Document Types',
                            'title' => "Structure of {$item->name} type document",
                            'url' => route('documents.structure', $item->name),
                        ],
                        [
                            'type' => 'Files',
                            'title' => "Files of {$item->name} type document",
                            'url' => route('documents.files.index', $item->name),
                        ],
                        [
                            'type' => 'Document Types',
                            'title' => "Insert new {$item->name} type document data",
                            'url' => route('documents.create', $item->name),
                        ],
                        [
                            'type' => 'Files',
                            'title' => "Settings of {$item->name} type document",
                            'url' => route('documents.settings', $item->name),
                        ]
                    ];

                    return $PAGES;
                })->toArray();
            }, function ($collection) {
                // Returning empty array because the collection is empty
                return [];
            });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function file()
    {
        return $this->hasMany(File::class, 'document_type_id', 'id');
    }
}
