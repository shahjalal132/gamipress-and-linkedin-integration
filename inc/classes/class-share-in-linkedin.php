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
        $this->client_id     = get_option( 'linkedin_client_id', '' );
        $this->client_secret = get_option( 'linkedin_client_secret', '' );
        $this->callback_url  = get_option( 'linkedin_callback_url', '' );

        add_filter( 'the_content', [ $this, 'add_social_share_buttons' ] );

        // handle ajax request
        add_action( 'wp_ajax_share_on_linkedin', [ $this, 'share_on_linkedin' ] );
        add_action( 'wp_ajax_nopriv_share_on_linkedin', [ $this, 'share_on_linkedin' ] );

        // handle ajax request
        add_action( 'wp_ajax_check_user_logged_in', [ $this, 'check_user_logged_in' ] );
        add_action( 'wp_ajax_nopriv_check_user_logged_in', [ $this, 'check_user_logged_in' ] );

        // get ?code for login with linkedin
        add_action( 'wp_loaded', [ $this, 'get_linkedin_auth_code' ] );

        // TODO: update is_linkedin_logged_in to no after 60 days

    }

    public function get_linkedin_auth_code() {

        // get current user id
        $current_user_id = get_current_user_id();

        // Check if this is the specific callback URL and has the 'code' query param
        if ( isset( $_GET['code'] ) && strpos( $_SERVER['REQUEST_URI'], '/linkedin-callback' ) !== false ) {

            // Store the auth code
            $auth_code = sanitize_text_field( $_GET['code'] );

            if ( !empty( $auth_code ) ) {

                // Update user meta
                update_user_meta( $current_user_id, 'linkedin_auth_code', $auth_code );

                // Get access token for this user
                $this->get_linkedin_access_token( $auth_code );

                // get current post url from cookie
                $cookie_key = 'gli_current_post_url';
                if ( isset( $_COOKIE[$cookie_key] ) ) {
                    $current_post_url = sanitize_text_field( $_COOKIE[$cookie_key] );
                    $this->put_program_logs( 'Current Post URL: ' . $current_post_url );
                    if ( !empty( $current_post_url ) ) {
                        // redirect to current post url
                        wp_redirect( $current_post_url );
                        exit();
                    }
                }
            }
        } else {
            // $this->put_program_logs( 'No auth code found' );
        }
    }

    public function get_linkedin_access_token( string $auth_code ) {

        // get current user id
        $current_user_id = get_current_user_id();

        // access token url
        $url = "https://www.linkedin.com/oauth/v2/accessToken";

        $params = [
            'grant_type'    => 'authorization_code',
            'code'          => $auth_code,
            'redirect_uri'  => $this->callback_url,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ];

        // Use wp_remote_post to make the HTTP POST request
        $response = wp_remote_post( $url, [
            'body'      => $params,
            'timeout'   => 120,
            'sslverify' => false,
        ] );

        // log access token response
        // $this->put_program_logs( 'Linkedin Access Token Response: ' . json_encode( $response ) );
        update_user_meta( $current_user_id, 'linkedin_access_token_response', json_encode( $response ) );

        // Check for errors
        if ( is_wp_error( $response ) ) {

            // get error message
            $error_message = $response->get_error_message();
            // $this->put_program_logs( "Linkedin Access Token Request Error: $error_message" );

            // update error to user meta
            update_user_meta( $current_user_id, 'linkedin_access_token_error', $error_message );
        } else {

            // get status code
            $status_code = wp_remote_retrieve_response_code( $response );
            // get response body
            $body = wp_remote_retrieve_body( $response );

            // check status code
            if ( $status_code == 200 ) {

                // decode response body
                $body = json_decode( $body );

                // get access token
                $access_token = $body->access_token;

                // log access token
                // $this->put_program_logs( 'Linkedin Access Token: ' . $access_token );

                // update user meta
                update_user_meta( $current_user_id, 'linkedin_access_token', $access_token );

                // get user info
                $this->get_linkedin_user_info( $access_token );

                // update is_linkedin_logged_in to yes
                update_user_meta( $current_user_id, 'is_linkedin_logged_in', 'yes' );

            }

        }
    }

    function get_linkedin_user_info( string $access_token ) {

        // get current user id
        $current_user_id = get_current_user_id();

        // Get user info URL
        $url = "https://api.linkedin.com/v2/userinfo";

        // Make a GET request using wp_remote_get
        $response = wp_remote_get( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'timeout' => 120,
        ] );

        // log user info response
        // $this->put_program_logs( 'Linkedin User Info Response: ' . json_encode( $response ) );
        update_user_meta( $current_user_id, 'linkedin_user_info_response', json_encode( $response ) );

        // Check for errors
        if ( is_wp_error( $response ) ) {
            $user_info_error = $response->get_error_message();
            // $this->put_program_logs( "Linkedin User Info Request Error: $user_info_error" );
            // update error to user meta
            update_user_meta( $current_user_id, 'linkedin_user_info_error', $user_info_error );
            return;
        }

        // Retrieve the response body and decode JSON
        $body = wp_remote_retrieve_body( $response );
        // get sub
        $urn = json_decode( $body )->sub;
        // $this->put_program_logs( 'Linkedin URN: ' . $urn );

        if ( !empty( $urn ) ) {
            // update user meta
            update_user_meta( $current_user_id, 'linkedin_urn', $urn );
            return "url get success";
        }
    }

    public function check_user_logged_in() {

        // get current user id
        $current_user_id = get_current_user_id();

        // get is user linkedin logged in
        $is_linkedin_logged_in = get_user_meta( $current_user_id, 'is_linkedin_logged_in', true );

        wp_send_json( [
            'is_logged_in' => $is_linkedin_logged_in,
        ] );
    }

    public function add_social_share_buttons( $content ) {
        if ( is_single() && in_the_loop() && is_main_query() ) {
            // HTML for the social buttons
            $social_buttons = "
            <div class='gli-social-share-buttons'>

                <a href='JavaScript:void(0)' id='gli-share-linkedin' data-post-url='" . get_permalink() . "' data-post-title='" . get_the_title() . "' data-post-content='" . get_the_content() . "' class='btn btn-linkedin'>LinkedIn</a>

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
                    <a id='gli-sign-in-with-linkedin-button' href='https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=$this->client_id&redirect_uri=$this->callback_url&scope=openid%20profile%20w_member_social' class='btn btn-linkedin' target='_blank'>Sign In with LinkedIn</a>
                    <button type='button' id='gli-sign-in-with-linkedin-popup-close' class='btn btn-linkedin'>Close</button>
                </div>

                <div id='toast-container' ></div>

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
        $image_url      = sanitize_text_field( $_POST['image_url'] );

        // check if data is empty
        if ( empty( $predefined_url ) || empty( $post_title ) || empty( $prompt_value ) ) {
            wp_send_json_error( 'An error occurred! Please fill all the fields.' );
        }

        // Call the share function
        $response = $this->share_post_on_linkedin( $predefined_url, $post_title, $prompt_value, $image_url );

        // Send JSON response based on result
        if ( $response['status'] === 'success' ) {
            wp_send_json_success( $response['message'] );
        } else {
            wp_send_json_error( $response['message'] );
        }
    }
    public function share_post_on_linkedin( string $predefined_url, string $post_title, string $post_content, string $image_url ) {

        // get current user id
        $current_user_id = get_current_user_id();

        $url          = get_option( 'api_url', 'https://api.linkedin.com/v2/ugcPosts' );
        $access_token = get_user_meta( $current_user_id, 'linkedin_access_token', true );
        $urn          = get_user_meta( $current_user_id, 'linkedin_urn', true );

        if ( empty( $access_token ) ) {
            return [
                'status'  => 'error',
                'message' => 'Access token is missing. Please configure the plugin.',
            ];
        }

        $this->put_program_logs( 'Reached Here before upload image to linkedin' );

        // upload image to linkedin and get asset id
        $asset_id = $this->upload_image_to_linkedin_get_asset_id( $access_token, $image_url, $urn );
        $this->put_program_logs( 'asset id: ' . $asset_id );

        // Clean the post content (strip unsupported HTML)
        $clean_content = wp_strip_all_tags( $post_content );

        // Save the clean content in an option
        // update_option( 'clean_post_content', $clean_content );

        // Clean the post title (strip unsupported HTML)
        $clean_title = wp_strip_all_tags( $post_title );

        // Save the clean title in an option
        // update_option( 'clean_post_title', $clean_title );

        // Prepare the post data without image
        /* $post_data = [
            "author"          => "urn:li:person:$urn",
            "lifecycleState"  => "PUBLISHED",
            "specificContent" => [
                "com.linkedin.ugc.ShareContent" => [
                    "shareCommentary"    => [
                        "text" => $clean_content,
                    ],
                    "shareMediaCategory" => "ARTICLE",
                    "media"              => [
                        [
                            "status"      => "READY",
                            "originalUrl" => $predefined_url,
                            "title"       => [
                                "text" => $clean_title,
                            ],
                        ],
                    ],
                ],
            ],
            "visibility"      => [
                "com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC",
            ],
        ]; */

        // Prepare the post data with image
        $post_data = [
            "author"          => "urn:li:person:$urn",
            "lifecycleState"  => "PUBLISHED",
            "specificContent" => [
                "com.linkedin.ugc.ShareContent" => [
                    "shareCommentary"    => [
                        "text" => $clean_content,
                    ],
                    "shareMediaCategory" => "IMAGE",
                    "media"              => [
                        [
                            "status"      => "READY",
                            "description" => [
                                "text" => "$post_title",
                            ],
                            "media"       => $asset_id,
                            "title"       => [
                                "text" => "$post_title",
                            ],
                        ],
                    ],
                ],
            ],
            "visibility"      => [
                "com.linkedin.ugc.MemberNetworkVisibility" => "PUBLIC",
            ],
        ];

        // prepare headers for without image post
        /* $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
        ]; */

        // prepare headers for with image post
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type'  => 'application/json',
            "X-Restli-Protocol-Version: 2.0.0",
        ];

        $response = wp_remote_post( $url, [
            'headers' => $headers,
            'body'    => wp_json_encode( $post_data ),
            'timeout' => 120,
        ] );

        // log response
        // $this->put_program_logs( "LinkedIn Response: " . json_encode( $response ) );

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

        if ( $status_code === 201 && isset( $decoded_body['id'] ) ) {
            // Award points if successful
            $this->award_point_to_user();
            return [
                'status'  => 'success',
                'message' => 'Post shared successfully!',
            ];
        } else if ( $status_code === 401 ) {
            // update user meta linkedin login no
            update_user_meta( $current_user_id, 'is_linkedin_logged_in', 'no' );
        }

        return [
            'status'  => 'error',
            'message' => 'Failed to share the post on LinkedIn.',
        ];
    }

    public function upload_image_to_linkedin_get_asset_id( string $access_token, string $image_url, string $urn ) {

        // LinkedIn API URL for registering image upload
        $register_upload_url = "https://api.linkedin.com/v2/assets?action=registerUpload";

        // Step 1: Register the image upload
        $headers = [
            "Authorization"             => "Bearer $access_token",
            "Content-Type"              => "application/json",
            "X-Restli-Protocol-Version" => "2.0.0",
        ];

        $body = [
            "registerUploadRequest" => [
                "recipes"              => [ "urn:li:digitalmediaRecipe:feedshare-image" ],
                "owner"                => "urn:li:person:$urn",
                "serviceRelationships" => [
                    [
                        "relationshipType" => "OWNER",
                        "identifier"       => "urn:li:userGeneratedContent",
                    ],
                ],
            ],
        ];

        // Send POST request to register the image upload
        $response = wp_remote_post( $register_upload_url, [
            "headers" => $headers,
            "body"    => json_encode( $body ),
        ] );

        // Log the response
        // $this->put_program_logs( "LinkedIn Upload Image Response: " . $response );

        if ( is_wp_error( $response ) ) {
            // $this->put_program_logs( "LinkedIn Upload Image Request Error: " . $response->get_error_message() );
            return new WP_Error( "linkedin_register_error", "Error registering image upload: " . $response->get_error_message() );
        }

        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( !isset( $response_body['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ) ) {
            // $this->put_program_logs( "LinkedIn Upload Image Response: " . json_encode( $response_body ) );
            return new WP_Error( "linkedin_upload_url_error", "Failed to retrieve the upload URL." );
        }

        $upload_url = $response_body['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $asset_id   = $response_body['value']['asset'];

        // static image url 
        $_image_url = "https://test.courselms.com/wp-content/uploads/2022/01/asdadasda-836x1024.jpg";

        // Step 2: Upload the image
        $image_data = file_get_contents( $_image_url );
        if ( !$image_data ) {
            // $this->put_program_logs( "Unable to read the image file." );
            return new WP_Error( "image_read_error", "Unable to read the image file." );
        }

        $upload_response = wp_remote_post( $upload_url, [
            "headers" => [
                "Authorization" => "Bearer $access_token",
                "Content-Type"  => "image/jpeg",
            ],
            "body"    => $image_data,
        ] );

        if ( is_wp_error( $upload_response ) ) {
            // $this->put_program_logs( "LinkedIn Upload Image Request Error: " . $upload_response->get_error_message() );
            return new WP_Error( "linkedin_image_upload_error", "Error uploading the image: " . $upload_response->get_error_message() );
        }

        $upload_response_code = wp_remote_retrieve_response_code( $upload_response );
        if ( $upload_response_code !== 201 ) { // LinkedIn expects a 201 response for a successful upload
            // $this->put_program_logs( "LinkedIn Upload Image Response: " . json_encode( $upload_response ) );
            return new WP_Error( "linkedin_image_upload_failed", "Image upload failed. Response code: " . $upload_response_code );
        }

        // Return the asset ID for the uploaded image
        return $asset_id;
    }

    public function award_point_to_user() {

        $base_url             = site_url() . '/wp-json';
        $url                  = "$base_url/wp/v2/gamipress/award-points";
        $gamipress_auth_token = get_option( 'auth_token' );
        $current_user_id      = get_current_user_id();
        $point                = 10;

        // $this->put_program_logs( 'current user id: ' . $current_user_id );

        $data = [
            'user'        => $current_user_id,
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
            // $this->put_program_logs( "Gamipress Request Error: $error_message" );
            return [
                'status'  => 'error',
                'message' => $error_message,
            ];
        }

        // Extract response body and status code
        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        // $this->put_program_logs( "Gamipress Status Code: $status_code" );
        // $this->put_program_logs( "Gamipress Response: $body" );

        if ( '200' === $status_code ) {
            wp_send_json( [
                "status"  => "success",
                "message" => "You earned $point points!",
                "body"    => $body,
            ] );
        }
    }

}