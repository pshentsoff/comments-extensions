<?php
/*
Plugin Name: Comments Extensions
Plugin URI:
Description: Plugin extends comment fields
Version: 0.2.2
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

/**
 * Function return set of options with info
 *
 * @return array of options
 */
function comments_extensions_get_options() {
    return array(
        'comments_extensions_clubs_list' => array(
            'type' => 'textarea',
            'default' => '',
            'title' => __( 'List of values pairs:', 'comments-extensions' ),
            'desc' => __('Pairs like "unique key => string value", one pre line, no quotas.', 'comments-extensions'),
            'class' => 'comments-extensions-clubs-list',
        ),
    );
}

/**
 * Admin area
 */
if ( is_admin() ) {

    /* Configuration Page */
    function comments_extensions_admin_menu() {
        add_options_page(
            __( 'Comments extensions options', 'comments-extensions' ),
            __( 'Comments extensions', 'comments-extensions' ),
            'manage_options',
            'comments-extensions',
            'comments_extensions_options'
        );
    }
    add_action( 'admin_menu','comments_extensions_admin_menu' );

    function comments_extensions_options() {
        // Security check
        if (
            function_exists( 'current_user_can' )
            && !current_user_can( 'manage_options' )
        ) {
            die( __( 'Cheatin&#8217; uh?' ) );
        }

        /* Get options info */
        $comments_extensions_options = comments_extensions_get_options();

        /* Update settings if submit action */
        if($_POST['action'] == 'comments_extensions_update') {

            check_admin_referer('comments_extensions_update', 'comments_extensions_update_nonce');

            foreach($comments_extensions_options as $option_name => $option_info) {

                if(($option_name == 'comments_extensions_clubs_list') && (isset($_POST[$option_name]) && !empty($_POST[$option_name]))) {
                    $lines = explode("\n", $_POST[$option_name]);
                    $clubs_list = array();
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if(empty($line)) continue;
                        $pair = explode('=>',$line);
                        $clubs_list[trim($pair[0])] = trim($pair[1]);
                    }
                    update_option($option_name, $clubs_list);
                } else {
                    update_option($option_name, $_POST[$option_name]);
                }
            }

            $_POST['notice'] = __('Settings saved.');
        }

        ?>
        <?php
        // Show notice
        if( $_POST['notice'] )
        {
            ?>
            <div id='message' class='updated fade'><p><?php echo $_POST['notice']; ?></p></div>
        <?php
        }
        ?>
    <div class='wrap'>
        <?php screen_icon(); ?>
        <h2><?php _e("Comments Extensions Settings", COMMENTS_EXTENSIONS_TEXT_DOMAIN); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('comments_extensions_update', 'comments_extensions_update_nonce'); ?>
            <table class="form-table">
                <tbody>
            <?php
            foreach($comments_extensions_options as $option_name => $option_info) {

                $option_value = get_option($option_name);

                if($option_name == 'comments_extensions_clubs_list') {
                    $lines = '';
                    foreach ($option_value as $key => $value) {
                        if(empty($key) || empty($value)) continue;
                        $lines .= $key.' => '.$value."\n";
                    }
                    $option_value = $lines;
                }

                ?>
                <tr valign='top'>
                    <th scope='row'>
                        <label for='<?php echo esc_attr( $option_name ); ?>'><?php echo $option_info['title']; ?></label>
                    </th>
                    <td>
                <?php
                switch($option_info['type']) {
                    case 'textarea':
                        echo "<textarea type='text' name='$option_name' id='$option_name' ";
                        if(isset($option_info['class'])) echo "class='".$option_info['class']."' ";
                        echo "style='width: 300px; height: 264px;' ";
                        echo '>';
                        echo $option_value;
                        echo '</textarea>' . "\n";;
                        break;
                    case 'checkbox':
                        $checked = ( $option_value ? 'checked="checked" ' : '' );
                        echo '<input type="hidden" name="' . $option_name . '" value="0"/>' . "\n";
                        echo '<input type="checkbox" name="' . $option_name . '" value="1" id="' . $option_name. '" ' . $checked;
                        if(isset($option_info['class'])) echo " class='".$option_info['class']."' ";
                        echo ' />' . "\n";
                        break;
                    default:
                        echo "<input type='text' name='$option_name' id='$option_name' ";
                        if(isset($option_info['class'])) echo "class='".$option_info['class']."' ";
                        echo '/>' . "\n";;
                        break;
                }

                ?>
                        <br /><small><?php echo $option_info['desc']; ?></small>
                    </td>
                </tr>
            <?php
            }
            ?>
                <tr valign='top'>
                    <th scope='row'></th>
                    <td>
                        <input name='Submit' class='button button-primary' value='<?php _e( 'Save Changes' ); ?>' type='submit' />
                        <input name='action' value='comments_extensions_update' type='hidden' />
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
<?php
    }

}

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

    //@todo make it some kind of session static to prevent reading from DB on every comment
    $clubs_list = get_option('comments_extensions_clubs_list');

    return $clubs_list;
}

/**
 * Function return club name by club id
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

    $default['title_reply'] = __( 'Your message', COMMENTS_EXTENSIONS_TEXT_DOMAIN );
    $default['label_submit'] = __( 'Send message', COMMENTS_EXTENSIONS_TEXT_DOMAIN );

    //Club selection list
    $club_list = comments_extensions_get_clubs_list();
    $comment_club_field = '<p class="comment-form-club">';
    $comment_club_field .= '<label for="club">'.__('Club', COMMENTS_EXTENSIONS_TEXT_DOMAIN).'</label>';
    $comment_club_field .= '<select id="club" name="club">';
    $comment_club_field .= '<option value="">'.__('Please, select club', COMMENTS_EXTENSIONS_TEXT_DOMAIN).'</option>';
    foreach($club_list as $club_id => $club_name) {
        $comment_club_field .= '<option value="'.$club_id.'">'.$club_name.'</option>';
    }
    $comment_club_field .= '</select>';
    $comment_club_field .= '</p>';
    $default['fields']['club'] = $comment_club_field;

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

    $result = array();

    $filtered = isset($_REQUEST['filter_club']) && !empty($_REQUEST['filter_club']);

    foreach($comments as $key => $comment) {
        $comment = comments_extensions_get_comment($comment);
        if($filtered) {
            if($_REQUEST['filter_club'] == $comment->club_id) {
                $comment->comment_author .= ' ('.$comment->comment_club.')';
                $result[$key] = $comment;
            }
        } else {
            $comment->comment_author .= ' ('.$comment->comment_club.')';
            $result[$key] = $comment;
        }
    }

    return $result;
}

add_filter('get_comment', 'comments_extensions_get_comment');
/**
 * Filter function for get_comment() that's add comment meta fields
 * @param $comment - comment data as StdClass object
 * @return StdClass object
 */
function comments_extensions_get_comment($comment) {

    $comment->club_id = get_comment_meta($comment->comment_ID, 'club', true);

    if(empty($comment->club_id)) {
        $comment->comment_club = __('Not selected.', COMMENTS_EXTENSIONS_TEXT_DOMAIN);
    } else {
        $comment->comment_club = comments_extensions_get_club_by_id($comment->club_id);
    }

    return $comment;
}

/**
 * Add filter by clubs to manage comments page
 * 'restrict_manage_comments' hook function
 * @since 0.2.2
 */
add_filter('restrict_manage_comments','comments_extensions_restrict_manage_comments');
function comments_extensions_restrict_manage_comments() {
    //Club selection list
    $club_list = comments_extensions_get_clubs_list();
    $filtered = isset($_REQUEST['filter_club']) && !empty($_REQUEST['filter_club']);

    echo '<select id="filter_club" name="filter_club">';
    echo '<option value="">'.__('Show all clubs', COMMENTS_EXTENSIONS_TEXT_DOMAIN).'</option>';

    foreach($club_list as $club_id => $club_name) {
        echo '<option value="'.$club_id.'"';
        echo (($filtered && $_REQUEST['filter_club'] == $club_id) ? ' selected="selected" ' : '');
        echo '>'.$club_name.'</option>';
    }
    echo '</select>';
}
