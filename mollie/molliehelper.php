<?php
/* *
 *	Bixie ZOOcart - Mollie
 *  molliehelper.php
 *	Created on 8-4-2015 23:53
 *  
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */
 
// No direct access
defined('_JEXEC') or die;

class Molliehelper {
	/**
	 * @var string
	 */
	protected $api_key = '';
	/**
	 * @var \Joomla\Registry\Registry
	 */
	protected $params;
	/**
	 * @var Mollie_API_Client
	 */
	protected $mollie;

	/**
	 * @param \Joomla\Registry\Registry $params
	 */
	public function __construct ($params) {

		require_once __DIR__ . "/Mollie/API/Autoloader.php";
		$this->params = $params;
		$this->api_key = $params->get('test', 0) ? $params->get('test_api', '') : $params->get('live_api', '');

		$this->mollie = new Mollie_API_Client;
		$this->mollie->setApiKey($this->api_key);

	}

	/**
	 * Haal de lijst van beschikbare betaalmethodes
	 * @return Mollie_API_Object_List|Mollie_API_Object_Method[]
	 */
	public function getMethods () {
		return $this->mollie->methods->all();
	}

	/**
	 * Haal de lijst van beschikbare banken
	 * @return Mollie_API_Object_Issuer[]|Mollie_API_Object_List
	 */
	public function getBanks () {
		return $this->mollie->issuers->all();
	}

	/**
	 * Zet een betaling klaar bij de bank en maak de betalings URL beschikbaar
	 * @param      $method
	 * @param      $order_id
	 * @param      $amount
	 * @param      $description
	 * @param      $return_url
	 * @param null $issuer
	 * @return Mollie_API_Object_Payment
	 * @throws Mollie_API_Exception
	 */
	public function createPayment ($method, $order_id, $amount, $description, $return_url, $issuer = null) {
		if (empty($order_id)) {
			throw new Mollie_API_Exception("No order_id given.");
		}
		return $this->mollie->payments->create(array(
			"amount"       => $amount,
			"method"       => $method,
			"description"  => $description,
			"redirectUrl"  => $return_url,
			"metadata"     => array(
				"order_id" => $order_id
			),
			"issuer"       => $issuer
		));
	}

	/**
	 * @param $transaction_id
	 * @return Mollie_API_Object_Payment
	 * @throws Mollie_API_Exception
	 */
	public function checkPayment ($transaction_id) {

		return $this->mollie->payments->get($transaction_id);

	}



}