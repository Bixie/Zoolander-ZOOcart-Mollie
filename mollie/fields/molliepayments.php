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

	public function getOptions () {
		$options = array(JHtml::_('select.option', '', 'Optie'));
		return $options;
	}

}