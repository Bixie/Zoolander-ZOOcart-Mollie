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

class plgZoocart_PaymentMollie extends JPaymentDriver {

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
		$data['test'] = $this->params->get('test', 0);
		$data['auto'] = $this->params->get('auto', 0);
		$billing_address_id = $this->app->data->create($data['order']->billing_address)->id;
		$billing_address = $this->app->zoocart->table->addresses->get((int)$billing_address_id);
		$user = JFactory::getUser($data['order']->user_id);
		$amount = $data['order']->total;
		$description = JText::_('PLG_ZOOCART_ORDER') . ' ' . $data['order']->id . ', ' . JFactory::getApplication()->getCfg('sitename');
		$aOrderParams['contact'] = array(
			'first_name' => '', 
			'last_name' => $billing_address->name, 
			'address1' => $billing_address->address, 
			'address2' => '', 
			'zip' => $billing_address->zip, 
			'city' =>$billing_address->city, 
			'country' => $billing_address->country, 
			'email' => $user->email, 
			'phone' => '', 
		);
		$aOrderParams['status'] = array(
			'success' => 'COMPLETED',
			'pending' => 'PENDING',
			'cancelled' => 'FAILED',
		);
// $this->app->zoocart->payment->getCallbackUrl('ideal');
		//get the gateway

		return $data;
	}

	public function message($data = array()) {
		$html = '';
		$app = App::getInstance('zoo');
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

	public function render($data = array()) {
		$app = App::getInstance('zoo');
		$data['order']->state = $app->zoocart->getConfig()->get('payment_pending_orderstate', 4);
		$app->zoocart->table->orders->save($data['order']);

		return parent::render($data);
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
	public function callback($data = array()) {
		$data = $this->app->data->create($data);
		//get the gatewaysettings
		$mollieType = $this->params->get('type', 'mollie-simulator');
		$returnResult = array();
		$this->app->session->set('com_zoo.zoocart.payment_mollie.message',$returnResult['message']);
		$this->app->session->set('com_zoo.zoocart.payment_mollie.messageStyle',$returnResult['messageStyle']);
		$this->app->session->set('com_zoo.zoocart.payment_mollie.formHtml',$returnResult['formHtml']);
		//	echo $oGateway->doValidate(); //TODO lookup transactions in admin?

		$id = (int) $returnResult['order_id'];
		if($id) {
			$order = $this->app->zoocart->table->orders->get($id);
		} else {
			$order = $data->get('order', null);
		}
		$status = JPaymentDriver::ZC_PAYMENT_FAILED;
		// Checked against frauds in gateway
		$valid = $returnResult['valid'];

		if ($valid) {
			$valid = $order->id > 0;
			// todo: check multiple crossing payments
			if (!$valid) {
				$status = JPaymentDriver::ZC_PAYMENT_FAILED;
			} else {
				//get the payment_status
				$status = $returnResult['success'];
			}
			return array('status' => $status, 'transaction_id' => $returnResult['transaction_id'], 'order_id' => $order->id, 'total' => $order->total,'redirect'=>$returnResult['redirect']);
		}
		//add a redirect option here
		return array('status' => $status, 'transaction_id' => $returnResult['transaction_id'], 'order_id' => $order->id, 'total' => 0,'redirect'=>$returnResult['redirect']);
	}
	

}
