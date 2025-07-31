<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Interfaces\SearchableContent;

class User extends Authenticatable implements SearchableContent
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
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
        $USER_PROFILE_ITEM = [
            'type' => 'Users',
            'title' => "Your user profile",
            'url' => route('users.profile'),
        ];

        return self::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->get()
            ->whenNotEmpty(function ($collection) use ($USER_PROFILE_ITEM) {
                // Check if the user is an admin or not (Empty the collection if the user is not an admin)
                if (is_role('Admin')) {
                    return $collection->map(function ($item) {
                        return [
                            'type' => 'Users',
                            'title' => "Data user - with name: '{$item->name}' and email: '{$item->email}'",
                            'url' => route('users.index'),
                        ];
                    })->push($USER_PROFILE_ITEM)->toArray();
                } else {
                    // Return the collection of available pages
                    return $USER_PROFILE_ITEM;
                }
            }, function ($collection) {
                // Return an empty array if the collection is empty
                return [];
            });
    }

    public function document_type()
    {
        return $this->hasMany(DocumentType::class, 'document_type_id', 'id');
    }

    public function file()
    {
        return $this->hasMany(File::class, 'user_id', 'id');
    }

    public function temp_schema()
    {
        return $this->hasMany(TempSchema::class, 'user_id', 'id');
    }
}
