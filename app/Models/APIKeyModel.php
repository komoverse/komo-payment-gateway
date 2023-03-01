<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class APIKeyModel extends Model
{
    use HasFactory;
    protected $table = 'tb_api_key';
    protected $fillable = [
        'api_key',
        'source'
    ];  

    static function findAPIKey($api_key) {
        $result = DB::table('tb_api_key')
                    ->where('api_key', $api_key)
                    ->first();
        return $result;
    }
}
