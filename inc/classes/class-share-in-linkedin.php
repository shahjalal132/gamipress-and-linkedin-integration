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
    }

    public function add_social_share_buttons( $content ) {
        if ( is_single() && in_the_loop() && is_main_query() ) {
            // HTML for the social buttons
            $social_buttons = '
            <div class="gli-social-share-buttons">
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode( get_permalink() ) . '" target="_blank" class="btn btn-linkedin">LinkedIn</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=' . urlencode( get_permalink() ) . '" target="_blank" class="btn btn-facebook">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url=' . urlencode( get_permalink() ) . '" target="_blank" class="btn btn-twitter">Twitter</a>
                <a href="https://api.whatsapp.com/send?text=' . urlencode( get_permalink() ) . '" target="_blank" class="btn btn-whatsapp">WhatsApp</a>
            </div>';

            // Append the buttons to the post content
            return $content . $social_buttons;
        }

        // Return the original content if not a single post
        return $content;
    }

}