<?php
if ( !class_exists('ASL_Post') ) {
	/**
	 * Class ASL_Post
	 *
	 * A default class to instantiate to generate post like results.
	 */
	class ASL_Post {

		public $ID                    = 0;
		public $post_title            = '';
		public $post_author           = '';
		public $post_name             = '';
		public $post_type             = 'post';
		public $post_date             = '0000-00-00 00:00:00';
		public $post_date_gmt         = '0000-00-00 00:00:00';
		public $post_content          = '';
		public $post_content_filtered = '';
		public $post_excerpt          = '';
		public $post_status           = 'publish';
		public $comment_status        = 'closed';
		public $ping_status           = 'closed';
		public $post_password         = '';
		public $post_parent           = 0;
		public $post_mime_type        = '';
		public $to_ping               = '';
		public $pinged                = '';
		public $post_modified         = '';
		public $post_modified_gmt     = '';
		public $comment_count         = 0;
		public $menu_order            = 0;
		public $guid                  = '';
		public $asl_guid;
		public $asl_id;
		public $asl_data;
		public $blogid;

		public function __construct() {}
	}
}
