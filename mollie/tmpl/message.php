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

	$zoo = App::getInstance('zoo');
	$app_id = $zoo->request->get('app_id', 'int', null);
	$app_id = !$app_id && $zoo->zoo->getApplication() ? $zoo->zoo->getApplication()->id : $app_id;
	$link = $zoo->link(array('controller' => 'orders', 'task' => 'view', 'id' => $order->id, 'app_id' => $app_id));
?>

<div class="zoocart-container">
	<p><?php echo JText::_('PLG_ZOOCART_ORDER_SUCCESS_MESSAGE'); ?></p>
	<a class="btn btn-primary" href="<?php echo $link; ?>"><?php echo JText::_('PLG_ZOOCART_GO_TO_ORDER'); ?></a>
</div>