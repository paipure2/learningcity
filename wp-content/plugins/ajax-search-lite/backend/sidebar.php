<?php
if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}
?>
<div id="asl-side-container">
	<a class="wd-accessible-switch" href="#"><?php echo isset($_COOKIE['asl-accessibility']) ? 'DISABLE ACCESSIBILITY' : 'ENABLE ACCESSIBILITY'; ?></a>
	<div class="newsletter">
		<h2><?php esc_html_e('Subscribe to our newsletter', 'ajax-search-lite'); ?></h2>
		<p><?php esc_html_e('Get the latest news and updates', 'ajax-search-lite'); ?></p>
		<form action="https://wp-dreams.us9.list-manage.com/subscribe/post?u=370663b5e3df02747aa5673ed&amp;id=65e28ba277&amp;f_id=00220ae1f0" method="post" name="mc-embedded-subscribe-form" target="_blank">
			<input name="EMAIL" aria-label="<?php esc_attr_e('Email address', 'ajax-search-lite'); ?>" id="email" type="email" placeholder="email@domain.com"><input type="submit" value="<?php esc_attr_e('Subscribe', 'ajax-search-lite'); ?>" name="subscribe">
		</form>
	</div>
	<div class="gopro">
		<h2>Try the pro version Backend for FREE</h2>
		<p>Get instant access to the PRO version <strong>backend</strong> demo.</p>
		<form target="_blank" class="asp_form_try" action="https://ajaxsearchpro.com/admin-demo/" method="POST">
			<input class="asp_form_try_email" aria-label="<?php esc_attr_e('Email address', 'ajax-search-lite'); ?>" placeholder="email@domain.com" name="email" type="email" tabindex="0">
			<input class="asp_form_try_submit button" type="submit"  value="<?php esc_attr_e('Try Now', 'ajax-search-lite'); ?>" tabindex="0">
		</form>
	</div>
</div>