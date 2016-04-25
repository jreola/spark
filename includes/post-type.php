<?php

class WP_Spark_Post_Type {

    /**
     * Post type name.
     *
     * @var string
     */
    public $name = 'spark_integration';

    /**
     * @var WP_Spark_Plugin
     */
    private $plugin;

    public function __construct(WP_Spark_Plugin $plugin) {
        $this->plugin = $plugin;

        //Enable setting menu page
        add_action('admin_menu', array($this, 'enable_setting_pages'));

        // Register custom post type to store Spark integration records.
        add_action('init', array($this, 'register_post_type'));

        // Removes builtin submitdiv meta box.
        add_action('admin_menu', array($this, 'remove_submitdiv'));

        // Enqueue scripts/styles and disables autosave for this post type.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Alters message when post is updated.
        add_filter('post_updated_messages', array($this, 'post_updated_messages'));

        // Alters messages when bulk updating.
        add_filter('bulk_post_updated_messages', array($this, 'bulk_post_updated_messages'), 10, 2);

        // Custom bulk actions.
        add_filter('bulk_actions-edit-spark_integration', array($this, 'custom_bulk_actions'));

        // Custom row actions.
        add_filter('post_row_actions', array($this, 'custom_row_actions'), 10, 2);

        // Activate and deactivate actions.
        add_action('admin_action_activate', array($this, 'activate'));
        add_action('admin_action_deactivate', array($this, 'deactivate'));

        // Add notices for activate/deactivate actions.
        add_action('all_admin_notices', array($this, 'admin_notices'));

        // Custom columns
        add_filter(sprintf('manage_%s_posts_columns', $this->name), array($this, 'columns_header'));
        add_action(sprintf('manage_%s_posts_custom_column', $this->name), array($this, 'custom_column_row'), 10, 2);

        // Alter post class in admin to notice whether setting is activated or not.
        add_filter('post_class', array($this, 'post_class'), 10, 3);

        // Hides subsub top navigation.
        add_filter('views_edit-' . $this->name, array($this, 'hide_subsubsub'));

        // Alters title placeholder.
        add_filter('enter_title_here', array($this, 'title_placeholder'));

        if (empty(get_option('service_url')) || empty(get_option('accesstoken'))) {
            add_action('admin_notices', array($this, 'admin_notice'));
        }
    }

    public function admin_notice() {
        ?>
        <div class="notice error spark-notice" >
            <p><?php _e('Please update spark settings to send notification!', 'spark'); ?></p>
        </div>
        <?php
    }

    public function register_post_type() {
        $args = array(
            'description' => '',
            'public' => false,
            'publicly_queryable' => false,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 75, // Below tools
            'menu_icon' => $this->plugin->plugin_url . 'img/logo.png',
            'can_export' => true,
            'delete_with_user' => true,
            'hierarchical' => false,
            'has_archive' => false,
            'query_var' => false,
            'map_meta_cap' => false,
            'capabilities' => array(
                // meta caps (don't assign these to roles)
                'edit_post' => 'manage_options',
                'read_post' => 'manage_options',
                'delete_post' => 'manage_options',
                // primitive/meta caps
                'create_posts' => 'manage_options',
                // primitive caps used outside of map_meta_cap()
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
                // primitive caps used inside of map_meta_cap()
                'read' => 'manage_options',
                'delete_posts' => 'manage_options',
                'delete_private_posts' => 'manage_options',
                'delete_published_posts' => 'manage_options',
                'delete_others_posts' => 'manage_options',
                'edit_private_posts' => 'manage_options',
                'edit_published_posts' => 'manage_options',
            ),
            'rewrite' => false,
            // What features the post type supports.
            'supports' => array(
                'title',
            ),
            'labels' => array(
                'name' => __('Spark Integration', 'spark'),
                'singular_name' => __('Spark Integration', 'spark'),
                'menu_name' => __('Spark', 'spark'),
                'name_admin_bar' => __('Spark', 'spark'),
                'add_new' => __('Add New', 'spark'),
                'add_new_item' => __('Add New Spark Integration', 'spark'),
                'edit_item' => __('Edit Spark Integration', 'spark'),
                'new_item' => __('New Spark Integration', 'spark'),
                'view_item' => __('View Spark Integration', 'spark'),
                'search_items' => __('Search Spark Integration', 'spark'),
                'not_found' => __('No spark integration found', 'spark'),
                'not_found_in_trash' => __('No spark integration in trash', 'spark'),
                'all_items' => __('Spark Integrations', 'spark'),
            ),
        );

        // Register the post type.
        register_post_type($this->name, $args);
    }

    public function remove_submitdiv() {
        remove_meta_box('submitdiv', $this->name, 'side');
    }

    public function enqueue_scripts() {
        if ($this->name === get_post_type()) {
            wp_dequeue_script('autosave');

            wp_enqueue_style(
                    // Handle.
                    'spark-admin',
                    // Src.
                    $this->plugin->plugin_url . 'css/admin.css',
                    // Deps
                    array(),
                    // Version.
                    filemtime($this->plugin->plugin_path . 'css/admin.css'),
                    // Media.
                    'all'
            );

            wp_enqueue_script(
                    // Handle.
                    'spark-admin-js',
                    // Src.
                    $this->plugin->plugin_url . 'js/admin.js',
                    // Deps
                    array('jquery'),
                    // Ver.
                    filemtime($this->plugin->plugin_path . 'js/admin.js')
            );
        }
    }

    public function post_updated_messages($messages) {
        $messages[$this->plugin->post_type->name] = array_fill(0, 11, __('Setting updated.', 'spark'));

        return $messages;
    }

    /**
     * @param array $bulk_messages Arrays of messages, each keyed by the corresponding post type. Messages are
     *                             keyed with 'updated', 'locked', 'deleted', 'trashed', and 'untrashed'.
     * @param array $bulk_counts   Array of item counts for each message, used to build internationalized strings.
     */
    public function bulk_post_updated_messages($bulk_messages, $bulk_counts) {
        $screen = get_current_screen();

        if ($this->name === $screen->post_type) {
            $bulk_messages['post'] = array(
                'updated' => _n('%s integration updated.', '%s integrations updated.', $bulk_counts['updated']),
                'locked' => _n('%s integration not updated, somebody is editing it.', '%s integrations not updated, somebody is editing them.', $bulk_counts['locked']),
                'deleted' => _n('%s integration permanently deleted.', '%s integrations permanently deleted.', $bulk_counts['deleted']),
                'trashed' => _n('%s integration moved to the Trash.', '%s integrations moved to the Trash.', $bulk_counts['trashed']),
                'untrashed' => _n('%s integration restored from the Trash.', '%s integrations restored from the Trash.', $bulk_counts['untrashed']),
            );
        }

        return $bulk_messages;
    }

    /**
     * Custom bulk actions.
     *
     * @param  array $actions
     * @return array
     *
     * @filter bulk_actions-edit-spark_integration
     */
    public function custom_bulk_actions($actions) {
        unset($actions['edit']);

        // Unfortunately adding bulk actions won't work here.
        // @see https://core.trac.wordpress.org/ticket/16031
        //
		// $actions['activate']   = __( 'Activate', 'spark' );
        // $actions['deactivate'] = __( 'Deactivate', 'spark' );

        return $actions;
    }

    /**
     * Custom row actions for this post type.
     *
     * @param  array $actions
     * @return array
     *
     * @filter post_row_actions
     */
    public function custom_row_actions($actions) {
        $post = get_post();

        if ($this->plugin->post_type->name === get_post_type($post)) {
            unset($actions['inline hide-if-no-js']);
            unset($actions['view']);

            $setting = get_post_meta($post->ID, 'spark_integration_setting', true);
            $post_type_object = get_post_type_object($post->post_type);

            if ($setting['active']) {
                $actions['deactivate'] = "<a title='" . esc_attr(__('Deactivate this integration setting')) . "' href='" . wp_nonce_url(admin_url(sprintf($post_type_object->_edit_link . '&amp;action=deactivate', $post->ID)), 'deactivate-post_' . $post->ID) . "'>" . __('Deactivate') . "</a>";
            } else {
                $actions['activate'] = "<a title='" . esc_attr(__('Activate this integration setting')) . "' href='" . wp_nonce_url(admin_url(sprintf($post_type_object->_edit_link . '&amp;action=activate', $post->ID)), 'activate-post_' . $post->ID) . "'>" . __('Activate') . "</a>";
            }
        }

        return $actions;
    }

    /**
     * Activates the integration.
     *
     * @action admin_action_{action}
     */
    public function activate() {
        $this->_set_active_setting();
    }

    /**
     * Deactivates the integration.
     *
     * @action admin_action_{action}
     */
    public function deactivate() {
        $this->_set_active_setting(false);
    }

    /**
     * Action handler for activating/deactivating integration setting(s).
     *
     * @param bool $activate
     */
    private function _set_active_setting($activate = true) {
        $screen = get_current_screen();
        if ($screen->id !== $this->name) {
            return;
        }

        $post = !empty($_REQUEST['post']) ? get_post($_REQUEST['post']) : null;
        if (!$post) {
            wp_die(
                    sprintf(__('The integration you are trying to %s is no longer exists.', 'spark'), $activate ? 'activate' : 'deactivate' )
            );
        }

        check_admin_referer(sprintf('%s-post_%d', $activate ? 'activate' : 'deactivate', $post->ID));

        $sendback = admin_url('edit.php?post_type=' . $this->name);
        $setting = get_post_meta($post->ID, 'spark_integration_setting', true);
        $setting['active'] = $activate;

        update_post_meta($post->ID, 'spark_integration_setting', $setting);

        $key_arg = $activate ? 'activated' : 'deactivated';

        wp_redirect(add_query_arg(array("$key_arg" => 1, 'ids' => $post->ID), $sendback));
        exit;
    }

    /**
     *
     * @action all_admin_notices
     */
    public function admin_notices() {
        $screen = get_current_screen();
        if ($screen->id !== 'edit-' . $this->name) {
            return;
        }

        $bulk_counts = array(
            'activated' => isset($_REQUEST['activated']) ? absint($_REQUEST['activated']) : 0,
            'deactivated' => isset($_REQUEST['deactivated']) ? absint($_REQUEST['deactivated']) : 0,
        );

        $bulk_messages = array(
            'activated' => _n('%s integration activated.', '%s integrations activated.', $bulk_counts['activated']),
            'deactivated' => _n('%s integration deactivated.', '%s integrations deactivated.', $bulk_counts['deactivated']),
        );

        $bulk_counts = array_filter($bulk_counts);

        // If we have a bulk message to issue:
        $messages = array();
        foreach ($bulk_counts as $message => $count) {
            if (isset($bulk_messages[$message])) {
                $messages[] = sprintf($bulk_messages[$message], number_format_i18n($count));
            }
        }

        if ($messages) {
            echo '<div id="message" class="updated"><p>' . join(' ', $messages) . '</p></div>';
        }
    }

    /**
     * Custom columns for this post type.
     *
     * @param  array $columns
     * @return array
     *
     * @filter manage_{post_type}_posts_columns
     */
    public function columns_header($columns) {
        unset($columns['date']);
        $columns['room_id'] = __('Room ID', 'spark');
        $columns['events'] = __('Notified Events', 'spark');

        return $columns;
    }

    /**
     * Custom column appears in each row.
     *
     * @param string $column  Column name
     * @param int    $post_id Post ID
     *
     * @action manage_{post_type}_posts_custom_column
     */
    public function custom_column_row($column, $post_id) {
        $setting = get_post_meta($post_id, 'spark_integration_setting', true);
        switch ($column) {
            case 'room_id':
                echo!empty($setting['room_id']) ? $setting['room_id'] : '';
                break;
            case 'events':
                $events = $this->plugin->event_manager->get_events();

                if (!empty($setting['events'])) {
                    echo '<ul>';
                    foreach ($setting['events'] as $event => $enabled) {
                        if ($enabled && !empty($events[$event])) {
                            printf('<li>%s</li>', esc_html($events[$event]['description']));
                        }
                    }
                    echo '</ul>';
                }
                break;
        }
    }

    /**
     * Alter post class in list table to notice whether setting is activated or not.
     *
     * @param array  $classes An array of post classes.
     * @param string $class   A comma-separated list of additional classes added to the post.
     * @param int    $post_id The post ID.
     *
     * @filter post_class
     */
    public function post_class($classes, $class, $post_id) {
        if (!is_admin()) {
            return $classes;
        }

        $screen = get_current_screen();
        if (sprintf('edit-%s', $this->name) !== $screen->id) {
            return $classes;
        }

        $setting = get_post_meta($post_id, 'spark_integration_setting', true);
        if (!$setting['active']) {
            $classes[] = 'inactive';
        } else {
            $classes[] = 'active';
        }

        return $classes;
    }

    /**
     * Hides subsubsub top nav.
     *
     * @return array
     */
    public function hide_subsubsub() {
        return array();
    }

    public function title_placeholder($title) {
        $screen = get_current_screen();

        if ($this->name === $screen->post_type) {
            $title = __('Integration Name', 'spark');
        }

        return $title;
    }

    public function enable_setting_pages() {
        add_submenu_page('edit.php?post_type=' . $this->name, __('Spark Settings', 'spark'), __('Settings', 'spark'), 'edit_posts', basename(__FILE__), array($this, 'global_options'));
        add_action('admin_init', array($this, 'register_spark_settings'));
    }

    public function register_spark_settings() {
        register_setting('spark_settings', 'service_url');
        register_setting('spark_settings', 'accesstoken');
    }

    public function global_options() {
        ?>
        <div class="wrap">
            <h2><?php _e('Spark Settings', 'spark') ?></h2>
            <?php settings_errors(); ?>
            <form action="options.php" method="post">
                <?php settings_fields('spark_settings'); ?>
                <?php do_settings_sections('spark_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo __('Service URL', 'spark'); ?></th>
                        <td><input class="regular-text" type="text" name="service_url" value="<?php echo get_option('service_url'); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo __('Access Token', 'spark'); ?></th>
                        <td><input class="regular-text" type="text" name="accesstoken" value="<?php echo get_option('accesstoken'); ?>" /></td>
                    </tr>
                </table>
                <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes', 'spark'); ?>" class="button button-primary" />
            </form>
        </div>
        <?php
    }

}
