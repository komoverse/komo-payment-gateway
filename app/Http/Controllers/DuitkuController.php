<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DuitkuController extends Controller
{
    private $merchant_code;
    private $api_key;

    public function __construct(){
        if (env('DUITKU_MODE') == 'development'){
            $this->merchant_code = env('DUITKU_SANDBOX_MERCHANT_CODE', null);
            $this->api_key = env('DUITKU_SANDBOX_API_KEY', null);
            $this->get_payment_method_url = 'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod';
        }

        if (env('DUITKU_MODE') == 'production'){
            $this->merchant_code = env('DUITKU_PRODUCTION_MERCHANT_CODE', null);
            $this->api_key = env('DUITKU_PRODUCTION_API_KEY', null);
            $this->get_payment_method_url = 'https://passport.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod';
        }
    }

    /* ----- API FUNCTIONS ----- */
    public function getPaymentMethod($data){
        // Request HTTP Get Payment Method
        // https://docs.duitku.com/api/id/#request-http-get-payment-method

        // Initialize variables.
        $merchantCode = $this->merchant_code;
        $apiKey = $this->api_key;
        $datetime = date('Y-m-d H:i:s');
        $paymentAmount = $data['paymentAmount'];
        $signature = hash('sha256',$merchantCode . $paymentAmount . $datetime . $apiKey);

        $params = array(
            'merchantcode' => $merchantCode,
            'amount' => $paymentAmount,
            'datetime' => $datetime,
            'signature' => $signature
        );

        // Send a cURL request.
        $params_string = json_encode($params);
        $url = $this->get_payment_method_url;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($params_string))
        );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // Execute post.
        $request = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200)
        {
            // Prints out a json_decoded result.
            $results = json_decode($request, true);
            print_r($results, false);
        }
        else{
            $request = json_decode($request);
            $error_message = "Server Error " . $httpCode ." ". $request->Message;
            echo $error_message;
        }

    }
}
