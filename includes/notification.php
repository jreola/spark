<?php

class WP_Spark_Notification {

    /**
     * @var WP_Spark_Plugin
     */
    private $plugin;

    public function __construct(WP_Spark_Plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Notify Spark with given payload.
     *
     * @var WP_Spark_Event_Payload $payload
     *
     * @return mixed True if success, otherwise WP_Error
     */
    public function notify(WP_Spark_Event_Payload $payload) {
        $payload_json = $payload->toJSON();
        $args = array(
            'user-agent' => $this->plugin->name . '/' . $this->plugin->version,
            'body' => $payload_json,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . get_option('accesstoken')
            ),
        );
        $resp = wp_remote_post($payload->get_url(), $args);
        if (is_wp_error($resp)) {
            return $resp;
        } else {
            $status = intval(wp_remote_retrieve_response_code($resp));
            $message = wp_remote_retrieve_body($resp);
            if (200 !== $status) {
                return new WP_Error('spark_unexpected_response', $message);
            }

            return $resp;
        }
    }

}
