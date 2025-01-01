<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Share_In_Linkedin {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_filter( 'the_content', [ $this, 'add_social_share_buttons' ] );

        // handle ajax request
        add_action( 'wp_ajax_share_on_linkedin', [ $this, 'share_on_linkedin' ] );
        add_action( 'wp_ajax_nopriv_share_on_linkedin', [ $this, 'share_on_linkedin' ] );
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

        $data = sprintf( "Post Url: %s - Post Title: %s - Prompt value : %s", $predefined_url, $post_title, $prompt_value );
        $this->put_program_logs( $data );

        // TODO: share on linkedin
    }

}