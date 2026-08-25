<?php
/** Plugin fallback for Staff Group archives. */

use DPI\Blocks\TemplateLoader;

defined( 'ABSPATH' ) || exit;

require TemplateLoader::template_path( 'archive-staff.php' );
