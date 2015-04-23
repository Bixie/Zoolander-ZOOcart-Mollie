<?php
/**
 * @package		ZOOcart
 * @author		ZOOlanders http://www.zoolanders.com
 * @author		Matthijs Alles - Bixie
 * @copyright	Copyright (C) JOOlanders, SL
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 */

/**
 * @var string $actionUrl
 * @var string $selected
 * @var Mollie_API_Object_List|Mollie_API_Object_Method[] $methods
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<?php if (!empty($data['errorMessage'])) : ?>
	<div class="uk-alert uk-alert-warning"><?php echo $data['errorMessage']; ?></div>
<?php else: ?>
	<form action="<?php echo $actionUrl; ?>" method="post">
		<p><?php echo JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_ORDER_PLACED'); ?></p>
		<?php if (count($methods) == 1) :
			$method = reset($methods);
			?>
			<div class="uk-grid">
				<div class="uk-width-1-2">
					<div class="uk-panel uk-panel-box">
						<img src="<?php echo $method->image->normal; ?>" alt="<?php echo $method->id; ?>" >
						<strong><?php echo $method->description; ?></strong>
					</div>
				</div>
				<div class="uk-width-1-2 uk-text-right">
					<input type="hidden" name="mollie_method" value="<?php echo $method->id; ?>"/>
					<button type="submit" class="uk-button uk-button-success"><?php echo JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_TO_PAYMENT'); ?></button>
				</div>
			</div>

		<?php else: ?>
			<h4><?php echo JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_CHOOSE_METHOD'); ?></h4>
			<div class="uk-grid uk-grid-small uk-grid-width-medium-1-<?php echo count($methods); ?> uk-margin">
				<?php foreach ($methods as $method) : ?>
					<div>
						<div class="uk-panel uk-panel-box">
							<label for="mollie_method_<?php echo $method->id; ?>">
								<input type="radio" name="mollie_method" id="mollie_method_<?php echo $method->id; ?>"  class="uk-margin-small-right"
									   value="<?php echo $method->id; ?>" <?php echo $method->id == $selected ? 'checked="checked"' : ''; ?>/>
								<img src="<?php echo $method->image->normal; ?>" alt="<?php echo $method->id; ?>" >
								<strong><?php echo $method->description; ?></strong>
							</label>
						</div>
					</div>

				<?php endforeach; ?>
			</div>
			<div class="uk-text-right">
				<button type="submit" class="uk-button uk-button-success"><?php echo JText::_('PLG_ZOOCART_PAYMENT_MOLLIE_TO_PAYMENT'); ?></button>
			</div>
		<?php endif; ?>
		<input type="hidden" name="mollie_task" value="prepare"/>
		<?php echo JHtml::_('form.token'); ?>
	</form>
<?php endif; ?>



