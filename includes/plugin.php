<?php

class WP_Spark_Plugin {

	/**
	 * @var array
	 */
	private $items = array();

	/**
	 * @param string $path Path to main plugin file
	 */
	public function run( $path ) {
		// Basic plugin information.
		$this->name    = 'wp_spark'; // This maybe used to prefix options, slug of menu or page, and filters/actions.
		$this->version = '0.0.1';

		// Path.
		$this->plugin_path   = trailingslashit( plugin_dir_path( $path ) );
		$this->plugin_url    = trailingslashit( plugin_dir_url( $path ) );
		$this->includes_path = $this->plugin_path . trailingslashit( 'includes' );

		// Instances.
		$this->post_type       = new WP_Spark_Post_Type( $this );
		$this->notifier        = new WP_Spark_Notification( $this );
		$this->post_meta_box   = new WP_Spark_Post_Meta_Box( $this );
		$this->submit_meta_box = new WP_Spark_Submit_Meta_Box( $this );
		$this->event_manager   = new WP_Spark_Event_Manager( $this );
	}

	public function __set( $key, $value ) {
		$this->items[ $key ] = $value;
	}

	public function __get( $key ) {
		if ( isset( $this->items[ $key ] ) ) {
			return $this->items[ $key ];
		}

		return null;
	}

	public function __isset( $key ) {
		return isset( $this->items[ $key ] );
	}

	public function __unset( $key ) {
		if ( isset( $this->items[ $key ] ) ) {
			unset( $this->items[ $key ], $this->raws[ $key ], $this->frozen[ $key ] );
		}
	}
}
