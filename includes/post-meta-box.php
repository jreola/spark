<?php

class WP_Spark_Post_Meta_Box {

	/**
	 * @var WP_Spark_Plugin
	 */
	private $plugin;

	public function __construct( WP_Spark_Plugin $plugin ) {
		$this->plugin = $plugin;

		add_action( 'add_meta_boxes_' . $this->plugin->post_type->name, array( $this, 'add_meta_box' ) );

		add_action( 'save_post', array( $this, 'save_post' ) );

		// AJAX handler to test sending notification.
		add_action( 'wp_ajax_spark_test_notify', array( $this, 'ajax_test_notify' ) );
	}

	public function add_meta_box() {
		add_meta_box(
			// ID.
			'spark_setting_metabox',

			// Title.
			__( 'Integration Setting', 'spark' ),

			// Callback.
			array( $this, 'render_meta_box' ),

			// Screen.
			$this->plugin->post_type->name,

			// Context.
			'advanced',

			// Priority.
			'high'
		);
	}

	/**
	 * Display the meta box.
	 *
	 * @param object $post
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field(
			// Action
			$this->plugin->post_type->name,

			// Name.
			$this->plugin->post_type->name . '_nonce'
		);

		// Get existing setting.
		$setting = get_post_meta( $post->ID, 'spark_integration_setting', true );

		// Available events.
		$events = $this->plugin->event_manager->get_events();

		require_once $this->plugin->plugin_path . 'views/post-meta-box.php';
	}

	/**
	 * Saves data in meta box to post meta.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_post( $post_id ) {
		if ( $this->plugin->post_type->name !== get_post_type( $post_id ) ) {
			return;
		}

		// Check nonce.
		if ( empty( $_POST[ $this->plugin->post_type->name . '_nonce' ] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $_POST[ $this->plugin->post_type->name . '_nonce' ], $this->plugin->post_type->name ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_POST['spark_setting'] ) ) {
			return;
		}

		$events = array_keys( $this->plugin->event_manager->get_events() );
		$fields = array(
			'room_id'     => 'sanitize_text_field',
			'active'      => function( $val ) {
				if ( $val ) {
					return true;
				} else {
					return false;
				}
			},
			'events' => function( $val ) use( $events ) {
				$saved = array_fill_keys( $events , 0 );

				foreach ( $events as $event ) {
					if ( ! empty( $val[ $event ] ) ) {
						$saved[ $event ] = absint( $val[ $event ] );
					}
				}

				return $saved;
			}
		);

		$cleaned = array();

		$previous_setting = get_post_meta( $post_id, 'spark_integration_setting', true );
		foreach ( $fields as $field => $sanitizer ) {
			if ( is_callable( $sanitizer ) ) {
				$cleaned[ $field ] = call_user_func(
					$sanitizer,
					! empty( $_POST['spark_setting'][ $field ] ) ? $_POST['spark_setting'][ $field ] : null,
					! empty( $previous_setting[ $field ] ) ? $previous_setting[ $field ] : null
				);
			}
		}

		update_post_meta( $post_id, 'spark_integration_setting', $cleaned );
	}

	public function ajax_test_notify() {
		try {
			$expected_params = array(
				'room_id',
				'test_notify_nonce',
			);
			foreach ( $expected_params as $param ) {
				if ( ! isset( $_REQUEST[ $param ] ) ) {
					throw new Exception( sprintf( __( 'Missing param %s', 'spark' ), $param ) );
				}
			}

			if ( ! wp_verify_nonce( $_REQUEST['test_notify_nonce'], 'test_notify_nonce' ) ) {
				throw new Exception( __( 'Malformed value for nonce', 'spark' ) );
			}

			$payload = array(
				'room_id'     => $_REQUEST['room_id'],
				'text'        => __( 'Test sending payload!', 'spark' ),
			);

			$resp = $this->plugin->notifier->notify( new WP_Spark_Event_Payload( $payload ) );

			if ( is_wp_error( $resp ) ) {
				throw new Exception( $resp->get_error_message() );
			}

			$body = trim( wp_remote_retrieve_body( $resp ) );
			if ( 200 !== intval( wp_remote_retrieve_response_code( $resp ) ) ) {
				throw new Exception( $body );
			}

			if ( 'ok' !== strtolower( $body ) ) {
				throw new Exception( $body );
			}
			wp_send_json_success();

		} catch ( Exception $e ) {
			$status_code = 500;
			$message     = $e->getMessage();

			if ( ! $message ) {
				$message = __( 'Unexpected response', 'spark' );
			}

			status_header( 500 );
			wp_send_json_error( $message );
		}
	}
}
