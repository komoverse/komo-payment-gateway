<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class CoinpaymentsController extends Controller
{
    protected $api_public_key;
    protected $api_private_key;
    protected $api_url;

    public function __construct() 
    {
        $this->api_public_key = config('coinpayments.api_public_key');
        $this->api_private_key = config('coinpayments.api_private_key');
        $this->api_url = "https://www.coinpayments.net/api.php";
    }

    function CoinPaymentAPI($cmd, $req = array()) {
        // Fill these in from your API Keys page
        $public_key = $this->api_public_key;
        $private_key = $this->api_private_key;

        // Set the API command and required fields
        $req['version'] = 1;
        $req['cmd'] = $cmd;
        $req['key'] = $public_key;
        $req['format'] = 'json'; //supported values are json and xml

        // Generate the query string
        $post_data = http_build_query($req, '', '&');

        // Calculate the HMAC signature on the POST data
        $hmac = hash_hmac('sha512', $post_data, $private_key);

        // Create cURL handle and initialize (if needed)
        static $ch = NULL;
        if ($ch === NULL) {
            $ch = curl_init($this->api_url);
            curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('HMAC: '.$hmac));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        // Execute the call and close cURL handle
        $data = curl_exec($ch);
        // Parse and return data if successful.
        if ($data !== FALSE) {
            if (PHP_INT_SIZE < 8 && version_compare(PHP_VERSION, '5.4.0') >= 0) {
                // We are on 32-bit PHP, so use the bigint as string option. If you are using any API calls with Satoshis it is highly NOT recommended to use 32-bit PHP
                $dec = json_decode($data, TRUE, 512, JSON_BIGINT_AS_STRING);
            } else {
                $dec = json_decode($data, TRUE);
            }
            if ($dec !== NULL && count($dec)) {
                return $dec;
            } else {
                // If you are using PHP 5.5.0 or higher you can use json_last_error_msg() for a better error message
                return array('error' => 'Unable to parse JSON result ('.json_last_error().')');
            }
        } else {
            return array('error' => 'cURL error: '.curl_error($ch));
        }
    }

    function getRates() {
        print_r($this->CoinPaymentAPI('rates'));
    }

    function createTransaction($req) {
        $rules = [
            'USD_amount' => 'required|numeric',
            'crypto_target' => 'required|string|max:6',
            'komo_username' => 'required|string',
            'email' => 'required|email',
            'komo_tx_id' => 'required|string|size:32', 
        ];
        $validator = Validator::make($req, $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            $req = (object) $req;
        }

        $cp_request['cmd'] = 'create_transaction';
        $cp_request['amount'] = $req->USD_amount; // USD
        $cp_request['currency1'] = 'USD';
        $cp_request['currency2'] = $req->crypto_target;
        $cp_request['buyer_email'] = $req->email;
        $cp_request['buyer_name'] = $req->komo_username;
        $cp_request['invoice'] = $req->komo_tx_id;

        $coinpaymentdata = $this->CoinPaymentAPI('create_transaction', $cp_request);
        return $coinpaymentdata;
    }
}
