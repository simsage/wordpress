<?php


class simsage_analytics
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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
        }
        wp_enqueue_style('simsage-analytics-style'); // add our style-sheet (assets/css/operator.css)
        wp_enqueue_script( 'd3-script-1', plugins_url( 'assets/js/d3.min.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'simsage-analytics-script-1', plugins_url( 'assets/js/simsage-analytics.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'simsage-analytics-script28', plugins_url( 'assets/js/setup-analytics.js', __FILE__ ), array('jquery'), '1.0', true );
        // this is the html of the admin page, rendered in the context of this class
        include_once PLUGIN_DIR . 'inc/simsage_analytics_view.php';
    }


    // register all our javascript and css styles for this plugin
    function register_script_and_style() {
        // and the required styles for operator.css
        wp_register_style( 'simsage-analytics-style', plugins_url( 'assets/css/analytics.css', __FILE__ ) );
    }


    /**
     * get a SimSage specific value from the accounts section
     *
     * @return string the setting, or an empty string if not found
     */
    private function get_account_setting( $key ) {
        $plugin_options = get_option(PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account[$key]) ) {
                return $account[$key];
            }
        }
        return "";
    }


    /**
     * get a SimSage specific value from the selected site
     *
     * @return string the value, or an empty string if not found
     */
    private function get_site_setting( $key ) {
        $plugin_options = get_option(PLUGIN_NAME);
        if ( isset($plugin_options["simsage_site"]) ) {
            $site = $plugin_options["simsage_site"];
            if ( isset($site[$key]) ) {
                return $site[$key];
            }
        }
        return "";
    }

}

