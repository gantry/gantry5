<?php
/**
 * Helper class G5TemplateHelper containing useful theme functions and hooks
 */

// Extend Timber context
add_filter( 'timber_context', [ 'G5TemplateHelper', 'add_to_context' ] );

// Add comments pagination link attributes
add_filter( 'previous_comments_link_attributes', [ 'G5TemplateHelper', 'comments_pagination_attributes' ] );
add_filter( 'next_comments_link_attributes', [ 'G5TemplateHelper', 'comments_pagination_attributes' ] );

class G5TemplateHelper {
    /**
     * Extend the Timber context
     *
     * @param array $context
     *
     * @return array
     */
    public static function add_to_context( array $context ) {
        $context[ 'pagination' ]   = Timber::get_pagination();
        $context[ 'is_user_logged_in' ] = is_user_logged_in();

        return $context;
    }

    /**
     * Single comment callback
     *
     * Using the callback so the walker can go through and give us nested comments
     *
     * @param type $comment
     * @param type $args
     * @param type $depth
     */
    public static function comments( $comment, $args, $depth ) {
        $GLOBALS[ 'comment' ] = $comment; ?>

        <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
            <article class="comment-body">
                <header class="comment-author">
                    <div class="author-avatar">
                        <?php echo get_avatar( $comment, $size = '48' ); ?>
                    </div>
                    <div class="author-meta vcard">
                        <?php printf( __( '<span class="author-name">%s</span>', 'g5_hydrogen' ), get_comment_author_link() ); ?>
                        <time datetime="<?php echo comment_date( 'c' ); ?>">
                            <a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
                                <?php printf( __( '%1$s', 'g5_hydrogen' ), get_comment_date(),  get_comment_time() ); ?>
                            </a>
                        </time>
                        <?php edit_comment_link( __( '(Edit)', 'g5_hydrogen' ), '<span class="edit-link">', '</span>' ); ?>
                    </div>
                    <div class="clear"></div>
                </header>

                <section class="comment-content">
                    <?php if ($comment->comment_approved == '0') : ?>
                        <div class="notice">
                            <p class="alert-info"><?php _e( 'Your comment is awaiting moderation.', 'g5_hydrogen' ); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php comment_text(); ?>

                    <?php comment_reply_link( array_merge( $args, ['before' => '<div class="comment-reply">', 'after' => '</div>', 'depth' => $depth, 'max_depth' => $args[ 'max_depth' ] ] ) ); ?>
                </section>

            </article>
        <?php
    }

    public static function comments_pagination_attributes( $attributes ) {
        $attributes .= 'class="button"';
        return $attributes;
    }
}
