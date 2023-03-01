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

    static function submitCallback($req, $source) {
        $insert = DB::table('tb_pg_callback')
                    ->insert([
                        'payload' => json_encode($req),
                        'source' => $source,
                    ]);
        return $insert;
    }

    static function getFromKOMOTXID($komo_tx_id) {
        $result = DB::table('tb_shard_tx')
                    ->where('komo_tx_id', $komo_tx_id)
                    ->first();
        return $result;
    }

    static function updateShardTX($data) {
        $update = DB::table('tb_shard_tx')
                    ->where('komo_tx_id', $data->komo_tx_id)
                    ->update([
                        'tx_status' => $data->tx_status,
                    ]);
        return $update;
    }

    static function addAccountShard($komo_username, $shard) {
        $update = DB::table('tb_account')
                    ->where(DB::raw('BINARY `komo_username`'), '=', $komo_username)
                    ->increment('shard', $shard);
        return $update;
    }
}
