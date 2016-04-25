<?php
class WP_Spark_Submit_Meta_Box {

	/**
	 * @var WP_Spark_Plugin
	 */
	private $plugin;

	public function __construct( WP_Spark_Plugin $plugin ) {
		$this->plugin = $plugin;

		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Register submit meta box.
	 *
	 * @param
	 */
	public function register_meta_box( $post_type ) {
		if ( $this->plugin->post_type->name === $post_type ) {
			add_meta_box( 'spark_submitdiv', __( 'Save Setting', 'spark' ), array( $this, 'spark_submitdiv' ), null, 'side', 'core' );
		}
	}

	/**
	 * Display post submit form fields.
	 *
	 * @param object $post
	 */
	public function spark_submitdiv( $post ) {
		require_once $this->plugin->plugin_path . 'views/submit-meta-box.php';
	}
}
