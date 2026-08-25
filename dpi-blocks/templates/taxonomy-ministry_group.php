<?php
/** Plugin fallback for Ministry Group archives. */

use DPI\Blocks\TemplateLoader;

defined( 'ABSPATH' ) || exit;

require TemplateLoader::template_path( 'archive-ministry.php' );
