<?php
/*
Plugin Name: Options Changer
Description: A simple WordPress plugin to fetch and save options.
Version: 1.0
Author: Erik Yo
*/

// Add menu item to Tools menu
function options_changer_menu() {
    $submenu = add_submenu_page( 'tools.php', 'Options Changer', 'Options Changer', 'manage_options', 'options-changer', 'options_changer_page' );

    add_action( 'admin_print_styles-' . $submenu, 'options_changer_admin_style' );
}

add_action( 'admin_menu', 'options_changer_menu' );

// Enqueue admin style

function options_changer_admin_style() {
    wp_enqueue_style( 'options-changer-admin', plugin_dir_url( __FILE__ ) . '/style.css' );
}


function isJson( $string ) {
    json_decode( $string );

    return json_last_error() === JSON_ERROR_NONE;
}

// Options Changer Page
function options_changer_page() {
    // Check if the user has the required capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $option_type = 'string';

    // Fetch option from database
    if ( isset( $_POST['fetch_option'] ) ) {
        $option_name  = sanitize_text_field( $_POST['option_name'] );
        $option_value = get_option( $option_name );
        if ( ! $option_value ) {
            echo '<div class="error"><p>Option not found!</p></div>';
            var_dump( $option_value );
        } else {
            if ( is_array( $option_value ) ) {
                $option_type  = 'array';
                $option_value = json_encode( $option_value, JSON_PRETTY_PRINT );
            } elseif ( is_object( $option_value ) ) {
                $option_type  = 'object';
                $option_value = json_decode( $option_value, true );
            } elseif ( strlen( $option_value ) > 1 && is_string( $option_value ) ) {
                if ( is_serialized_string( $option_value ) ) {
                    $option_type  = 'serialized';
                    $option_value = json_encode( maybe_unserialize( $option_value ) );
                } elseif ( isJson( $option_value ) ) {
                    $option_type  = 'json';
                    $option_value = json_decode( $option_value, true );
                }
            }

            echo '<div class="updated"><p>Option fetched successfully! (Type: ' . $option_type . ')</p></div>';
        }
    }

    // Save option to database
    if ( isset( $_POST['save_option'] ) ) {

        $option_name  = sanitize_text_field( $_POST['option_name'] );
        $option_value = sanitize_text_field( $_POST['option_value'] );

        // Save option to database
        if ( $option_type == 'array' ) {
            $option_value = json_decode( $option_value, true );
        } elseif ( $option_type == 'object' ) {
            $option_value = json_decode( $option_value, true );
        } elseif ( $option_type == 'json' ) {
            $option_value = json_encode( $option_value );
        } elseif ( $option_type == 'serialized' ) {
            $option_value = serialize( $option_value );
        }
        // update_option( $option_name, $option_value );

        print_r($option_value);
        echo '<div class="updated"><p>Option saved successfully!</p></div>';
    }

    // Get all option names from the database
    $all_options  = wp_load_alloptions();
    $option_names = array_keys( $all_options );
    $button_color = empty( $option_value ) ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600';
    ?>
  <div class="wrap content max-w-[600px]">
    <h1 class="text-4xl font-bold">Options Changer</h1>

    <form method="post" action="" class="flex flex-col">
      <!--the type of option (array, object, string, json, serialized)-->
      <input type="hidden" value="<?php echo $option_type; ?>" name="option_type"/>

      <!-- the options name -->
      <div class="flex items-center w-full px-3 py-2 mt-4 rounded-t-lg bg-blue-500 dark:bg-gray-700 shadow-lg">

        <label for="option_name" class="block mr-4 text-gray-100 leading-none">Option Name:</label>
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
        <div class="flex items-center justify-center w-4 h-4 ml-auto mr-1">
          <button id="fetch_option" name="fetch_option" class="p-2 text-white rounded-full cursor-pointer hover:bg-blue-600 dark:text-white dark:hover:bg-gray-500">
            <svg class="w-4 h-4 rotate-90 rtl:-rotate-90" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
              <path d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z"/>
            </svg>
            <span class="sr-only">Update</span>
          </button>
        </div>
      </div>

      <!-- the button to fetch the new option -->
      <div>
        <!-- the text area to display the option -->
        <div class="p-2.5 bg-white dark:bg-gray-500 w-full flex flex-col text-gray-900 dark:placeholder-gray-400 dark:text-white">
          <label for="option_value" class="py-2">Option Value:</label>
          <textarea name="option_value" id="option_value" rows="15" class="w-full p-2"><?php echo $option_value; ?></textarea>
        </div>
      </div>

      <!-- the save button to save the new option to the database -->
      <button name="save_option" onclick="return confirm('Are you sure?');" class="<?php echo $button_color; ?> text-white text-lg py-2 px-4 rounded-b-lg mb-4">Save</button>
    </form>
  </div>
    <?php
}
