<?php
/**
* @package		ZOOcart
* @author		ZOOlanders http://www.zoolanders.com
* @author		Matthijs Alles - Bixie
* @copyright	Copyright (C) JOOlanders, SL
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
require_once __DIR__ . '/molliehelper.php';

class plgZoocart_PaymentMollie extends JPaymentDriver {

	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function getPaymentFee($data = array()) {
		if ($this->params->get('fee_type' ,'net') == 'perc') {
			$perc = ((float) $this->params->get('fee', 0)) / 100;

			if ($data['order']) {
				return $data['order']->net * $perc;
			} else {
				$total  = $this->app->zoocart->cart->getTotal($this->app->user->get()->id)
					- $this->app->zoocart->cart->getPaymentFee();
				return $total * $perc;
			}
		}

		return (float) $this->params->get('fee', 0);
	}

	protected function getRenderData($data = array()) {
		$data = parent::getRenderData($data);
		//sort data
		$methodsAllowed = $this->params->get('methods', array());
		$data['errorMessage'] = '';
		$methods = array();
		$selected = null;
		$amount = $data['order']->total;
		try {
			$mollie = new Molliehelper($this->params);

			$methodsData = $mollie->getMethods();
			foreach ($methodsData as $method) {
				if (in_array($method->id, $methodsAllowed) && $amount > $method->getMinimumAmount() && $amount < $method->getMaximumAmount()) {
					if ($selected == null) { //select first
						$selected = $method->id;
					}
					$methods[] = $method;
				}
			}
			//geen geldige methode
			if (count($methodsAllowed) && $selected == null) {
				$data['errorMessage'] = JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_INVALID_AMOUNT');
			}

		} catch (Mollie_API_Exception $e) {
			$data['errorMessage'] = $e->getMessage();
		}

		//get the methods
		$data['actionUrl'] = $this->app->zoocart->payment->getCallbackUrl('mollie');
		$data['selected'] = $selected;
		$data['methods'] = $methods;
		return $data;
	}

	public function message($data = array()) {
		$html = '';
		$message = $this->app->session->get('com_zoo.zoocart.payment_mollie.message','');
		$messageStyle = $this->app->session->get('com_zoo.zoocart.payment_mollie.messageStyle','');
		$formHtml = $this->app->session->get('com_zoo.zoocart.payment_mollie.formHtml','');
		if ($message || $formHtml) {
			if ($message) $html .= '<div class="uk-alert uk-alert-large '.$messageStyle.'" data-uk-alert><a href="" class="uk-alert-close uk-close"></a>'.$message.'</div>';
			if ($formHtml) $html .= '<div class="uk-form">'.$formHtml.'</div>';
			$this->app->session->set('com_zoo.zoocart.payment_mollie.message',null);
			$this->app->session->set('com_zoo.zoocart.payment_mollie.messageStyle',null);
			$this->app->session->set('com_zoo.zoocart.payment_mollie.formHtml',null);
		}
		return $html;
	}

	public function zoocartRender($data = array()) {
		$app = App::getInstance('zoo');
		$data['order']->state = $app->zoocart->getConfig()->get('payment_pending_orderstate', 4);
		$app->zoocart->table->orders->save($data['order']);

		return parent::zoocartRender($data);
	}
	
	/**
	 * Plugin event triggered when the payment plugin notifies for the transaction
	 *
	 * @param  array  $data The data received
	 *
	 * @return array(
	 *         		status: 0 => failed, 1 => success, -1 => pending
	 *         		transaction_id
	 *         		order_id,
	 *         		total,
	 * 			redirect: false (default) or internal url
	 *         )
	 */
	public function zoocartCallback(&$data = array()) {
		$data = $this->app->data->create($data);
		$task = $this->app->request->get('mollie_task', '');
		$return = array(
			'status' => JPaymentDriver::ZC_PAYMENT_PENDING,
			'transaction_id' => '',
			'order_id' => 0,
			'total' => 0,
			'redirect'=> false
		);
		switch ($task) {
			case 'prepare':
				$order_id = (int) $this->app->session->get('com_zoo.zoocart.pay_order_id');
				$order = $this->app->zoocart->table->orders->get($order_id);
				$method = $this->app->request->get('mollie_method', '');
				$issuer = $this->app->request->get('mollie_issuer', null);
				$amount = $order->total;
				$returnUrl = $this->app->zoocart->payment->getCallbackUrl('mollie', 'raw') . '&mollie_task=return&order_id=' . $order_id;
				$description = JText::_('PLG_ZOOCART_ORDER') . ' ' . $order->id . ', ' . JFactory::getApplication()->getCfg('sitename');

				$return['order_id'] = $order_id;
				try {
					$mollie = new Molliehelper($this->params);

					$payment = $mollie->createPayment($method, $order->id, $amount, $description, $returnUrl, $issuer);
					$return['transaction_id'] = $payment->id;
					$this->app->session->set('com_zoo.zoocart.payment_mollie.payment_id.order' . $order->id, $payment->id);
					$return['redirect'] = $payment->getPaymentUrl();
				} catch (Mollie_API_Exception $e) {
					JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
					$return['status'] = JPaymentDriver::ZC_PAYMENT_FAILED;
					$return['redirect'] = $returnUrl;
				}
				break;
			case 'selectbank':
			    //todo for ideal
				$apiResult = array();
				break;
			case 'return':
				$data = $this->app->data->create( $data );
				if ($id = (int) $data->get('order_id', null)) {
					$order = $this->app->table->orders->get( $id );
				} else {
					$order = $data->get( 'order', null );
				}
				$return['order_id'] = $order->id;
				$return['transaction_id'] = $this->app->session->get('com_zoo.zoocart.payment_mollie.payment_id.order' . $order->id);
				$return['redirect'] = $this->app->zoocart->payment->getReturnUrl();
				$message = array();
				try {
					$mollie = new Molliehelper($this->params);
					$payment = $mollie->checkPayment($return['transaction_id']);
					if ($payment->isPaid() == true) {
						$return['status'] = JPaymentDriver::ZC_PAYMENT_PAYED;
						$return['total'] = $payment->amount;
						$message['message'] = JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_TRANS_SUCCESS');
						$message['messageStyle'] = 'uk-alert-success';
					} elseif ($payment->isOpen() == false) {
						$return['status'] = JPaymentDriver::ZC_PAYMENT_FAILED;
						$return['total'] = $order->subtotal;
						$message['message'] = JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_TRANS_FAILED');
						$message['messageStyle'] = 'uk-alert-danger';
					}

					$this->app->session->set('com_zoo.zoocart.payment_mollie.payment_id.order' . $order->id, null);

				} catch (Mollie_API_Exception $e) {
					$return['status'] = JPaymentDriver::ZC_PAYMENT_FAILED;
					$return['total'] = $order->subtotal;
					$message['message'] = $e->getMessage();
					$message['messageStyle'] = 'uk-alert-danger';
				}
				$this->app->session->set('com_zoo.zoocart.payment_mollie.message',$message['message']);
				$this->app->session->set('com_zoo.zoocart.payment_mollie.messageStyle',$message['messageStyle']);
				$this->app->session->set('com_zoo.zoocart.payment_mollie.formHtml',$message['formHtml']);

				break;
			case 'webhook':
				$data = $this->app->data->create($data);
				$payment_id = $data->get('id', null);
				if ($payment_id) {
					try {
						$mollie = new Molliehelper($this->params);
						$payment = $mollie->checkPayment($return['transaction_id']);
						if ($id = (int) $payment->metadata->order_id) {
							$order = $this->app->table->orders->get( $id );
						} else {
							throw new Mollie_API_Exception(JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_INVALID_REQUEST'));
						}
						$return['order_id'] = $order->id;
						if ($payment->isPaid() == true) {
							$return['status'] = JPaymentDriver::ZC_PAYMENT_PAYED;
							$return['total'] = $payment->amount;
							$message['message'] = JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_TRANS_SUCCESS');
							$message['messageStyle'] = 'uk-alert-success';
						} elseif ($payment->isOpen() == false) {
							$return['status'] = JPaymentDriver::ZC_PAYMENT_FAILED;
							$return['total'] = $order->subtotal;
							$message['message'] = JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_TRANS_FAILED');
							$message['messageStyle'] = 'uk-alert-danger';
						}

					} catch (Mollie_API_Exception $e) {
						$return['status'] = JPaymentDriver::ZC_PAYMENT_FAILED;
						$message['message'] = $e->getMessage();
						$message['messageStyle'] = 'uk-alert-danger';
					}
				}
				break;
			default:
				break;
		}
		return $return;
	}
	

}
