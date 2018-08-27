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
if (!class_exists( 'Bixie\ZoocartMollie\Helper')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

JFormHelper::loadFieldClass('list');
use Bixie\ZoocartMollie\Helper as Molliehelper;
use Mollie\Api\Exceptions\ApiException;

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
		$this->pluginParams = new JRegistry;
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
		} catch (ApiException $e) {
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
		return $options;
	}

}