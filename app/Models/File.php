<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\SearchableContent;

class File extends Model implements SearchableContent
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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['document_type'];

    /*
     * ------------------------------------------------------------------------
     * Implementing SearchableContent interface for search content function
     * ------------------------------------------------------------------------
     */
    public static function search(string $query): array
    {
        return self::whereRaw('CONCAT(name, \'.\', extension) like ?', ["%{$query}%"])
            ->get()
            ->whenNotEmpty(function ($collection) {
                return $collection->flatMap(function ($item) {
                    $PAGES = [
                        [
                            'type' => 'Files',
                            'title' => "Data file - with name: '{$item->name}.{$item->extension}'",
                            'url' => $item->document_type_id !== NULL ? route('documents.files.index', $item->document_type->name)  : route("documents.files.root.index"),
                        ],
                        [
                            'type' => 'Files',
                            'title' => "Preview file - with name: '{$item->name}.{$item->extension}'",
                            'url' => $item->document_type_id !== NULL ? route('documents.files.preview', [$item->document_type->name, 'file' => $item->encrypted_name])  : route("documents.files.root.preview", ['file' => $item->encrypted_name]),
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
