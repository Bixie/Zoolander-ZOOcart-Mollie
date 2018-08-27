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
namespace Bixie\ZoocartMollie;

// No direct access
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\PaymentMethod;

defined('_JEXEC') or die;

class Helper {
	/**
	 * @var string
	 */
	protected $api_key = '';
	/**
	 * @var \Joomla\Registry\Registry
	 */
	protected $params;
	/**
	 * @var MollieApiClient
	 */
	protected $mollie;

	/**
	 * @param \Joomla\Registry\Registry $params
	 */
	public function __construct ($params) {

		$this->params = $params;
		$this->api_key = $params->get('test', 0) ? $params->get('test_api', '') : $params->get('live_api', '');

        try {
            $this->mollie = new MollieApiClient();
            $this->mollie->setApiKey($this->api_key);
        } catch (ApiException $e) {
            //raise joomla error?
        }

    }

	/**
	 * Haal de lijst van beschikbare betaalmethodes
	 * @return \Mollie\Api\Resources\Method[]
     * @throws ApiException
	 */
	public function getMethods () {
        $methods = $this->mollie->methods->all();
        return $methods->getArrayCopy();
	}

	/**
	 * Haal de lijst van beschikbare banken
	 * @return \Mollie\Api\Resources\Issuer[]
     * @throws ApiException
	 */
	public function getBanks () {
        $method = $this->mollie->methods->get(PaymentMethod::IDEAL, ['include' => 'issuers']);
        return $method->issuers()->getArrayCopy();
    }

	/**
	 * Zet een betaling klaar bij de bank en maak de betalings URL beschikbaar
	 * @param      $method
	 * @param      $order_id
	 * @param      $amount
	 * @param      $description
	 * @param      $return_url
     * @param      $webhook_url
	 * @param null $issuer
	 * @return \Mollie\Api\Resources\Payment
	 * @throws ApiException
	 */
	public function createPayment ($method, $order_id, $amount, $description, $return_url, $webhook_url, $issuer = null) {
		if (empty($order_id)) {
			throw new ApiException("No order_id given.");
		}
		return $this->mollie->payments->create(array(
			"amount"       => [
			    "currency" => "EUR",
                "value" => sprintf('%.2f', $amount),
            ],
			"method"       => $method,
			"description"  => $description,
			"redirectUrl"  => $return_url,
            "webhookUrl"   => $webhook_url,
            "metadata"     => array(
				"order_id" => $order_id
			),
			"issuer"       => $issuer
		));
	}

	/**
	 * @param $transaction_id
	 * @return \Mollie\Api\Resources\Payment|false
	 * @throws ApiException
	 */
	public function checkPayment ($transaction_id) {

        return $this->mollie->payments->get($transaction_id);

    }



}