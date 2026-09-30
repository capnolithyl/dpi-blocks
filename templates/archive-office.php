<?php
/** Plugin fallback for the Office archive. */

use DPI\Blocks\TemplateLoader;

defined( 'ABSPATH' ) || exit;

$dpi_directory = TemplateLoader::directory_context( 'office', 'office_group' );

get_header();
require TemplateLoader::template_path( '_directory.php' );
get_footer();
