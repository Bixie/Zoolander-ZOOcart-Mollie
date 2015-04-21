<?php
/* *
 *	Bixie ZOOcart - Mollie
 *  molliepayments.php
 *	Created on 8-4-2015 23:32
 *  
 *  @author Matthijs
 *  @copyright Copyright (C)2015 Bixie.nl
 *
 */

// No direct access
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');
require_once dirname(__DIR__) . '/molliehelper.php';
/**
 *
 */
class JFormFieldMolliepayments extends JFormFieldList {
	/**
	 * The form field type.
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'Molliepayments';

	protected $pluginParams;

	protected function getInput () {
		$plugin = JPluginHelper::getPlugin('zoocart_payment', 'mollie');
		$this->pluginParams = new \Joomla\Registry\Registry();
		$this->pluginParams->loadString($plugin->params);
		if ($this->pluginParams->get('live_api', '') == '' || $this->pluginParams->get('test_api', '') == '') {
			return JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_TYPE_API_ERROR');
		} else {
			return parent::getInput();
		}
	}


	public function getOptions () {

		$options = array();
		try {
			$mollie = new Molliehelper($this->pluginParams);

			$methods = $mollie->getMethods();
			foreach ($methods as $method) {
				$options[] = JHtml::_('select.option', $method->id, $method->description);
			}
		} catch (Mollie_API_Exception $e) {
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
		return $options;
	}

}