<?php

namespace WPDRMS\ASL\Options\Routes;

if ( !defined('ABSPATH') ) {
	die("You can't access this file directly.");
}

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WPDRMS\ASL\Rest\AbstractRest;

class SearchOptionsRoute extends AbstractRest {
	public function registerRoutes(): void {
		register_rest_route(
			ASL_DIR,
			'options/search_instance/get',
			array(
				'methods'             => 'GET',
				'callback'            => array(
					$this,
					'getSearchInstanceOptions',
				),
				'permission_callback' => array(
					$this,
					'allowOnlyAdmins',
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function getSearchInstanceOptions( WP_REST_Request $request ): WP_REST_Response {
		try {
			$data                        = wd_asl()->instances->get(0)['data'];
			$data['advtitlefield']       = stripcslashes($data['advtitlefield']);
			$data['advdescriptionfield'] = stripcslashes($data['advdescriptionfield']);
			return new WP_REST_Response(
				$data,
				200
			);
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				new WP_Error('taxonomy_terms_get', $e->getMessage()),
				400
			);
		}
	}
}
