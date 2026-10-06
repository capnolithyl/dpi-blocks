<?php
/**
 * Plugin service coordinator.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static ?self $instance = null;

	private bool $registered = false;

	private Assets $assets;
	private Settings $settings;
	private Header $header;
	private ContentTypes $content_types;
	private TemplateLoader $templates;
	private BlockLab $block_lab;
	private Updater $updater;

	/** Return the shared plugin instance. */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Wire all plugin services. */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}
		$this->registered = true;

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		$this->settings      = new Settings();
		$this->assets        = new Assets();
		$this->content_types = new ContentTypes();
		$this->header        = new Header();
		$this->templates     = new TemplateLoader();
		$this->block_lab     = new BlockLab();
		$this->updater       = new Updater();

		$this->settings->register();
		$this->assets->register();
		$this->content_types->register();
		$this->header->register();
		$this->templates->register();
		$this->block_lab->register();
		$this->updater->register();

		if ( Dependencies::has_acf_pro() ) {
			( new Fields() )->register();
			( new Blocks() )->register();
		} else {
			add_action( 'admin_notices', array( Dependencies::class, 'render_admin_notice' ) );
		}
	}

	/** Load packaged translations after WordPress has initialized localization. */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'dpi-blocks', false, dirname( plugin_basename( DPI_BLOCKS_FILE ) ) . '/languages' );
	}

	/** Return the header integration service. */
	public function header(): Header {
		if ( ! isset( $this->header ) ) {
			$this->header = new Header();
		}
		return $this->header;
	}

	/** Return the shared asset service. */
	public function assets(): Assets {
		if ( ! isset( $this->assets ) ) {
			$this->assets = new Assets();
		}
		return $this->assets;
	}
}
