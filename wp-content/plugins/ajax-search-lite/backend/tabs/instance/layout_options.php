<?php
/**
 * Layout options panel
 *
 * @noinspection HtmlUnknownAttribute
 */

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>

<ul id="subtabs"  class='tabs'>
	<li><a tabid="401" class='subtheme current'><?php esc_html_e('Search Box layout & Theme', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="402" class='subtheme'><?php esc_html_e('Results layout', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="403" class='subtheme'><?php esc_html_e('Results Behaviour', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="404" class='subtheme'><?php esc_html_e('Keyword Highlighting', 'ajax-search-lite'); ?></a></li>
	<li><a tabid="405" class='subtheme'><?php esc_html_e('Custom CSS', 'ajax-search-lite'); ?></a></li>
</ul>
<div class='tabscontent'>
	<div tabid="401">
		<?php require ASL_PATH . 'backend/tabs/instance/layout/box_layout.php'; ?>
	</div>
	<div tabid="402">
		<fieldset>
			<legend><?php esc_html_e('Results layout', 'ajax-search-lite'); ?>
				<span class="asl_legend_docs">
					<a target="_blank" href="https://documentation.ajaxsearchlite.com/layout-options/results-layout-and-fields-shown"><span class="fa fa-book"></span>
						<?php esc_html_e('Documentation', 'ajax-search-lite'); ?>
					</a>
				</span>
			</legend>
			<?php require ASL_PATH . 'backend/tabs/instance/layout/results_layout.php'; ?>
		</fieldset>
	</div>
	<div tabid="403">
		<fieldset>
			<legend><?php esc_html_e('Results Behaviour', 'ajax-search-lite'); ?>
				<span class="asl_legend_docs">
					<a target="_blank" href="https://documentation.ajaxsearchlite.com/layout-options/results-box-behavior"><span class="fa fa-book"></span>
						<?php esc_html_e('Documentation', 'ajax-search-lite'); ?>
					</a>
				</span>
			</legend>
			<?php require ASL_PATH . 'backend/tabs/instance/layout/results_behaviour.php'; ?>
		</fieldset>
	</div>
	<div tabid="404">
		<?php require ASL_PATH . 'backend/tabs/instance/layout/keyword_highlight.php'; ?>
	</div>
	<div tabid="405">
		<fieldset>
			<legend><?php esc_html_e('Custom CSS', 'ajax-search-lite'); ?></legend>
			<?php require ASL_PATH . 'backend/tabs/instance/layout/custom_css.php'; ?>
		</fieldset>
	</div>
</div>
<div class="item">
	<input type="hidden" name='asl_submit' value=1 />
	<input name="submit_asl" type="submit" value="<?php esc_html_e('Save options!', 'ajax-search-lite'); ?>" />
</div>
