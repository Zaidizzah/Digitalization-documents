<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Interfaces\SearchableContent;

class TempSchema extends Model implements SearchableContent
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'schema',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
     * ------------------------------------------------------------------------
     * Implementing SearchableContent interface for search content function
     * ------------------------------------------------------------------------
     */
    public static function search(string $querySearch): array
    {
        return self::where('user_id', auth()->user()->id)->whereHas('user', function ($query) use ($querySearch) {
            $query->where('name', 'like', "%{$querySearch}%")
                ->orWhere('email', 'like', "%{$querySearch}%");
        })
            ->get()
            ->whenNotEmpty(function ($collection) {
                // Return the collection of available pages 
                return $collection->push([
                    'type' => 'Temporary Schema',
                    'title' => "Your temporary schema data has been saved and can be accessed for creating new document type or inserting new attribute schema to existing document type.",
                    'url' => route('documents.create'),
                ])->toArray();
            }, function ($collection) {
                // Return an empty array if the collection is empty
                return [];
            });
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
