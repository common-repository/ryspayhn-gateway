<?php

/**
 * Copyright (c) 2020.
 * File: ryspayhn_request.php
 * Last Modified: 12/1/20 16:59
 * Jesus Nuñez
 */
class ryspayhn_request
{
	protected $token;
	protected $card;
	protected $cardType;
	protected $card_exp;
	protected $card_cvc;
	protected $name;
	protected $lastName;
	protected $amount;
	protected $order;
	
	public function __construct($token, $card, $cardType, $card_exp, $card_cvc, $name, $lastName, $amount, $order)
	{
		$this->cardType = $cardType;
		$this->card = $card;
		$this->card_cvc = $card_cvc;
		$this->card_exp = $card_exp;
		$this->token = $token;
		$this->amount = $amount;
		$this->name = $name;
		$this->lastName = $lastName;
		$this->order = $order;
	}
	
	private function get_user_token()
	{
		$payment_gateway_id = 'ryspayhn';
		$payment_gateways = WC_Payment_Gateways::instance();
		$payment_gateway = $payment_gateways->payment_gateways()[$payment_gateway_id];
		return $payment_gateway->user_tokenryspayhn;
	}
	
	private function get_user_license()
	{
		$payment_gateway_id = 'ryspayhn';
		$payment_gateway = WC_Payment_Gateways::instance();
		$payment_gateway = $payment_gateway->payment_gateways()[$payment_gateway_id];
		return $payment_gateway->ryspayhn_license;
	}
	
	private function get_paygate_url()
	{
		$payment_gateway_id = 'ryspayhn';
		$payment_gateway = WC_Payment_Gateways::instance();
		$payment_gateway = $payment_gateway->payment_gateways()[$payment_gateway_id];
		if ($payment_gateway->ryspaynh_sandbox === 'yes') {
			return 'https://stage.paygatehn.com';
		} else {
			return 'https://api.paygatehn.com';
		}
	}
	
	public function request_post()
	{
		$array_with_parameters = "{\"safeIdentifier\": \"" . (string)$this->card . "\" ,\"firstName\": \"" . (string)$this->name . "\" ,\"lastName\": \"" . (string)$this->lastName . "\" ,\"validThru\":\"" . (string)$this->card_exp . "\" , \"amount\": " . $this->amount . ", \"description\": \"" . (string)$this->order . "\" ,\"cvv\": \"" . (string)$this->card_cvc . "\" ,\"cardType\": \"" . (string)$this->cardType . "\"}";
		$url = $this->get_paygate_url() . '/payments';
		$response = wp_remote_post($url, [
			'headers' => ['Content-Type' => 'application/json; charset=utf-8', "Authorization" => "Bearer " . $this->get_user_token()],
			'body' => $array_with_parameters,
			'method' => 'POST',
			'data_format' => 'body',
		]);
		$body = wp_remote_retrieve_body($response);
		$code = wp_remote_retrieve_response_code($response);
		return $response = (object)['code' => $code, 'response' => json_decode($body)];
	}
	
	public function get_user_transaccions()
	{
		$token = $this->get_user_token();
		if (empty($token)) {
			return false;
		} else {
			$args = [
				'headers' => [
					'Authorization' => 'Bearer ' . $token
				]
			];
			$url = $this->get_paygate_url() . '/payments';
			error_log(print_r($url, true));
			$response = wp_remote_get($url, $args);
			$body = wp_remote_retrieve_body($response);
			return json_encode($body, true);
		}
	}
	
	public function license_verification()
	{
		$body = [
			'license' => $this->get_user_license(),
			'server' => $_SERVER['HTTP_HOST'],
			'date' => get_option('ryspayhnDate')
		];
		$args = [
			'body' => $body,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => [],
			'cookies' => []
		];
		$response = wp_remote_post("http://licencias.ryspayhn.com/sistema/modules/include/verify.php", $args);
		
		$response = wp_remote_retrieve_body($response);
		
		return json_encode($response, true);
	}
}

