<?php


class simsage_data
{
    // location of the images folder
    private $asset_folder = '';

    // constructor
    public function __construct() {
        // web-based folder locations, relative to this file
        $this->asset_folder = plugin_dir_url(__FILE__) . 'assets/';
        // initialize this control - add the required actions
        add_action( 'init', array( $this, 'register_script_and_style' ) );
    }


    /**
     * callback - return the html, styles, and js to render and work the SimSage operator
     */
    public function load_settings_page() {
        // check user capabilities.
        $user = wp_get_current_user();
        $allowed_roles = array( 'editor', 'administrator', 'author' );
        if ( empty( $user ) || !array_intersect( $allowed_roles, $user->roles ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
        }
        wp_enqueue_style('simsage-analytics-style'); // add our style-sheet (assets/css/operator.css)
        wp_enqueue_style( 'jquery-ui-style' );
        wp_enqueue_script( 'd3-script-1', plugins_url( 'assets/js/d3.min.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'simsage-analytics-script-1', plugins_url( 'assets/js/simsage-data.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'simsage-analytics-script-2', plugins_url( 'assets/js/setup-data.js', __FILE__ ), array('jquery'), '1.0', true );
        // Load the WP datepicker script
        wp_enqueue_script( 'jquery-ui-datepicker' );
        // this is the html of the admin page, rendered in the context of this class
        include_once SIMSAGE_PLUGIN_DIR . 'inc/simsage_data_view.php';
    }


    // register all our javascript and css styles for this plugin
    function register_script_and_style() {
        // and the required styles for operator.css
        wp_register_style( 'simsage-analytics-style', plugins_url( 'assets/css/data.css', __FILE__ ) );
        // jQuery date-picker styling
        wp_register_style( 'jquery-ui-style', plugins_url( 'assets/css/jquery-ui.css', __FILE__ ) );
    }


    /**
     * get a SimSage specific value from the accounts section
     *
     * @return string the setting, or an empty string if not found
     */
    private function get_account_setting( $key ) {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account[$key]) ) {
                return sanitize_text_field($account[$key]);
            }
        }
        return "";
    }


    /**
     * get a SimSage specific knowledge-base value
     *
     * @param $key string the key to look for
     * @return string the value, or an empty string if not found
     */
    private function get_site_setting( $key ) {
        $kb = simsage_get_kb();
        if ( $kb != null ) {
            if ( isset($kb[$key]) ) {
                return sanitize_text_field($kb[$key]);
            }
        }
        return "";
    }

}


