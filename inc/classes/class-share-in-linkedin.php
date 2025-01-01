<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Share_In_Linkedin {

    use Singleton;
    use Program_Logs;

    public $user_urn;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_filter( 'the_content', [ $this, 'add_social_share_buttons' ] );

        // set user urn
        $this->user_urn = "JyqN44xm9_";
    }

    public function add_social_share_buttons( $content ) {
        if ( is_single() && in_the_loop() && is_main_query() ) {
            // HTML for the social buttons
            $social_buttons = "
            <div class='gli-social-share-buttons'>
                <a href='JavaScript:void(0)' id='gli-share-linkedin' data-user-urn='" . $this->user_urn . "' data-post-url='" . get_permalink() . "' data-post-title='" . get_the_title() . "' class='btn btn-linkedin'>LinkedIn</a>
            </div>";

            // Append the buttons to the post content
            return $content . $social_buttons;
        }

        // Return the original content if not a single post
        return $content;
    }

}