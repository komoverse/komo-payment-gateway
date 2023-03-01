<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Validator;
use App\Models\ShardTransactionModel;

class PaypalController extends Controller
{
    public function generatePaypalLink($req) {
        $rules = [
            'currency' => 'required|string',
            'pay_amount' => 'required|numeric',
        ];
        $validator = Validator::make($req, $rules);
        if ($validator->fails()) {
            return $validator->errors();
        } else {
            $req = (object) $req;
        }


        $provider = new PayPalClient;
        $provider->getAccessToken();

        $data = json_decode('{
            "intent": "CAPTURE",
            "purchase_units": [
              {
                "amount": {
                  "currency_code": "'.$req->currency.'",
                  "value": "'.round($req->pay_amount, 2, PHP_ROUND_HALF_UP).'"
                }
              }
            ]
        }', true);
        $paypal = $provider->createOrder($data);

        return $paypal;
    }

    public function callback(Request $req) {
        if (ShardTransactionModel::submitCallback($req->post(), 'paypal')) {
            return response()->json('Callback received '.date('Y-m-d H:i:s'), 200);
        }
    }
}
