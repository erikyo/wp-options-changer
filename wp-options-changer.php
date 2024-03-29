<?php
/*
Plugin Name: Options Changer
Description: A simple WordPress plugin to fetch and save options.
Version: 1.0
Author: Erik Yo
*/

/**
 * options_changer_menu function
 */
function options_changer_menu() {
    $submenu = add_submenu_page( 'tools.php', 'Options Changer', 'Options Changer', 'manage_options', 'options-changer', 'options_changer_page' );

    add_action( 'admin_print_styles-' . $submenu, 'options_changer_admin_style', 0 );
}

add_action( 'admin_menu', 'options_changer_menu' );

// Enqueue admin style
/**
 * Enqueues the admin style for the options changer.
 *
 * @return void
 */
function options_changer_admin_style() {
    wp_enqueue_style( 'options-changer-admin', plugin_dir_url( __FILE__ ) . '/style.css' );
}

/**
 * Check if the given string is a valid JSON.
 *
 * @param string $string The input string to be checked
 *
 * @return bool Returns true if the input string is a valid JSON, false otherwise
 */
function isJson( $string ) {
    json_decode( $string );

    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Retrieves the real value of a specified option from the database.
 *
 * @param string $option The name of the option to retrieve.
 *
 * @return string The value of the specified option, or an empty string if the option is not found.
 */
function get_real_option_value( $option ) {
    global $wpdb;
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

    if ( $row !== null ) {
        return $row->option_value;
    } else {
        return '';
    }
}

/**
 * Generates the options changer page with functionality to fetch, save, and display option values.
 *
 * @throws Exception when there is an error in fetching or saving options.
 */
function options_changer_page() {
    // Check if the user has the required capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    // Check if the form has been submitted and the nonce is valid
    if ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'options-changer-nonce' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $unremovable_options = array( 'home', 'blogname', 'blogdescription', 'users_can_register', 'admin_email', 'start_of_week', 'use_balanceTags', 'use_smilies', 'require_name_email', 'comments_notify', 'posts_per_rss', 'rss_use_excerpt', 'mailserver_url', 'mailserver_login', 'mailserver_pass', 'mailserver_port', 'default_category', 'default_comment_status', 'default_ping_status', 'default_pingback_flag', 'posts_per_page', 'date_format', 'time_format', 'links_updated_date_format', 'comment_moderation', 'moderation_notify', 'permalink_structure', 'rewrite_rules', 'hack_file', 'blog_charset', 'moderation_keys', 'active_plugins', 'category_base', 'ping_sites', 'comment_max_links', 'gmt_offset', 'default_email_category', 'recently_edited', 'template', 'stylesheet', 'comment_registration', 'html_type', 'use_trackback', 'default_role', 'db_version', 'uploads_use_yearmonth_folders', 'upload_path', 'blog_public', 'default_link_category', 'show_on_front', 'tag_base', 'show_avatars', 'avatar_rating', 'upload_url_path', 'thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop', 'medium_size_w', 'medium_size_h', 'avatar_default', 'large_size_w', 'large_size_h', 'image_default_link_type', 'image_default_size', 'image_default_align', 'close_comments_for_old_posts', 'close_comments_days_old', 'thread_comments', 'thread_comments_depth', 'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order', 'sticky_posts', 'widget_categories', 'widget_text', 'widget_rss', 'uninstall_plugins', 'timezone_string', 'page_for_posts', 'page_on_front', 'default_post_format', 'link_manager_enabled', 'finished_splitting_shared_terms', 'site_icon', 'medium_large_size_w', 'medium_large_size_h', 'wp_page_for_privacy_policy', 'show_comments_cookies_opt_in', 'admin_email_lifespan', 'disallowed_keys', 'comment_previously_approved', 'auto_plugin_theme_update_emails', 'auto_update_core_dev', 'auto_update_core_minor', 'auto_update_core_major', 'wp_force_deactivated_plugins', 'initial_db_version' );

    // Generate a nonce to be used in the form action
    $nonce = wp_create_nonce( 'options-changer-nonce' );

    // Get all option names from the database
    $all_options  = wp_load_alloptions();
    $option_names = array_keys( $all_options );

    $option_name  = $_POST['option_name'] ?? 'siteurl';
    $option_value = get_real_option_value( $option_name ) ?? '';

    $button_color = empty( $option_value ) ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600';

    // remove option from database
    if ( isset( $_POST['delete_option'] ) ) {
        if ( $option_value === null ) {
            echo '<div class="error"><p>Option not found!</p></div>';
            var_dump( $option_value );
        } else {
            $option_name = sanitize_text_field( $_POST['option_name'] );

            if ( strlen( $option_value ) > 1 && is_string( $option_value ) ) {
                // do not delete wordpress core options
                if ( in_array( $option_name, $unremovable_options ) ) {
                    echo '<div class="error"><p>Option ' . $option_name . ' cannot be deleted!</p></div>';
                } else {
                    // Delete option from database
                    if ( delete_option( $option_name ) ) {
                        echo '<div class="updated"><p>Option ' . $option_name . ' removed successfully!</p></div>';
                        $option_name  = 'siteurl';
                    }
                }
            }
        }
    }

    // Fetch option from database
    if ( isset( $_POST['fetch_option'] ) ) {
        if ( $option_value === null ) {
            echo '<div class="error"><p>Option not found!</p></div>';
            var_dump( $option_value );
        } else {
            /* BY DEFAULT IS STRING */
            $option_type = 'string';

            if ( strlen( $option_value ) > 1 && is_string( $option_value ) ) {
                if ( is_serialized( $option_value ) ) {
                    /* SERIALIZED */
                    $option_type  = 'serialized';
                    $option_value = json_encode( maybe_unserialize( $option_value ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT );
                } elseif ( isJson( $option_value ) ) {
                    /* JSON */
                    $option_type = 'json';
                }
            }

            echo '<div class="updated"><p>Option fetched successfully! (Type: ' . $option_type . ')</p></div>';
        }
    }

    if ( isset( $_POST['save_option'] ) ) {

        $option_name      = sanitize_text_field( $_POST['option_name'] );
        $option_type      = sanitize_text_field( $_POST['option_type'] );
        $option_value     = stripslashes( $_POST['option_value'] );
        $raw_option_value = $option_value;

        // Save option to database
        if ( $option_type == 'serialized' ) {
            // Update option in database -the array will be automatically serialized to string
            update_option( $option_name, json_decode( $option_value, true ) );
            // format the option value to JSON string for display in the textarea
            $option_value = json_encode( json_decode( $option_value, true ) , JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT );
        } else {
            // Update option in database as string
            update_option( $option_name, sanitize_text_field( $option_value ) );
        }

        echo '<div class="updated"><p>Option saved successfully!</p></div>';
    }
    ?>
  <div class="wrap content max-w-[800px]">
    <h1 class="text-4xl font-bold">Options Changer</h1>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.35.0/codemirror.js"></script>
    <script src="https://codemirror.net/mode/javascript/javascript.js"></script>
    <link rel="stylesheet" media="all" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.35.0/codemirror.css"></style>

    <form method="post" action="" class="flex flex-col">
      <!--the type of option (array, object, string, json, serialized)-->
      <input type="hidden" value="<?php echo $option_type; ?>" name="option_type"/>
      <input type="hidden" value="<?php echo $nonce; ?>" name="nonce"/>

      <!-- the options name -->
      <div class="flex items-center w-full px-3 py-3 mt-4 rounded-t-lg bg-blue-500 shadow-lg">
        <label for="option_name" class="block mr-4 text-md text-gray-100 leading-none">Option Name:</label>
        <select name="option_name"
                onchange="document.querySelector('#fetch_option').click()"
                id="option_name"
                required
                class="block p-2 text-gray-900 bg-white rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500"
        >
            <?php
            foreach ( $option_names as $option ) {
                if ( ! empty( $option_name ) && $option_name === $option ) {
                    echo '<option value="' . esc_attr( $option ) . '" selected>' . esc_html( $option ) . '</option>';
                } else {
                    echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
                }
            }
            ?>
        </select>

        <!-- delete option button -->
        <div class="flex items-center justify-center w-4 h-4 ml-auto mr-4">
          <button id="delete_option" name="delete_option" onclick="return confirm('Are you sure to delete this option?');" class="p-2 text-white rounded-full cursor-pointer bg-red-600 hover:bg-red-400">
              <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" class="fill-white">
                <path d="M0 0h24v24H0z" fill="none"/><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
              </svg>
            <span class="sr-only">Delete</span>
          </button>
        </div>

        <!-- Fetch option button -->
        <div class="flex items-center justify-center w-4 h-4 ml-4 mr-1">
          <button id="fetch_option" name="fetch_option" class="p-2 text-white rounded-full cursor-pointer bg-indigo-600 hover:bg-blue-400">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" class="fill-white">
              <path d="M0 0h24v24H0z" fill="none"/><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
            </svg>
            <span class="sr-only">Update</span>
          </button>
        </div>
      </div>

        <?php if ( ! empty( $raw_option_value ) && isset( $_POST['save_option'] ) ) { ?>
          <!-- the button to fetch the new option -->
          <div>
            <!-- the text area to display the option -->
            <div class="p-2.5 bg-slate-800 w-full flex flex-col text-gray-900">
              <label for="raw_value" class="py-2 text-gray-100">RAW Option Value:</label>
              <textarea readonly name="raw_value" id="raw_value" rows="5" class="w-full !bg-slate-700 text-gray-100 border border-indigo-600 font-mono p-2"><?php echo $raw_option_value ?></textarea>
            </div>
          </div>
        <?php } ?>

      <!-- the button to fetch the new option -->
      <div>
        <!-- the text area to display the option -->
        <div class="p-2.5 bg-slate-300 w-full flex flex-col text-gray-900">
          <label for="option_value" class="py-2">Option Value:</label>
          <textarea name="option_value" id="option_value" data-type="<?php echo $option_type; ?>" rows="20" class="w-full font-mono p-2"><?php echo ! empty( $option_value ) ? $option_value : ''; ?></textarea>
        </div>
      </div>
      <script>
        const responseContainer = document.getElementById('raw_value');

        // the response
        if (responseContainer) {
          const editor = CodeMirror.fromTextArea(responseContainer, {
            mode: "javascript",
            lineNumbers: true,
            readOnly: true,
          });
          editor.save()
        }

        // the editor
        const editorContainer = document.getElementById('option_value');

          const editor = CodeMirror.fromTextArea(editorContainer, {
            mode: "javascript",
            lineNumbers: true,
          });
          editor.save()

      </script>
      <style>
        .cm-content, .cm-gutter { min-height: 150px; }
        .cm-gutters { margin: 1px; }
        .cm-scroller { overflow: auto; }
        .cm-wrap { border: 1px solid silver }
      </style>

      <!-- the save button to save the new option to the database -->
      <button name="save_option" onclick="return confirm('Are you sure?');" class="<?php echo $button_color; ?> text-white text-lg py-2 px-4 rounded-b-lg mb-4">Save</button>
    </form>
  </div>
    <?php
}
