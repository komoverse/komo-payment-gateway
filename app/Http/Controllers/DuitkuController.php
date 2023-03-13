<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DuitkuController extends Controller
{
    private $merchant_code;
    private $api_key;

    public function __construct(){
        if (config('duitku.mode') == 'sandbox') {
            $this->merchant_code = config('duitku.sandbox_merchant_code');
            $this->api_key = config('duitku.sandbox_api_key');
            $this->get_payment_method_url = 'https://sandbox.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod';
            $this->request_transaction_url = 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';
        }

        if (config('duitku.mode') == 'live'){
            $this->merchant_code = config('duitku.live_merchant_code');
            $this->api_key = config('duitku.live_api_key');
            $this->get_payment_method_url = 'https://passport.duitku.com/webapi/api/merchant/paymentmethod/getpaymentmethod';
            $this->request_transaction_url = 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry';
        }

    }

    /* ----- API FUNCTIONS ----- */
    public function requestTransaction($data){
        // Request HTTP Transaksi
        // https://docs.duitku.com/api/id/#request-http-transaksi

        $merchantCode = $this->merchant_code; // dari duitku
        $apiKey = $this->api_key; // dari duitku
        $paymentAmount = $data['pay_amount'];
        $paymentMethod = 'VC'; // VC = Credit Card
        $merchantOrderId = time() . ''; // dari merchant, unik
        $productDetails = 'Tes pembayaran menggunakan Duitku';
        $email = 'test@test.com'; // email pelanggan anda
        $phoneNumber = '08123456789'; // nomor telepon pelanggan anda (opsional)
        $additionalParam = ''; // opsional
        $merchantUserInfo = ''; // opsional
        $customerVaName = 'John Doe'; // tampilan nama pada tampilan konfirmasi bank
        $callbackUrl = 'http://example.com/callback'; // url untuk callback
        $returnUrl = 'http://example.com/return'; // url untuk redirect
        $expiryPeriod = 10; // atur waktu kadaluarsa dalam hitungan menit
        $signature = md5($merchantCode . $merchantOrderId . $paymentAmount . $apiKey);


        // Customer Detail
        $firstName = "John";
        $lastName = "Doe";

        // Address
        $alamat = "Jl. Kembangan Raya";
        $city = "Jakarta";
        $postalCode = "11530";
        $countryCode = "ID";

        $address = array(
            'firstName' => $firstName,
            'lastName' => $lastName,
            'address' => $alamat,
            'city' => $city,
            'postalCode' => $postalCode,
            'phone' => $phoneNumber,
            'countryCode' => $countryCode
        );

        $customerDetail = array(
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'billingAddress' => $address,
            'shippingAddress' => $address
        );

        $shards = array(
            'name' => 'SHARD(s)',
            'price' => 15000,
            'quantity' => $data['pay_amount']);

        $itemDetails = array(
            $shards
        );

        /*Khusus untuk metode pembayaran OL dan SL
        $accountLink = array (
            'credentialCode' => '7cXXXXX-XXXX-XXXX-9XXX-944XXXXXXX8',
            'ovo' => array (
                'paymentDetails' => array (
                    0 => array (
                        'paymentType' => 'CASH',
                        'amount' => 40000,
                    ),
                ),
            ),
            'shopee' => array (
                'useCoin' => false,
                'promoId' => '',
            ),
        );*/

        /*Khusus untuk metode pembayaran Kartu Kredit
        $creditCardDetail = array (
            'acquirer' => '014',
            'binWhitelist' => array (
                '014',
                '400000'
            )
        );*/

        $params = array(
            'merchantCode' => $merchantCode,
            'paymentAmount' => $paymentAmount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $productDetails,
            'additionalParam' => $additionalParam,
            'merchantUserInfo' => $merchantUserInfo,
            'customerVaName' => $customerVaName,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            //'accountLink' => $accountLink,
            //'creditCardDetail' => $creditCardDetail,
            'itemDetails' => $itemDetails,
            'customerDetail' => $customerDetail,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'signature' => $signature,
            'expiryPeriod' => $expiryPeriod
        );

        // Send a cURL request.
        $params_string = json_encode($params);
        $url = $this->request_transaction_url;
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

        if($httpCode == 200)
        {
            $result = json_decode($request, true);
            dd($result, "This works, but sandbox doesn't include result[vaNumber] array. Try the live version.");
            //header('location: '. $result['paymentUrl']);
            echo "paymentUrl :". $result['paymentUrl'] . "<br />";
            echo "merchantCode :". $result['merchantCode'] . "<br />";
            echo "reference :". $result['reference'] . "<br />";
            echo "vaNumber :". $result['vaNumber'] . "<br />";
            echo "amount :". $result['amount'] . "<br />";
            echo "statusCode :". $result['statusCode'] . "<br />";
            echo "statusMessage :". $result['statusMessage'] . "<br />";
        }
        else
        {
            $request = json_decode($request);
            $error_message = "Server Error " . $httpCode ." ". $request->Message;
            echo $error_message;
        }
    }

    public function getPaymentMethod($data){
        // Request HTTP Get Payment Method
        // https://docs.duitku.com/api/id/#request-http-get-payment-method

        // Initialize variables.
        $merchantCode = $this->merchant_code;
        $apiKey = $this->api_key;
        $datetime = date('Y-m-d H:i:s');
        $paymentAmount = $data['pay_amount'];
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
