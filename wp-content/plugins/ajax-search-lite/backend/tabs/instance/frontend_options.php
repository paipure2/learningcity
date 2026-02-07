<?php
/**
 * Frontend search options panel
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<ul id="subtabs"  class='tabs'>
	<li><a tabid="301" class='subtheme current'><?php esc_html_e('Category Filters', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="302" class='subtheme'><?php esc_html_e('More Filters', 'ajax-search-lite'); ?><span>PREMIUM</span></a></li>
</ul>
<div class='tabscontent'>
	<div tabid="301">
		<?php require ASL_PATH . 'backend/tabs/instance/frontend/category_filters.php'; ?>
	</div>
	<div tabid="302">
		<?php require ASL_PATH . 'backend/tabs/instance/frontend/more_filters.php'; ?>
	</div>
</div>


<div class="item">
	<input type="hidden" name='asl_submit' value=1 />
	<input name="submit_asl" type="submit" value="Save options!" />
</div>
