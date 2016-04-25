<?php

class WP_Spark_Event_Manager {

    /**
     * @var WP_Spark_Plugin
     */
    private $plugin;

    public function __construct(WP_Spark_Plugin $plugin) {
        $this->plugin = $plugin;

        $this->dispatch_events();
    }

    private function dispatch_events() {

        $events = $this->get_events();

        // Get all integration settings.
        // @todo Adds get_posts method into post type
        // that caches the results.
        $integrations = get_posts(array(
            'post_type' => $this->plugin->post_type->name,
            'nopaging' => true,
            'posts_per_page' => -1,
        ));

        foreach ($integrations as $integration) {
            $setting = get_post_meta($integration->ID, 'spark_integration_setting', true);

            // Skip if inactive.
            if (empty($setting['active'])) {
                continue;
            }
            if (!$setting['active']) {
                continue;
            }

            if (empty($setting['events'])) {
                continue;
            }

            // For each checked event calls the callback, that's,
            // hooking into event's action-name to let notifier
            // deliver notification based on current integration
            // setting.
            foreach ($setting['events'] as $event => $is_enabled) {
                if (!empty($events[$event]) && $is_enabled) {
                    $this->notifiy_via_action($events[$event], $setting);
                }
            }
        }
    }

    /**
     * Get list of events. There's filter `spark_get_events`
     * to extend available events that can be notified to
     * Spark.
     */
    public function get_events() {
        return apply_filters('spark_get_events', array(
            'post_published' => array(
                'action' => 'transition_post_status',
                'description' => __('When a post is published', 'spark'),
                'default' => true,
                'message' => function( $new_status, $old_status, $post ) {
                    $notified_post_types = apply_filters('spark_event_transition_post_status_post_types', array(
                        'post',
                    ));

                    if (!in_array($post->post_type, $notified_post_types)) {
                        return false;
                    }

                    if ('publish' !== $old_status && 'publish' === $new_status) {
                        return sprintf(
                                'A new post has been published: %s - %s', get_the_title($post->ID), get_permalink($post->ID)
                        );
                    }
                },
                    ),
                    'post_pending_review' => array(
                        'action' => 'transition_post_status',
                        'description' => __('When a post needs to be reviewed', 'spark'),
                        'default' => false,
                        'message' => function( $new_status, $old_status, $post ) {
                            $notified_post_types = apply_filters('spark_event_transition_post_status_post_types', array(
                                'post',
                            ));

                            if (!in_array($post->post_type, $notified_post_types)) {
                                return false;
                            }

                            if ('pending' !== $old_status && 'pending' === $new_status) {
                                return sprintf(
                                        'The following post needs to be reviewed: %s - %s', get_the_title($post->ID), get_edit_post_link($post->ID)
                                );
                            }
                        },
                            ),
                            'new_comment' => array(
                                'action' => 'wp_insert_comment',
                                'priority' => 999,
                                'description' => __('When there is a new comment', 'spark'),
                                'default' => false,
                                'message' => function( $comment_id, $comment ) {
                                    $comment = is_object($comment) ? $comment : get_comment(absint($comment));
                                    $post_id = $comment->comment_post_ID;

                                    $notified_post_types = apply_filters('spark_event_wp_insert_comment_post_types', array(
                                        'post',
                                    ));

                                    if (!in_array(get_post_type($post_id), $notified_post_types)) {
                                        return false;
                                    }

                                    $post_title = get_the_title($post_id);
                                    $comment_status = wp_get_comment_status($comment_id);

                                    // Ignore spam.
                                    if ('approved' == $comment_status) {
                                        return sprintf(
                                                'A new comment has been added to: %s - %s', get_the_title($post_id), get_permalink($post_id) . '#comment-' . $comment_id
                                        );
                                    } else if ('unapproved' == $comment_status) {
                                        return sprintf(
                                                'A new comment is pending approval: %s - %s', $post_title, get_admin_url() . "/comment.php?action=editcomment&c=$comment_id"
                                        );
                                    }
                                },
                                    ),
                                    'comment_pending_review' => array(
                                        'action' => 'transition_comment_status',
                                        'description' => __('When a comment is pending', 'spark'),
                                        'default' => false,
                                        'message' => function( $new_status, $old_status, $comment ) {
                                            $comment_id = $comment->comment_ID;
                                            $post_id = $comment->comment_post_ID;
                                            $notified_post_types = apply_filters('spark_event_transition_comment_status_post_types', array(
                                                'post',
                                            ));

                                            if (!in_array(get_post_type($post_id), $notified_post_types)) {
                                                return false;
                                            }

                                            if ('unapproved' === $old_status && 'approved' === $new_status) {
                                                return sprintf(
                                                        'A new comment has been published: %s - %s', get_the_title($post_id), get_permalink($post_id) . '#comment-' . $comment_id
                                                );
                                            }
                                        },
                                            )
                                        ));
                                    }

                                    public function notifiy_via_action(array $event, array $setting) {
                                        $notifier = $this->plugin->notifier;

                                        $priority = 10;
                                        if (!empty($event['priority'])) {
                                            $priority = intval($event['priority']);
                                        }

                                        $callback = function() use( $event, $setting, $notifier ) {
                                            $message = '';
                                            if (is_string($event['message'])) {
                                                $message = $event['message'];
                                            } else if (is_callable($event['message'])) {
                                                $message = call_user_func_array($event['message'], func_get_args());
                                            }

                                            if (!empty($message)) {
                                                $setting = wp_parse_args(
                                                        array(
                                                    'text' => $message,
                                                        ), $setting
                                                );

                                                $notifier->notify(new WP_Spark_Event_Payload($setting));
                                            }
                                        };
                                        add_action($event['action'], $callback, $priority, 5);
                                    }

                                }
                                