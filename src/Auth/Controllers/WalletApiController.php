<?php

namespace Vebto\Auth\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


class WalletApiController{

	private $api_url;	
	private $merchant_url;
	private $merchant_key;

	public function __construct() {
            
		$this->api_url = env('WALLET_HOST');		
		$this->mercahnt_url = env('APP_URL');
		$this->merchant_key = env('WALLET_SECRET');		

	}

	/**
	 * Call to Tain API for Wallet
	 *
	 * @param string $type GET, POST, ...
	 * @param string $url Enpoint
	 * @param array $params
	 * @return json Request params
	 */
	public function callWallet($params = [], $url='', $type='POST') {
		$client = new Client();

		$request_options = [];
		$request_options['headers'] = [
			'Authorization' => 'Bearer '. $this->merchant_key,
			'Operator-Name' => $this->mercahnt_url,
		];

		if(!empty($params)){
			$request_options['json'] = $params;
		}

		try {
			$res = $client->request($type, $this->api_url.'/api/'.$url, $request_options);
			$body = $res->getBody();
		} catch (RequestException $e) {
			if ($e->hasResponse()) {
				$body = $e->getResponse()->getBody();
			}			
		}

		return json_decode($body);

	}

	
	/**
	 * Activate Voucher
	 *
	 * @param array ['key'=>$voucher_code, 'user'=>$user_id]
	 * @return json
	 */
	public function voucherActivate($params=[]) {
            
		return $this->callWallet($params, 'activate');
	}
	/**
	 * Get vouchers prices from wallet
	 *
	 * @return json
	 */
	public function getVoucherPrices($params=[]) {
            
		return $this->callWallet($params, 'get_price');
	}

}
