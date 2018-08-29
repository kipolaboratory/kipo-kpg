<?php

namespace kipolaboratory\KipoPay;

/**
 * Class KipoPay
 * @package kipolaboratory\KipoKpg
 */
class KipoKPG
{
    public $merchant_key = '';

    private $_headers = [
        'Accept:application/json',
        'IP:127.0.0.1',
        'OS:web',
        'SC:false',
        'SK:.',
    ];

    private $_post_data = [
        'Command' => [
            'Sign' => '',
        ],
        'OrderAt' => '',
        'OrderID' => '100000',
        'Profile' => [
            'HID' => '+989901001001',
            'SID' => '00000000-0000-0000-0000-000000000000',
        ],
        'Session' => [
            '' => ''
        ],
        'Version' => [
            'AID' => 'kipo1-alpha',
        ],
        'RawData' => [
        ]
    ];

    public $request_url = 'https://kpg.kipopay.com:8091/V1.0/processors/json/';

    /**
     * KipoKPG constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        /**
         * Start session if doesn't start
         */
        if(!session_id())
            session_start();

        /**
         * Check merchant_key
         */
        if(isset($config['merchant_key']) AND !empty($config['merchant_key']))
            $this->merchant_key = $config['merchant_key'];
    }


    public function KPGInitiate($amount, $callback_url)
    {
        $curl = curl_init();

        /**
         * Set headers
         */
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $this->_headers
        ]);

        /**
         * Set custom options
         */
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->request_url,
            CURLOPT_POST => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_USERAGENT => 'kipopay-kpg-agent',
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $post_data = $this->_post_data;
        $post_data['Command']['Sign'] = 'KPG@KPG/Initiate';
        $post_data['OrderAt'] = date("Ymdhis");
        $post_data['RawData'] = [
            'MerchantKy' => $this->merchant_key,
            'Amount' => $amount,
            'BackwardUrl' => $callback_url
        ];

        /**
         * Set data
         */
        curl_setopt_array($curl, [
            CURLOPT_POSTFIELDS => json_encode($post_data)
        ]);

        /**
         * Send curl request to server
         */
        $response = curl_exec($curl);

        /**
         * Check if there is error
         */
        if (curl_error($curl)) {
            $error_message = curl_error($curl);
        }

        if (empty($error_message)) {
            $response = json_decode($response);
            if ($response->Outcome == "0000") {
                $shopping_key = $response->RawData->ShoppingKy;
                return ['status' => true, 'sk' => $shopping_key];
            } else {
                return ['status' => false, 'message' => -1];

            }
        } else {
            if ($curl->errorCode == 28)
                return ['status' => false, 'message' => -2];

            return ['status' => false, 'message' => -3];
        }
    }


    public static function killSessions()
    {
        unset($_SESSION['payment']);
        unset($_SESSION['iap']);
    }
}