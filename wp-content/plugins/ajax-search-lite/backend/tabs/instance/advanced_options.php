<?php
/**
 * Advanced options panel
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<ul id="subtabs"  class='tabs'>
	<li><a tabid="701" class='subtheme current'><?php esc_html_e('Content & Fields', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="702" class='subtheme'><?php esc_html_e('Exclude Results', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="703" class='subtheme'><?php esc_html_e('Keyword exceptions', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="705" class='subtheme'><?php esc_html_e('Accessibility', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="706" class='subtheme'><?php esc_html_e('More', 'ajax-search-lite'); ?><span>PREMIUM</span></a></li>
</ul>
<div class='tabscontent'>
	<div tabid="701">
		<?php require ASL_PATH . 'backend/tabs/instance/advanced/content.php'; ?>
	</div>
	<div tabid="702">
		<fieldset>
			<legend>
				<?php esc_html_e('Exclude/Include results', 'ajax-search-lite'); ?>
				<span class="asl_legend_docs">
					<a target="_blank" href="https://documentation.ajaxsearchlite.com/advanced-options/excluding-and-including-results"><span class="fa fa-book"></span>
						<?php esc_html_e('Documentation', 'ajax-search-lite'); ?>
					</a>
				</span>
			</legend>
			<?php require ASL_PATH . 'backend/tabs/instance/advanced/exclude_results.php'; ?>
		</fieldset>
	</div>
	<div tabid="703">
		<fieldset>
			<legend><?php esc_html_e('Keyword exceptions', 'ajax-search-lite'); ?>
				<span class="asl_legend_docs">
					<a target="_blank" href="https://documentation.ajaxsearchlite.com/advanced-options/keyword-exceptions"><span class="fa fa-book"></span>
						<?php esc_html_e('Documentation', 'ajax-search-lite'); ?>
					</a>
				</span>
			</legend>
			<?php require ASL_PATH . 'backend/tabs/instance/advanced/kw_exceptions.php'; ?>
		</fieldset>
	</div>
	<div tabid="705">
		<fieldset>
			<legend><?php esc_html_e('Accessibility', 'ajax-search-lite'); ?></legend>
			<?php require ASL_PATH . 'backend/tabs/instance/advanced/accessibility.php'; ?>
		</fieldset>
	</div>
	<div tabid="706">
		<?php require ASL_PATH . 'backend/tabs/instance/advanced/advanced_premium_more.php'; ?>
	</div>
</div>


<div class="item">
	<input type="hidden" name='asl_submit' value=1 />
	<input name="submit_asl" type="submit" value="Save options!" />
</div>
