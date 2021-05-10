<?php

// helpers
include SIMSAGE_PLUGIN_DIR . 'inc/simsage_upload_archive.php';


/**
 * Class simsage_search
 *
 * the main functionality of the SimSage search plugin can be found here
 * here we hook into WordPress for our actions, short-code, javascript files, and css styles
 *
 */
class simsage_search
{
    // location of the images folder
	private $asset_folder = '';

	// are we ready to add scripts, used by short-code renderer
	private $add_script = false;

	// context management
    private $context = "";
    private $context_boost = 0.2;

    // counter for search boxes
    private $search_counter = 1;


	// constructor
    public function __construct(){
    	// web-based folder locations, relative to this file
	    $this->asset_folder = plugin_dir_url(__FILE__) . 'assets/';
	    // initialize, setup the actions required and renderings of Javascript
	    $this->init();
    }

    /**
     * Activate the plugin, check the versions of PhP and WP
     */
    public function plugin_activate() {
    	// make sure we have the right versions of WP and php, notify the user if not
        simsage_check_versions(); // defined in inc/simsage_utilities.php
        flush_rewrite_rules();

        simsage_add_search_page();
    }

    /**
     * user de-activates the plugin
     */
    public function plugin_deactivate() {
        wp_clear_scheduled_hook( 'simsage_twicedaily' );
        flush_rewrite_rules();

        // add_filter('template_include', 'simsage_override_search_template');
    }

	/**
	 * show a notice on plugin activation if the SimSage plugin hasn't been configured yet
	 */
	public function activation_admin_notice() {
        global $pagenow;
        // only show this message on the plugins page
        if ( $pagenow == 'plugins.php' ) {
            $plugin_options = get_option(SIMSAGE_PLUGIN_NAME); // get our plugin's db data
            // display the "visit the plugin settings" link if this plugin doesn't have any settings yet
            if ( empty($plugin_options) || simsage_get_kb() == null ) {
                $message = __('Please setup the SimSage plugin settings ', SIMSAGE_PLUGIN_NAME);
                $plugin_settings_url = '<a href="' . admin_url('options-general.php?page=' . SIMSAGE_PLUGIN_NAME) . '">' .
                    __('SimSage plugin settings', SIMSAGE_PLUGIN_NAME) . '</a>';
                echo '<div class="updated notice-success is-dismissible">
                <p>' . $message . $plugin_settings_url . '</p>
            </div>';
            }
        }
	}

    public function get_view_context( $additional_params = array() ) {
        return array_merge( $additional_params, array(
            'account_server' => sanitize_text_field( $this->get_account_setting( "server" ) ),
            'account_id' => sanitize_text_field( $this->get_account_setting( "id" ) ),
            'site_kbId' => sanitize_text_field( $this->get_site_setting( "kbId" ) ),
            'operator_enabled' => $this->get_plan_boolean_value( "operatorEnabled", true ),
            'context_enabled' => sanitize_text_field( $this->context ),
            'context_match_boost' => sanitize_text_field( $this->context_boost ),
            'bot_threshold' => $this->get_user_value( "bot_threshold", 0.8125 ),
            'simsage_classes' => $this->get_user_value( "simsage_styling", "" ),
            'asset_folder' => $this->asset_folder,
            'simsage_search_width' => $this->get_user_value( "simsage_search_width", 500 ),
            'search_counter' => $this->search_counter,
        ) );
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * get a SimSage specific value from the accounts section (eg. server)
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
     * get a SimSage specific value of the knowledge-base (eg. kbId)
     *
     * @param $key string the key to look for in kb
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


    /**
     * get a SimSage user set value (eg. simsage_fragment_size)
     *
     * @param $key       string the key to look for
     * @param $default   string the default value to return if not found
     * @return float     the value, or $default if not found
     */
    private function get_user_value( $key, $default ) {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options[$key]) ) {
            return sanitize_text_field($plugin_options[$key]);
        }
        return sanitize_text_field($default);
    }


    /**
     * get a SimSage user set boolean value
     *
     * @param $key       string the key to look for
     * @param $default   string the default value to return if not found
     * @return string    the value as a string, or $default if not found
     */
    private function get_user_boolean_value( $key, $default ) {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options[$key]) ) {
            return $plugin_options[$key] ? "true" : "false";
        }
        return $default ? "true" : "false";
    }


    /**
     * get a SimSage-plan specific boolean value from the accounts section
     *
     * @param $key       string the plan key to look for
     * @param $default   string the default value to return if not found
     * @return string    the setting, or an empty string if not found
     */
    private function get_plan_boolean_value( $key, $default ) {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset( $account["plan"] ) ) {
                $plan = $account["plan"];
                if ( isset($plan[$key]) ) {
                    return $plan[$key] ? "true" : "false";
                }
            }
        }
        return $default ? "true" : "false";
    }


    // init our shortcode, style loaders and js loaders using actions
    function init() {
        // this is the [simsage-search] shortcode render function: simsage_handle_shortcode()
        add_shortcode( 'simsage-search', array( $this, 'simsage_handle_shortcode' ) );
        add_shortcode( 'simsage-search-results', array( $this, 'simsage_results_handle_shortcode' ) );

        add_shortcode( 'simsage-static-search', array( $this, 'simsage_handle_search_form_shortcode' ) );
        add_shortcode( 'simsage-static-results', array( $this, 'simsage_handle_search_results_shortcode' ) );

        add_action( 'init', array( $this, 'register_script_and_style' ) );
        // styles into the head
        add_action( 'wp_head', array( $this, 'simsage_print_styles' ), 999 );
        // include the simSage javascript files as part of the footer
        add_action( 'wp_footer', array( $this, 'simsage_print_script' ) );
        // setup an action to optionally (setup through settings) change the default WordPress search form
        add_action( 'get_search_form', array( $this, 'get_search_form' ) );
        // Admin notices for the plugin.
        add_action( 'admin_notices', array( $this, 'activation_admin_notice' ) );
    }

    function simsage_handle_search_form_shortcode( $attrs ) {
        $this->search_counter += 1;
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        $this->add_script = true;

        // get the context and context-boost settings
        $this->context = "";
        if (array_key_exists( 'context', $attrs )) {
            $this->context = sanitize_text_field($attrs["context"]);
        }
        $this->context_boost = "0.2";
        if (array_key_exists( 'context_boost', $attrs )) {
            $this->context_boost = sanitize_text_field($attrs["context-boost"]);
        }

        $style_file_is_overridden = apply_filters( 'simsage_styles', false );
        $remove_styles = wp_style_is('simsage-search-style-1', 'registered')
            || $style_file_is_overridden;

        if (!$remove_styles) {
            wp_enqueue_style('simsage-search-style-1'); // add our style-sheets
        }

        $search_slug = array_key_exists( 'main-search', $attrs ) && $attrs['main-search']
            ? '/'
            : apply_filters( 'simsage_search_page_slug', SIMSAGE_DEFAULT_SEARCH_PAGE_SLUG );

        if (simsage_get_kb() != null) {
            // render simsage_search_view.php in the context of this class
            ob_start();
            $view_context = $this->get_view_context( array(
                'action' => $search_slug,
                'remove_styles' => $remove_styles,
                'main_search' => $attrs['main-search'],
            ) );
            simsage_load_overrideable_template('simsage_search_static_view', $view_context);
            return ob_get_clean();
        } else {
            return "<div>SimSage-search plugin not configured.  Please configure your plugin first!</div>";
        }
    }

    // simsage short-code renderer
    function simsage_handle_shortcode( $attrs ) {
        $this->search_counter += 1;
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        $this->add_script = true;

        // get the context and context-boost settings
        $this->context = "";
        if (isset($attrs["context"])) {
            $this->context = sanitize_text_field($attrs["context"]);
        }
        $this->context_boost = "0.2";
        if (isset($attrs["context-boost"])) {
            $this->context_boost = sanitize_text_field($attrs["context-boost"]);
        }

        wp_enqueue_style('simsage-search-style-1'); // add our style-sheets

        if (simsage_get_kb() != null) {
            // render simsage_search_view.php in the context of this class
            ob_start();
            $view_context = $this->get_view_context();
            simsage_load_overrideable_template('simsage_search_view', $view_context);
            return ob_get_clean();
        } else {
            return "<div>SimSage-search plugin not configured.  Please configure your plugin first!</div>";
        }
    }

    // simsage-search-results short-code renderer
    function simsage_handle_search_results_shortcode( $attrs ) {
        $this->search_counter += 1;
        $plugin_options = get_option( SIMSAGE_PLUGIN_NAME );
        $this->add_script = true;

        wp_enqueue_style( 'simsage-search-style-1' ); // add our style-sheets

        if ( simsage_get_kb() != null ) {
            // render simsage_search_result_view.php in the context of this class
            ob_start();
            $view_context = $this->get_view_context();
            simsage_load_overrideable_template( 'simsage_search_result_static_view', $view_context );
            return ob_get_clean();
        } else {
            return "<div>SimSage-search plugin not configured.  Please configure your plugin first!</div>";
        }
    }

    // simsage-search-results short-code renderer
    function simsage_results_handle_shortcode( $attrs ) {
        $this->search_counter += 1;
        $plugin_options = get_option( SIMSAGE_PLUGIN_NAME );
        $this->add_script = true;

        wp_enqueue_style('simsage-search-style-1'); // add our style-sheets

        if ( simsage_get_kb() != null ) {
            // render simsage_search_result_view.php in the context of this class
            ob_start();
            $view_context = $this->get_view_context();
            simsage_load_overrideable_template( 'simsage_search_result_view', $view_context );
            return ob_get_clean();
        } else {
            return "<div>SimSage-search plugin not configured.  Please configure your plugin first!</div>";
        }
    }

    // SimSage override default search
    public function get_search_form( $content ) {
        $this->search_counter += 1;
        $plugin_options = get_option( SIMSAGE_PLUGIN_NAME );
        // only replace the search_form if the plugin has been configured and it has been configured to do so by the user
        if ( isset( $plugin_options["simsage_override_default_search"] ) && $plugin_options["simsage_override_default_search"] && simsage_get_kb() != null ) {
            $this->add_script = true;

            wp_enqueue_style( 'simsage-search-style-1' ); // add our style-sheets

            // render simsage_search_view.php in the context of this class
            ob_start();
            $view_context = $this->get_view_context( array( 'main_search' => true ) );
            simsage_load_overrideable_template( 'simsage_search_static_view', $view_context );
            return ob_get_clean();
        } else {
            return $content;
        }
    }

    // register all our javascript and css styles for this plugin
    function register_script_and_style() {
        // order the these scripts is important as there are inter-dependencies between them

        // we use sockjs and stomp for WebSocket communications with SimSage to provide operator assistance
        wp_register_script( 'simsage-search-script-1', plugins_url( 'assets/js/sockjs.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_register_script( 'simsage-search-script-2', plugins_url( 'assets/js/stomp.js', __FILE__ ), array('jquery'), '1.0', true );
        // specific implementation
        wp_register_script( 'simsage-search-script-3', plugins_url( 'assets/js/simsage-search.js', __FILE__ ), array('jquery'), '1.0', true );
        // and the required styles for search.css

        $simsage_style = array(
            'path' => plugins_url( 'assets/css/simsage-search.css', __FILE__ ),
            'ver' => NULL,
            'media' => NULL,
        );

        /**
         * Filter hook to allow stylesheet to be modified, can return NULL or false if none is to be added
         */
        $style_file = apply_filters( 'simsage_styles', $simsage_style );

        if ($style_file) {
            wp_register_style( 'simsage-search-style-1', $style_file['path'], $style_file['ver'], $style_file['media'] );
        }
    }

    // output our css as an "include" on each page using our plugin
    function simsage_print_styles() {
        if ( ! $this->add_script )
            return;

        wp_print_styles('simsage-style-1');
    }

    // output the scripts as "includes" on each page using our plugin
    function simsage_print_script() {
        if ( ! $this->add_script )
            return;

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-widget' );
        wp_enqueue_script( 'jquery-ui-slider' );

        wp_print_scripts('simsage-search-script-1');
        wp_print_scripts('simsage-search-script-2');
        wp_print_scripts('simsage-search-script-3');
    }

}
