<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\PaypalController;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller
{
    protected $KOMO_TX_ID;

    function __construct() {
        $this->KOMO_TX_ID = strtoupper(md5(uniqid()));
    }

    public function test() {
        $data = [
            'price' => 90
        ];
        return response()->json((new PaypalController)->generatePaypalLink($data), 200);
    }


    public function getConversionRate($to, $from = 'IDR') {
        $price = file_get_contents("https://min-api.cryptocompare.com/data/pricemultifull?fsyms=".strtoupper($from)."&tsyms=".strtoupper($to));
        $price = json_decode($price);
        return $price->RAW->$from->$to->PRICE;
    }

    public function topupShard(Request $req) {
        $validator = Validator::make($req->all(), [
            'komo_username' => 'required|string|max:100',
            'shard_amount' => 'required|integer',
            'currency' => 'required|string',
            'payment_channel' => 'required|in:paypal,coinpayments',
            'tx_source' => 'required|string',
        ]);
        if ($validator->fails()) {
            $response = [
                'status' => 'error',
                'message' => $validator->messages(),
            ];
            return response()->json($response, 400);
        }

        // create exchange value
        $shard_amount = $req->shard_amount;
        $value_IDR = $shard_amount;
        $value_USD = $value_IDR * $this->getConversionRate('USD');

        // Payment Gateway
        switch ($req->payment_channel) {
            case 'paypal':
                $pay_amount = $value_USD;
                $data = [
                    'pay_amount' => $pay_amount,
                    'currency' => 'USD',
                ];
                $pg_response = (new PaypalController)->generatePaypalLink($data);
                if (!($pg_response['status'] == 'CREATED')) {
                    return response()->json($pg_response, 400);
                }
                $checkout_url = $pg_response['links'][1]['href'];
                $checkout_type = 'redirect';
                break;

            case 'coinpayments':
                $pay_amount = $value_USD + ($value_USD * (0.5 / 100));
                $data = [
                    'USD_amount' => $pay_amount,
                    'crypto_target' => $req->currency,
                    'komo_tx_id' => $this->KOMO_TX_ID,
                    'komo_username' => 'aa',
                    'email' => 'nikko@komodolegends.io',
                ];
                $pg_response = (new CoinpaymentsController)->createTransaction($data);
                if (!($pg_response['error'] == 'ok')) {
                    return response()->json($pg_response, 400);
                }
                $checkout_type = 'qrcode';
                $checkout_url = $pg_response['result']['qrcode_url'];
                break;
            
            default:
                # code...
                break;
        }

        // Prepare response
        $response = [
            'transaction_id' => $this->KOMO_TX_ID,
            'recipient' => $req->komo_username,
            'payment_for' => $req->shard_amount.' SHARD',
            'pay_amount' => $pay_amount.' '.$req->currency,
            'payment_channel' => $req->payment_channel,
            'checkout_type' => $checkout_type,
            'checkout_url' => $checkout_url ?? null,
        ];
  
        // save tb_shard_tx
        $db_data = [
            'komo_tx_id' => $this->KOMO_TX_ID,
            'komo_username' => $req->komo_username,
            'description' => 'Topup '.$shard_amount.' SHARD via '.$req->payment_channel.' ('.$req->currency.')',
            'debit_credit' => 'debit',
            'amount_shard' => $shard_amount,
            'raw_komo_tx' => json_encode($response),
            'tx_status' => 'pending',
            'custom_param' => json_encode($pg_response),
            'tx_source' => $req->tx_source,
        ];
        // PG_Model::insertData($db_data);
  
        // Send response
        return response()->json($response, 200);
    }
}
