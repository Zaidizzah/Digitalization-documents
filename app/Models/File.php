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
        return $this->belongsTo(User::class);
    }

    public function document_type()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public static function filesWithFilter($name = null, $ext = null, $type = null){
        $inputs = [];

        $files = DB::table('files');
        if($name){
            $inputs['search'] = $name;
            $files->where('name', 'like', '%'.$name.'%');
        }
        if($ext){
            $inputs['type'] = $ext;
            $files->where('extension', $ext);
        }
        if($type){
            $document = DB::table('document_types')->where('name', $type)->first('id');
            $files->where('document_type_id', $document->id);
        }else{
            $files->where('document_type_id', null);
        }

        return $files->orderBy('created_at', 'desc')->paginate(25)->appends($inputs);
    }
}
