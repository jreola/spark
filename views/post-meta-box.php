<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row">
				<label for="spark_setting[room_id]"><?php _e( 'Room ID', 'spark' ); ?></label>
			</th>
			<td>
				<input type="text" class="regular-text" name="spark_setting[room_id]" id="spark_setting[room_id]" value="<?php echo ! empty( $setting['room_id'] ) ? esc_attr( $setting['room_id'] ) : ''; ?>">
				<p class="description">
					<?php _e( 'Room ID in which notification will be sent to.', 'spark' ); ?>
				</p>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">
				<?php _e( 'Events to Notify', 'spark' ); ?>
			</th>
			<td>
				<?php foreach ( $events as $event => $e ) : ?>
					<?php
					$field         = "spark_setting[events][$event]";
					$default_value = ! empty( $e['default'] ) ? $e['default'] : false;
					$value         = isset( $setting['events'][ $event ] ) ? $setting['events'][ $event ] : $default_value;
					?>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" value="1" <?php checked( $value ); ?>>
						<?php echo esc_html( $e['description'] ); ?>
					</label>
					<br>
			<?php endforeach; ?>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">
				<label for="spark_setting[active]"><?php _e( 'Active', 'spark' ); ?></label>
			</th>
			<td>
				<input type="checkbox" name="spark_setting[active]" id="spark_setting[active]" <?php checked( ! empty( $setting['active'] ) ? $setting['active'] : false ); ?>>
				<p class="description">
					<?php _e( 'Notification will not be sent if not checked.', 'spark' ); ?>
				</p>
			</td>
		</tr>

		<?php if ( 'publish' === $post->post_status ) : ?>
		<tr valign="top">
			<th scope="row"></th>
			<td>
				<div id="spark-test-notify">
					<input id="spark-test-notify-nonce" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'test_notify_nonce' ) ); ?>">
					<button class="button" id="spark-test-notify-button"><?php _e( 'Test send notification with this setting.', 'spark' ); ?></button>
					<div class="spinner"></div>
				</div>
				<div id="spark-test-notify-response"></div>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
