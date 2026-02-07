<?php

namespace WPDRMS\ASL\BlockEditor;

interface BlockInterface {
	/**
	 * Block registration handler
	 *
	 * @return void
	 */
	public function register(): void;
}