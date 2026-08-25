<?php
/** Plugin fallback for Office Group archives. */

use DPI\Blocks\TemplateLoader;

defined( 'ABSPATH' ) || exit;

require TemplateLoader::template_path( 'archive-office.php' );
