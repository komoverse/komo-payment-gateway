<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\PaypalController;
use App\Http\Controllers\DuitkuController;
use App\Http\Controllers\CoinPaymentsController;
use Illuminate\Support\Facades\Validator;
use App\Models\ShardTransactionModel;

class MainController extends Controller
{
    protected $KOMO_TX_ID;

    function __construct() {
        $this->KOMO_TX_ID = strtoupper(md5(uniqid()));
    }

    /* ----- API FUNCTIONS ----- */
    public function topupShard(Request $request) {
        // Validate data.
        $validator = Validator::make($request->all(), [
            'userdata' => 'required|json',
            'shard_amount' => 'required|integer',
            'currency' => 'required|string',
            'payment_channel' => 'required|in:paypal,coinpayments,duitku',
            'tx_source' => 'required|string',
        ]);

        if ($validator->fails()) {
            $response = [
                'status' => 'error',
                'message' => $validator->messages(),
            ];
            return response()->json($response, 400); // Bad Request
        }

        // Initialize userdata.
        $userdata = json_decode($request->userdata);

        // Create exchange value.
        $shard_amount = $request->shard_amount;
        $value_IDR = $shard_amount;
        $value_USD = $value_IDR * $this->getConversionRate('USD');

        // Payment Gateway
        switch ($request->payment_channel) {
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
                    'crypto_target' => $request->currency,
                    'komo_tx_id' => $this->KOMO_TX_ID,
                    'komo_username' => $userdata->komo_username,
                    'email' => $userdata->email,
                ];
                $pg_response = (new CoinPaymentsController)->createTransaction($data);
                if (!($pg_response['error'] == 'ok')) {
                    return response()->json($pg_response, 400);
                }
                $checkout_type = 'qrcode';
                $checkout_url = $pg_response['result']['qrcode_url'];
                break;

            case 'duitku':
                $pay_amount = $value_IDR;
                $data = [
                    'pay_amount' => $pay_amount,
                ];
                $pg_response = (new DuitkuController)->requestTransaction($data);
                $checkout_type = '[DUITKU PLACEHOLDER]';
                break;
            default:
                # code...
                break;
        }

        // Prepare response to return.
        $response = [
            'transaction_id' => $this->KOMO_TX_ID,
            'recipient' => $userdata->komo_username,
            'payment_for' => $request->shard_amount.' SHARD',
            'pay_amount' => $pay_amount.' '.$request->currency,
            'payment_channel' => $request->payment_channel,
            'checkout_type' => $checkout_type,
            'checkout_url' => $checkout_url ?? null,
        ];

        // Save ShardTransaction into tb_shard_tx table.
        $db_data = [
            'komo_tx_id' => $this->KOMO_TX_ID,
            'komo_username' => $userdata->komo_username,
            'description' => 'Topup '.$shard_amount.' SHARD via '.$request->payment_channel.' ('.$request->currency.')',
            'debit_credit' => 'debit',
            'amount_shard' => $shard_amount,
            'raw_komo_tx' => json_encode($response),
            'tx_status' => 'pending',
            'custom_param' => json_encode($pg_response),
            'tx_source' => $request->header('X-Api-Key'),
        ];
        ShardTransactionModel::insertData($db_data);

        // Return response.
        return response()->json($response, 200);
    }

    public function test() {
        $data = [
            'price' => 90
        ];
        return response()->json((new PaypalController)->generatePaypalLink($data), 200);
    }

    /* ----- HELPER FUNCTIONS ----- */
    public function getConversionRate($to, $from = 'IDR') {
        $price = file_get_contents("https://min-api.cryptocompare.com/data/pricemultifull?fsyms=".strtoupper($from)."&tsyms=".strtoupper($to));
        $price = json_decode($price);
        return $price->RAW->$from->$to->PRICE;
    }

}
