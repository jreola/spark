<?php

class WP_Spark_Event_Payload {

    /**
     * @var array
     */
    private $setting;

    public function __construct(array $setting) {
        $this->setting = $setting;
    }

    public function get_url() {
        return get_option('service_url');
    }

    public function toJSON() {
        return json_encode(array(
            'roomId' => $this->setting['room_id'],
            'text' => $this->setting['text']
        ));
    }

}
