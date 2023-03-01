<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class ShardTransactionModel extends Model
{
    use HasFactory;

    static function insertData($req) {
        $insert = DB::table('tb_shard_tx')
                    ->insert($req);
        return $insert;
    }
}
