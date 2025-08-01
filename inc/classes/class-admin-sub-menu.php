<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Admin_Sub_Menu {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'admin_menu', [ $this, 'register_admin_sub_menu' ] );
        add_filter( 'plugin_action_links_' . PLUGIN_BASE_NAME, [ $this, 'add_plugin_action_links' ] );

        // save api credentials
        add_action( 'wp_ajax_save_credentials', [ $this, 'save_api_credentials' ] );
        add_action( 'wp_ajax_save_options', [ $this, 'save_options' ] );
    }

    public function save_api_credentials() {

        $api_url       = sanitize_text_field( $_POST['api_url'] );
        $api_key       = sanitize_text_field( $_POST['api_key'] );
        $auth_token    = sanitize_text_field( $_POST['auth_token'] );
        $client_id     = sanitize_text_field( $_POST['client_id'] );
        $client_secret = sanitize_text_field( $_POST['client_secret'] );
        $redirect_url  = sanitize_text_field( $_POST['redirect_url'] );

        if ( empty( $api_url ) || empty( $api_key ) ) {
            wp_send_json_error( 'An error occurred! Please fill all the fields.' );
        }

        update_option( 'api_url', $api_url );
        update_option( 'api_key', $api_key );
        update_option( 'auth_token', $auth_token );
        update_option( 'linkedin_client_id', $client_id );
        update_option( 'linkedin_client_secret', $client_secret );
        update_option( 'linkedin_callback_url', $redirect_url );

        wp_send_json_success( 'Credentials saved successfully!' );
        die();
    }

    public function save_options() {

        $option1 = sanitize_text_field( $_POST['option1'] );
        $option2 = sanitize_text_field( $_POST['option2'] );

        update_option( 'option1', $option1 );
        update_option( 'option2', $option2 );

        wp_send_json_success( 'Options saved successfully!' );
        die();
    }

    function add_plugin_action_links( $links ) {
        $settings_link = '<a href="admin.php?page=gli-settings">' . __( 'Settings', 'gli' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function register_admin_sub_menu() {
        add_submenu_page(
            'options-general.php',
            'GLI Settings',
            'GLI Settings',
            'manage_options',
            'gli-settings',
            [ $this, 'menu_callback_html' ],
        );
    }

    public function menu_callback_html() {
        include_once PLUGIN_BASE_PATH . '/templates/template-admin-sub-menu.php';
    }

}