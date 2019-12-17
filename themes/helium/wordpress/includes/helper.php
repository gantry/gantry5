<?php
/**
 * Helper class G5ThemeHelper containing useful theme functions and hooks
 */

defined('ABSPATH') or die;

// Extend Timber context
add_filter('timber_context', array('G5ThemeHelper', 'add_to_context'));

// Modify the default Admin Bar margins to render properly in the mobile mode
add_theme_support('admin-bar', array('callback' => array('G5ThemeHelper', 'admin_bar_margins')));

// Add comments pagination link attributes
add_filter('previous_comments_link_attributes', array('G5ThemeHelper', 'comments_pagination_attributes'));
add_filter('next_comments_link_attributes', array('G5ThemeHelper', 'comments_pagination_attributes'));

// Change single post pagination to list items
add_filter('wp_link_pages_link', array('G5ThemeHelper', 'wp_link_pages_li_return'));

// Modify Tag Cloud widget arguments
add_filter('widget_tag_cloud_args', array('G5ThemeHelper', 'tag_cloud_widget_modified_args'));

class G5ThemeHelper
{
    /**
     * Extend the Timber context
     *
     * @param array $context
     *
     * @return array
     */
    public static function add_to_context(array $context)
    {
        $context['is_user_logged_in'] = is_user_logged_in();
        $context['pagination']        = Timber\Timber::get_pagination();

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
    public static function comments($comment, $args, $depth)
    {
        $GLOBALS['comment'] = $comment; ?>

        <li id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>
        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
            <span class="child-arrow-indicator"><i class="fa fa-arrow-up" aria-hidden="true"></i></span>
            <header class="comment-author">
                <div class="author-avatar">
                    <?php echo get_avatar($comment, $size = '40'); ?>
                </div>
                <div class="author-meta vcard">
                    <?php printf(__('<span class="author-name">%s</span>', 'g5_helium'), get_comment_author_link()); ?>
                    <br />
                    <time datetime="<?php echo comment_date('c'); ?>">
                        <a href="<?php echo esc_url(get_comment_link($comment->comment_ID)); ?>">
                            <?php printf(__('Commented on %1$s', 'g5_helium'), get_comment_date(), get_comment_time()); ?>
                        </a>
                    </time>
                    <?php edit_comment_link(__('(Edit)', 'g5_helium'), '<span class="edit-link">', '</span>'); ?>
                </div>
            </header>

            <section class="comment-content">
                <?php if ($comment->comment_approved == '0') : ?>
                    <div class="notice">
                        <p class="alert-info"><?php _e('Your comment is awaiting moderation.', 'g5_helium'); ?></p>
                    </div>
                <?php endif; ?>

                <?php comment_text(); ?>
            </section>

            <?php comment_reply_link(array_merge($args,
                array('add_below' => 'div-comment', 'before' => '<div class="comment-reply">', 'after' => '</div>', 'depth' => $depth, 'max_depth' => $args['max_depth']))); ?>

        </article>
        <?php
    }

    // Add comments pagination link attributes
    public static function comments_pagination_attributes($attributes)
    {
        $attributes .= 'class="button"';
        return $attributes;
    }

    // Change single post pagination to list items
    public static function wp_link_pages_li_return($link)
    {
        return '<li class="pagination-list-item">' . $link . '</li>';
    }

    public static function tag_cloud_widget_modified_args($args)
    {
        $new_args = array(
            'smallest' => '0.8',
            'largest'  => '1.3',
            'unit'     => 'rem',
            'orderby'  => 'count',
            'order'    => 'DESC'
        );

        $args = wp_parse_args($new_args, $args);
        return $args;
    }

    // Modify the default Admin Bar margins to render properly in the mobile mode
    public static function admin_bar_margins()
    { ?>
        <style type="text/css" media="screen">
            html {
                margin-top: 32px !important;
            }

            * html body {
                margin-top: 32px !important;
            }

            #g-offcanvas {
                margin-top: 32px !important;
            }

            @media screen and ( max-width: 782px ) {
                html {
                    margin-top: 45px !important;
                }

                * html body {
                    margin-top: 45px !important;
                }

                #g-offcanvas {
                    margin-top: 45px !important;
                }
            }

            @media screen and ( max-width: 600px ) {
                html {
                    margin-top: 0 !important;
                }

                * html body {
                    margin-top: 0 !important;
                }

                #g-page-surround {
                    margin-top: 45px !important;
                }
            }
        </style>
        <?php
    }
}
