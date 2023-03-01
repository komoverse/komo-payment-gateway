<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Validator;

class PaypalController extends Controller
{
    public function generatePaypalLink($req) {
        $rules = [
            'price' => 'required|numeric',
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
                  "currency_code": "USD",
                  "value": "'.$req->price.'"
                }
              }
            ]
        }', true);
        $paypal = $provider->createOrder($data);

        return $paypal;
    }
}
