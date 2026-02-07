<?php
/**
 * Result exclusions options
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<fieldset class="<?php echo class_exists('SitePress') || function_exists('pll_current_language') ? '' : 'hiddend'; ?>">
	<legend><?php esc_html_e('Mutli-language support', 'ajax-search-lite'); ?></legend>
	<div class="item<?php echo class_exists('SitePress') ? '' : ' hiddend'; ?>">
		<?php
		/** @noinspection PhpUndefinedVariableInspection */
		new wpdreamsYesNo('wpml_compatibility', __('WPML compatibility', 'ajax-search-lite'), $sd['wpml_compatibility']);
		?>
	</div>
	<div class="item<?php echo function_exists('pll_current_language') ? '' : ' hiddend'; ?>">
		<?php
		new wpdreamsYesNo('polylang_compatibility', __('Polylang compatibility', 'ajax-search-lite'), $sd['polylang_compatibility']);
		?>
	</div>
</fieldset>
<fieldset class="<?php echo class_exists('WooCommerce') ? '' : 'hiddend'; ?>">
	<legend><?php esc_html_e('WooCommerce related', 'ajax-search-lite'); ?></legend>
	<div class="item">
		<?php
		new wpdreamsYesNo('exclude_woo_hidden', __('Exclude hidden search WooCommerce products from search?', 'ajax-search-lite'), $sd['exclude_woo_hidden']);
		?>
		<p class="descMsg"><?php esc_html_e('"Hidden" in this case means "Shop only" or "Hidden"', 'ajax-search-lite'); ?></p>
	</div>
	<div class="item">
		<?php
		new wpdreamsYesNo('exclude_woo_catalog', __('Exclude hidden catalog WooCommerce products from search?', 'ajax-search-lite'), $sd['exclude_woo_catalog']);
		?>
		<p class="descMsg"><?php esc_html_e('This case means "Search results only"', 'ajax-search-lite'); ?></p>
	</div>
	<div class="item">
		<?php
		new wpdreamsYesNo('woo_exclude_outofstock', __('Exclude WooCommerce out of stock products?', 'ajax-search-lite'), $sd['woo_exclude_outofstock']);
		?>
	</div>
</fieldset>
<fieldset>
	<legend><?php esc_html_e('Exclude results', 'ajax-search-lite'); ?></legend>
	<div class="item">
		<?php
		$o                                     = new wpdreamsCategories('excludecategories', __('Exclude categories', 'ajax-search-lite'), $sd['excludecategories']);
		$params[ $o->getName() ]               = $o->getData();
		$params[ 'selected-' . $o->getName() ] = $o->getSelected();
		?>
	</div>
	<div class="item">
		<?php
		$o                       = new wpdreamsTextarea('excludeposts', __("Exclude Posts by ID's (comma separated post ID-s)", 'ajax-search-lite'), $sd['excludeposts']);
		$params[ $o->getName() ] = $o->getData();
		?>
	</div>
</fieldset>
