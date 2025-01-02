<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Share_In_Linkedin {

    use Singleton;
    use Program_Logs;

    private $client_id;
    private $client_secret;
    private $callback_url;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {

        // get api credentials
        $this->client_id     = get_option( 'linkedin_client_id', '86jkj8psufudby' );
        $this->client_secret = get_option( 'linkedin_client_secret', 'WPL_AP1.LtZcMQvYpaNWBm9K.qZgNRw==' );
        $this->callback_url  = get_option( 'linkedin_callback_url', 'https://imjol.com' );

        add_filter( 'the_content', [ $this, 'add_social_share_buttons' ] );

        // handle ajax request
        add_action( 'wp_ajax_share_on_linkedin', [ $this, 'share_on_linkedin' ] );
        add_action( 'wp_ajax_nopriv_share_on_linkedin', [ $this, 'share_on_linkedin' ] );

        // handle ajax request
        add_action( 'wp_ajax_check_user_logged_in', [ $this, 'check_user_logged_in' ] );
        add_action( 'wp_ajax_nopriv_check_user_logged_in', [ $this, 'check_user_logged_in' ] );

        // handle ajax request
        add_action( 'wp_ajax_sign_in_with_linkedin', [ $this, 'sign_in_with_linkedin' ] );
        add_action( 'wp_ajax_nopriv_sign_in_with_linkedin', [ $this, 'sign_in_with_linkedin' ] );
    }

    public function check_user_logged_in() {

        // get current user id
        $user_id = get_current_user_id();
        // get is user linkedin logged in
        $is_linkedin_logged_in = get_user_meta( $user_id, 'is_linkedin_logged_in', true );

        wp_send_json( [
            'is_logged_in' => $is_linkedin_logged_in,
        ] );
    }

    public function add_social_share_buttons( $content ) {
        if ( is_single() && in_the_loop() && is_main_query() ) {
            // HTML for the social buttons
            $social_buttons = "
            <div class='gli-social-share-buttons'>

                <a href='JavaScript:void(0)' id='gli-share-linkedin' data-post-url='" . get_permalink() . "' data-post-title='" . get_the_title() . "' class='btn btn-linkedin'>LinkedIn</a>

                <div id='gli-share-linkedin-popup' style='display:none;'>
                    <p>What do you want to talk about?</p>
                    <input type='text' id='gli-share-linkedin-popup-input' placeholder='What do you want to talk about?'>

                    <button type='button' id='gli-share-linkedin-popup-close' class='btn btn-linkedin'>Close</button>
                    <button type='button' id='gli-share-linkedin-popup-share' class='btn btn-linkedin'>
                    <span>Share</span>
                    <span class='spinner-loader-wrapper'></span>
                    </button>
                </div>

                <div id='gli-sign-in-with-linkedin' style='display:none;'>
                    <p>You need to sign in first to share on LinkedIn</p>
                    <a id='gli-sign-in-with-linkedin-button' href='https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=$this->client_id&redirect_uri=$this->callback_url&scope=openid%20profile%20w_member_social' class='btn btn-linkedin'>Login with LinkedIn</a>
                    <button type='button' id='gli-sign-in-with-linkedin-popup-close' class='btn btn-linkedin'>Close</button>
                </div>

            </div>";

            // Append the buttons to the post content
            return $content . $social_buttons;
        }

        // Return the original content if not a single post
        return $content;
    }

    public function share_on_linkedin() {
        // check nonce
        check_ajax_referer( 'wpb_public_nonce', 'nonce' );

        // get data
        $predefined_url = sanitize_text_field( $_POST['predefined_url'] );
        $post_title     = sanitize_text_field( $_POST['post_title'] );
        $prompt_value   = sanitize_text_field( $_POST['input_prompt_value'] );

        // check if data is empty
        if ( empty( $predefined_url ) || empty( $post_title ) || empty( $prompt_value ) ) {
            wp_send_json_error( 'An error occurred! Please fill all the fields.' );
        }

        // Call the share function
        $response = $this->share_post_on_linkedin( $predefined_url, $post_title, $prompt_value );

        // Send JSON response based on result
        if ( $response['status'] === 'success' ) {
            wp_send_json_success( $response['message'] );
        } else {
            wp_send_json_error( $response['message'] );
        }
    }

    public function share_post_on_linkedin( string $predefined_url, string $post_title, string $post_content ) {
        $url          = get_option( 'api_url', 'https://api.linkedin.com/v2/ugcPosts' );
        $access_token = get_option( 'api_key', '' );
        $urn          = "JyqN44xm9_";

        if ( empty( $access_token ) ) {
            return [
                'status'  => 'error',
                'message' => 'Access token is missing. Please configure the plugin.',
            ];
        }

        $post_data = [
            "author"          => "urn:li:person:$urn",
            "lifecycleState"  => "PUBLISHED",
            "specificContent" => [
                "com.linkedin.ugc.ShareContent" => [
                    "shareCommentary"    => [
                        "text" => $post_content,
                    ],
                    "shareMediaCategory" => "ARTICLE",
                    "media"              => [
                        [
                            "status"      => "READY",
                            "originalUrl" => $predefined_url,
                            "title"       => [
                                "text" => $post_title,
                            ],
                        ],
                    ],
                ],
            ],
            "visibility"      => [
                "com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC",
            ],
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $post_data ),
            'timeout' => 120,
        ] );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $this->put_program_logs( "LinkedIn Request Error: $error_message" );
            return [
                'status'  => 'error',
                'message' => $error_message,
            ];
        }

        $status_code  = wp_remote_retrieve_response_code( $response );
        $body         = wp_remote_retrieve_body( $response );
        $decoded_body = json_decode( $body, true );

        $this->put_program_logs( "LinkedIn Response: " . json_encode( $decoded_body ) );

        if ( $status_code === 201 && isset( $decoded_body['id'] ) ) {
            // Award points if successful
            $this->award_point_to_user();
            return [
                'status'  => 'success',
                'message' => 'Post shared successfully!',
            ];
        }

        return [
            'status'  => 'error',
            'message' => 'Failed to share the post on LinkedIn.',
        ];
    }

    public function award_point_to_user() {

        $base_url             = site_url() . '/wp-json';
        $url                  = "$base_url/wp/v2/gamipress/award-points";
        $gamipress_auth_token = get_option( 'auth_token' );
        $user_id              = get_current_user_id();
        $point                = 10;

        $this->put_program_logs( 'current user id: ' . $user_id );

        $data = [
            'user'        => $user_id,
            'points'      => $point,
            'points_type' => 'coins',
            'reason'      => '10 monedas por Linkedin',
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Basic ' . $gamipress_auth_token,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => $data,
            'timeout' => 120,
        ] );

        // Check for errors
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            $this->put_program_logs( "Gamipress Request Error: $error_message" );
            return [
                'status'  => 'error',
                'message' => $error_message,
            ];
        }

        // Extract response body and status code
        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        $this->put_program_logs( "Gamipress Status Code: $status_code" );
        $this->put_program_logs( "Gamipress Response: $body" );

        if ( '200' === $status_code ) {
            wp_send_json( [
                "status"  => "success",
                "message" => "You earned $point points!",
                "body"    => $body,
            ] );
        }
    }

    public function sign_in_with_linkedin() {
        // get current use id
        $user_id = get_current_user_id();

        // update user meta
        update_user_meta( $user_id, 'test_token', '123456789' );

        $this->put_program_logs( 'current user id: ' . $user_id );
    }

}