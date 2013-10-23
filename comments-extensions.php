<?php
/*
Plugin Name: Comments Extensions
Plugin URI:
Description: Plugin extends comment fields
Version: 0.0.9
Author: Vadim Pshentsov
Author URI: http://pshentsoff.ru
License: Apache License, Version 2.0
Wordpress version supported: 3.6 and above
Text Domain: comments-extensions
Domain Path: /languages
*/
/**
 * @file        comments-extensions.php
 * @description
 *
 * PHP Version  5.3
 *
 * @package
 * @category
 * @plugin URI
 * @copyright   2013, Vadim Pshentsov. All Rights Reserved.
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author      Vadim Pshentsov <pshentsoff@gmail.com>
 * @created     08.08.13
 */

define('COMMENTS_EXTENSIONS_TEXT_DOMAIN', 'comments-extensions');
add_action('plugins_loaded', 'comments_extensions_init');

//if ( is_admin() ) {
//
//    /* Configuration Page */
//    function comments_extensions_admin_menu() {
//        add_options_page(
//            __( 'Comments extensions options', 'comments-extensions' ),
//            __( 'Comments extensions', 'comments-extensions' ),
//            'manage_options',
//            'comments-extensions',
//            'comments_extensions_options'
//        );
//    }
//    add_action( 'admin_menu','comments_extensions_admin_menu' );
//
//    function comments_extensions_options() {
//        // Security check
//        if (
//            function_exists( 'current_user_can' )
//            && !current_user_can( 'manage_options' )
//        ) {
//            die( __( 'Cheatin&#8217; uh?' ) );
//        }
//
//        if($_POST['action'] == 'comments_extensions_update') {
//            check_admin_referer('comments_extensions_update', 'comments_extensions_update_nonce');
//        }
//
//        ?>
<!--        <form method="post" action="">-->
<!--            --><?php //wp_nonce_field('comments_extensions_update', 'comments_extensions_update_nonce'); ?>
<!--        </form>-->
<?php
//    }
//
//}
/**
 * Actions on plugin init
 */
function comments_extensions_init() {
    load_plugin_textdomain(COMMENTS_EXTENSIONS_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ).'/languages');
}

/**
 * Function return list of clubs as associative array
 * @return array of clubs
 */
function comments_extensions_get_clubs_list() {
    return array(
        'all' => 'О сети в целом',
        'birulevo' => 'Бирюлево',
        'butovo' => 'Бутово',
        'sokol' => 'м. Аэропорт, Сокол',
        'filevsky' => 'м. Филевский парк',
        'scherbinka' => 'Щербинка',
        'yuzhnaya' => 'м. Южная'
    );
}

/**
 * Function retrun club name by club id
 * @param $club_id
 * @return string - club name or empty string
 */
function comments_extensions_get_club_by_id($club_id) {
    $clubs = comments_extensions_get_clubs_list();
    return isset($clubs[$club_id]) ? $clubs[$club_id] : '';
}

add_filter( 'comment_form_defaults', 'comments_extensions_comment_form_defaults');
/**
 * Filter for comment_form_defaults() function
 * @param $default
 * @return mixed
 */
function comments_extensions_comment_form_defaults($default) {

    $commenter = wp_get_current_commenter();
    $req = get_option( 'require_name_email' );
    $aria_req = ( $req ? " aria-required='true'" : '' );
    $default['fields']['author'] = '<p class="comment-form-author">' . '<label for="author">' . __( 'Name', COMMENTS_EXTENSIONS_TEXT_DOMAIN ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>';
    $default['fields']['email']  = '<p class="comment-form-email"><label for="email">' . __( 'Email', COMMENTS_EXTENSIONS_TEXT_DOMAIN ) . '</label> ' . ( $req ? '<span class="required">*</span>' : '' ) .
            '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>';

    $default['title_reply'] = 'Ваше сообщение:';
    $default['label_submit'] = 'Отправить сообщение';

    //Club selection list
    $club_list = comments_extensions_get_clubs_list();
    $comment_club_field = '<p class="comment-form-club">';
    $comment_club_field .= '<label for="club">'.__('Club', COMMENTS_EXTENSIONS_TEXT_DOMAIN).'</label>';
    $comment_club_field .= '<select id="club" name="club">';
    $comment_club_field .= '<option>'.__('Please, select club', COMMENTS_EXTENSIONS_TEXT_DOMAIN).'</option>';
    foreach($club_list as $club_id => $club_name) {
        $comment_club_field .= '<option value="'.$club_id.'">'.$club_name.'</option>';
    }
    $comment_club_field .= '</select>';
    $comment_club_field .= '</p>';
    $default['fields']['club'] = $comment_club_field;

//    echo '<pre>'.print_r($default, true).'</pre>';

    return $default;
}

add_filter( 'preprocess_comment', 'comments_extensions_preprocess_comment' );
function comments_extensions_preprocess_comment( $commentdata ) {
//    if ( ! isset( $_POST['club'] ) )
//        wp_die( __( 'Error: please fill the required field (club).' ) );
    return $commentdata;
}

add_action( 'comment_post', 'comments_extensions_comment_post' );
function comments_extensions_comment_post( $comment_id ) {
    add_comment_meta( $comment_id, 'club', $_POST[ 'club' ] );
}

add_filter('the_comments','comments_extensions_the_comments');
function comments_extensions_the_comments($comments, &$oWP_Comment_Query) {

//    echo '<pre>'.print_r($comments, true).'</pre>';

    foreach($comments as $key => $comment) {
        $comment = comments_extensions_get_comment($comment);
//        echo '<pre>'.print_r($comment, true).'</pre>';
        $comment->comment_author .= ' ('.$comment->comment_club.')';
        $comments[$key] = $comment;
    }

    return $comments;
}

add_filter('get_comment', 'comments_extensions_get_comment');
/**
 * Filter function for get_comment() that's add comment meta fields
 * @param $comment - comment data as StdClass object
 * @return StdClass object
 */
function comments_extensions_get_comment($comment) {

//    echo '<pre>'.print_r($comment, true).'</pre>';

    $club_id = get_comment_meta($comment->comment_ID, 'club', true);

    if(empty($club_id)) {
        $club = __('Not selected.', COMMENTS_EXTENSIONS_TEXT_DOMAIN);
    } else {
        $club = comments_extensions_get_club_by_id($club_id);
    }
    $comment->comment_club = $club;
    return $comment;
}