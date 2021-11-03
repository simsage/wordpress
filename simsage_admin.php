<?php


/**
 * Provide a admin control panel for the plugin
 *
 * The 'simsage_admin' class is a class that sets-up the plugin's
 * required settings/parameters and maintains them through a panel
 * in the WordPress site Settings -> SimSage Settings page
 *
 * @link       https://simsage.ai
 * @since      1.2.0
 *
 * @package    simsage-plugin
 * @subpackage simsage-plugin/inc/simsage_admin_view.php
 */

class simsage_admin
{
    // plugin defaults - these are the defaults for this plugin's admin values
    private $plugin_defaults = array(
        "simsage_fragment_size" => array( "value" => 3, "min" => 1, "max" => 10, "name" => "Fragment Size"),
        "simsage_word_distance" => array( "value" => 20, "min" => 0, "max" => 100, "name" => "Word Distance"),
        "simsage_override_default_search" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Override default WordPress Search"),
        "simsage_use_bot" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Include responses from the SimSage A.I. Bot"),
        "bot_threshold" => array( "value" => 0.8125, "min" => 0.0, "max" => 1.0, "name" => "SimSage A.I. Bot threshold"),
        "search_width" => array( "value" => 500, "min" => 50.0, "max" => 2000.0, "name" => "SimSage Search text-box width")
    );

    private $simsage_leaf_icon = "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjxzdmcKICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIgogICB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgeG1sbnM6c29kaXBvZGk9Imh0dHA6Ly9zb2RpcG9kaS5zb3VyY2Vmb3JnZS5uZXQvRFREL3NvZGlwb2RpLTAuZHRkIgogICB4bWxuczppbmtzY2FwZT0iaHR0cDovL3d3dy5pbmtzY2FwZS5vcmcvbmFtZXNwYWNlcy9pbmtzY2FwZSIKICAgdmVyc2lvbj0iMS4xIgogICBpZD0iTGF5ZXJfMSIKICAgeD0iMHB4IgogICB5PSIwcHgiCiAgIHZpZXdCb3g9IjAgMCAyMCAyMCIKICAgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIKICAgc29kaXBvZGk6ZG9jbmFtZT0iU2ltU2FnZSBNYWduaWYtZXllLWluZyBnbGFzcyBpY29uIHdoaXRlLnN2ZyIKICAgd2lkdGg9IjIwIgogICBoZWlnaHQ9IjIwIgogICBpbmtzY2FwZTp2ZXJzaW9uPSIxLjAuMiAoZTg2Yzg3MDg3OSwgMjAyMS0wMS0xNSkiPjxtZXRhZGF0YQogICBpZD0ibWV0YWRhdGEyNyI+PHJkZjpSREY+PGNjOldvcmsKICAgICAgIHJkZjphYm91dD0iIj48ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD48ZGM6dHlwZQogICAgICAgICByZGY6cmVzb3VyY2U9Imh0dHA6Ly9wdXJsLm9yZy9kYy9kY21pdHlwZS9TdGlsbEltYWdlIiAvPjxkYzp0aXRsZT48L2RjOnRpdGxlPjwvY2M6V29yaz48L3JkZjpSREY+PC9tZXRhZGF0YT48ZGVmcwogICBpZD0iZGVmczI1IiAvPjxzb2RpcG9kaTpuYW1lZHZpZXcKICAgcGFnZWNvbG9yPSIjZmZmZmZmIgogICBib3JkZXJjb2xvcj0iIzY2NjY2NiIKICAgYm9yZGVyb3BhY2l0eT0iMSIKICAgb2JqZWN0dG9sZXJhbmNlPSIxMCIKICAgZ3JpZHRvbGVyYW5jZT0iMTAiCiAgIGd1aWRldG9sZXJhbmNlPSIxMCIKICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAiCiAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiCiAgIGlua3NjYXBlOndpbmRvdy13aWR0aD0iMTQzOSIKICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTA1MiIKICAgaWQ9Im5hbWVkdmlldzIzIgogICBzaG93Z3JpZD0iZmFsc2UiCiAgIGlua3NjYXBlOnpvb209IjIyLjYyNzQxNyIKICAgaW5rc2NhcGU6Y3g9IjUuMDEzOTAyNSIKICAgaW5rc2NhcGU6Y3k9IjEwLjQ4NDg1OSIKICAgaW5rc2NhcGU6d2luZG93LXg9IjAiCiAgIGlua3NjYXBlOndpbmRvdy15PSIwIgogICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIwIgogICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJMYXllcl8xIiAvPgo8c3R5bGUKICAgdHlwZT0idGV4dC9jc3MiCiAgIGlkPSJzdHlsZTIiPgoJLnN0MHtmaWxsOiNGRkZGRkY7fQo8L3N0eWxlPgo8ZwogICBpZD0iTGF5ZXJfNCIKICAgc3R5bGU9ImZpbGw6IzAwMDAwMCIKICAgdHJhbnNmb3JtPSJtYXRyaXgoMC4xOTQyMzE4MiwwLDAsMC4xNzgxNzE5MywtOS4zNTM5MDg4LC02Ljc2NTkzNzIpIj4KCTxnCiAgIGlkPSJMYXllcl8yXzJfIgogICBzdHlsZT0iZmlsbDojMDAwMDAwIj4KCQk8ZwogICBpZD0iZzYiCiAgIHN0eWxlPSJmaWxsOiMwMDAwMDAiPgoJCQk8cGF0aAogICBjbGFzcz0ic3QwIgogICBkPSJtIDgzLjgsMTA3LjggYyAtMC42LDAgLTEuMiwtMC4xIC0xLjcsLTAuNCBDIDU0LjQsOTUuNCA1MC44LDc2LjUgNTAuNiw3NS43IGwgLTAuMiwtMS4xIDAuMywtMSBjIDAuMSwtMC4zIDExLjEsLTMzLjMgNDguNiwtMzIuOCAxOS42LDAuMyAzMS42LDkuMiAzOC4yLDE2LjcgNy4yLDguMSA5LjcsMTYuMSA5LjgsMTYuNCBsIDAuNCwxLjQgLTAuNSwxLjQgYyAtNy40LDE5LjMgLTE5LjksMjcuMiAtMjksMzAuNSAtMi4zLDAuOCAtNC44LC0wLjQgLTUuNiwtMi42IC0wLjgsLTIuMiAwLjQsLTQuOCAyLjYsLTUuNiA3LjIsLTIuNiAxNi45LC04LjkgMjMuMiwtMjQgQyAxMzYuMiw2OS41IDEyNi41LDQ5LjkgOTkuMiw0OS41IDcxLjQsNDkuMSA2MS41LDcwIDU5LjQsNzUuMiBjIDEuMSwzLjUgNi4xLDE1LjUgMjYuMSwyNC4yIDIuMiwxIDMuMiwzLjUgMi4zLDUuNyAtMC43LDEuNyAtMi4zLDIuNyAtNCwyLjcgeiIKICAgaWQ9InBhdGg0IgogICBzdHlsZT0iZmlsbDojMDAwMDAwIiAvPgoJCTwvZz4KCQk8ZwogICBpZD0iZzEyIgogICBzdHlsZT0iZmlsbDojMDAwMDAwIj4KCQkJPGcKICAgaWQ9ImcxMCIKICAgc3R5bGU9ImZpbGw6IzAwMDAwMCI+CgkJCQk8cGF0aAogICBjbGFzcz0ic3QwIgogICBkPSJtIDk5LjgsOTQuNSBjIC0xMC42LDAgLTE5LC04LjQgLTE5LC0xOSAwLC0xLjYgMC4yLC0zLjMgMC42LC00LjcgMC42LC0yLjIgMywtMy42IDUuMSwtMy4xIDIuMiwwLjYgMy42LDMgMy4xLDUuMSAtMC4zLDAuOSAtMC4zLDEuNyAtMC4zLDIuNiAwLDUuOSA0LjcsMTAuNiAxMC42LDEwLjYgNS45LDAgMTAuNiwtNC43IDEwLjYsLTEwLjYgMCwtNS45IC00LjcsLTEwLjYgLTEwLjYsLTEwLjYgLTEuNywwIC0zLjYsMC41IC01LDEuMiAtMiwxLjIgLTQuNywwLjMgLTUuNywtMS43IC0xLjIsLTIgLTAuMywtNC43IDEuNywtNS43IDIuOCwtMS40IDUuOSwtMi4zIDksLTIuMyAxMC42LDAgMTksOC40IDE5LDE5IC0wLjIsMTAuNSAtOC42LDE5LjIgLTE5LjEsMTkuMiB6IgogICBpZD0icGF0aDgiCiAgIHN0eWxlPSJmaWxsOiMwMDAwMDAiIC8+CgkJCTwvZz4KCQk8L2c+CgkJPGcKICAgaWQ9ImcxOCIKICAgc3R5bGU9ImZpbGw6IzAwMDAwMCI+CgkJCTxnCiAgIGlkPSJnMTYiCiAgIHN0eWxlPSJmaWxsOiMwMDAwMDAiPgoJCQkJPHBhdGgKICAgY2xhc3M9InN0MCIKICAgZD0ibSAxMDYuMSwxNDQuNiBjIDAsMi44IC0yLjIsNSAtNSw1IGggLTIuNSBjIC0yLjgsMCAtNSwtMi4yIC01LC01IFYgMTA3IGMgMCwtMi44IDIuMiwtNSA1LC01IGggMi41IGMgMi44LDAgNSwyLjIgNSw1IHoiCiAgIGlkPSJwYXRoMTQiCiAgIHN0eWxlPSJmaWxsOiMwMDAwMDAiIC8+CgkJCTwvZz4KCQk8L2c+Cgk8L2c+CjwvZz4KPC9zdmc+Cg==";

    // location of the images folder
    private $asset_folder = '';

    // the cloud-servers to talk to
    private $api_server = "";

    // error collection array
    private $errors = array();

    // constructor
    public function __construct() {
        // initialize this control - add the required actions
        $this->init();
        // web-based folder locations, relative to this file
        $this->asset_folder = plugin_dir_url(__FILE__) . 'assets/';
    }


    /**
     * Callback to save the plugin options
     */
    public function update_plugin_options() {
	    if ( isset($_POST['action']) ) { // get the required (hidden) action field's value
		    $action = sanitize_text_field($_POST['action']); // get the action
		    if ( isset($_POST[SIMSAGE_PLUGIN_NAME]) ) {
                $plugin_parameters = $_POST[SIMSAGE_PLUGIN_NAME]; // get settings array
                if ($action == 'sign-in') {
                    // perform a sign-in
                    $this->do_sign_in($_POST, sanitize_text_field($plugin_parameters['simsage_registration_key']));
                } else if ($action == 'update-search') {
                    // do an update to the search posted values, items sanitized inside this function
                    $this->check_form_parameters($plugin_parameters, true);
                } else if ($action == 'update-filter') {
                    $this->set_ignore_urls( $plugin_parameters );
                } else if ($action == 'styling') {
                    $this->set_css_styling( $plugin_parameters );
                }
            }
	    }
    }


    /**
     * register admin menus after the plugin has been setup
     */
    public function add_admin_menus() {
        add_action( 'admin_menu', array( $this, 'add_menus' ) );
    }


    /**
     * add the administration menu for this plugin to wordpress
     */
    public function add_menus() {
        add_menu_page(
            __('SimSage', SIMSAGE_PLUGIN_NAME), // page title.
            __('SimSage', SIMSAGE_PLUGIN_NAME), // menu title.
            'edit_others_posts', // capability.
            "simsage", // menu_slug.
            array($this, 'load_settings_page'),
            $this->simsage_leaf_icon, // our icon
            5 // position of the menu item
        );
    }


    /**
     * return the url of the portal server to use for SimSage
     *
     * @return string the portal server's URL
     */
    public function get_portal_server() {
        $servers = simsage_get_servers();
        if ( isset($servers["portal"]) ) {
            return $servers["portal"];
        }
        return "";
    }


    /**
     * set the list of urls to ignore for indexing by SimSage
     *
     * @param $plugin_parameters array parameters
     */
    public function set_ignore_urls( $plugin_parameters ) {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        debug_log( print_r( $plugin_parameters, true) );
        $ignore_list = array_filter( explode( "|", $plugin_parameters['simsage_ignore_url_list'] ),
                                     function($value) { return !is_null($value) && $value !== ''; } );
        $plugin_options["simsage_ignore_url_list"] = $ignore_list;
        update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);
    }


    /**
     * set the css styling classes to apply to top-level layout
     *
     * @param $plugin_parameters array parameters
     */
    public function set_css_styling( $plugin_parameters ) {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        $plugin_options["simsage_styling"] = sanitize_text_field( $plugin_parameters['simsage_styling'] );
        $plugin_options["simsage_search_width"] = sanitize_text_field( $plugin_parameters['simsage_search_width'] );
        update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);
    }


    /*****************************************************************************
     *
     * private helper functions
     *
     */


    /**
     * Replace and clean a string from escape characters
     *
     * @param $str string the string to check and replace items in
     * @return string the string minus any \\ characters
     */
    private function remove_esc( $str ) {
        return str_replace( "\\", "", $str);
    }


    /**
     * perform a sign-in using the user supplied data from the admin form
     *
     * @param $post_data object data posted by simsage_admin_view
     * @param $registration_key string your SimSage registration key
     */
    private function do_sign_in( $post_data, $registration_key ) {
        // this is the name of the button
        $cmd = sanitize_text_field($post_data["submit"]);
        if ( $cmd == 'Close my SimSage Account' ) {
            $params = $post_data[SIMSAGE_PLUGIN_NAME];
            $password = $params['simsage_password']; // do not sanitize the password
            if (strlen(trim($password)) < 8) {
                add_error( $this->errors, 'Invalid SimSage password (too short)', $type = 'error');

            } else {
                // the user wants to close their account - make sure all values are valid, all values are sanitized
                $organisationId = get_organisationId();
                $kb = simsage_get_kb();
                $email = get_email();
                // try and delete the account
                if ($this->close_simsage_account($email, $organisationId, $kb["kbId"], $kb["sid"], $password)) {
                    // success!  clear the account information locally
                    $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
                    $plugin_options["simsage_username"] = "";
                    $plugin_options["simsage_registration_key"] = "";
                    if (isset($plugin_options["simsage_account"])) {
                        unset($plugin_options["simsage_account"]);
                    }
                    if (isset($plugin_options["simsage_qa"])) {
                        unset($plugin_options["simsage_qa"]);
                    }
                    if (isset($plugin_options["simsage_synonyms"])) {
                        unset($plugin_options["simsage_synonyms"]);
                    }
                    update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);

                    // show we've successfully removed the user's account
                    add_error( $this->errors, 
                        "We've removed all your SimSage account information.  You can now safely remove this plugin.  Thank you for using SimSage!",
                        $type = 'info');
                }
            }

        } else if ( $cmd == 'Connect to SimSage' ) {

            debug_log("Connect to SimSage");

            $plugin_options = get_option( SIMSAGE_PLUGIN_NAME );

            // get the correct servers to talk to
            $servers = simsage_get_servers();
            $this->api_server = $servers["api"];

            // save the user-name parameter but not the password for security reasons
            $plugin_options["simsage_registration_key"] = $registration_key;
            // remove the account if it was set
            if (isset($plugin_options["simsage_account"])) {
                unset($plugin_options["simsage_account"]);
            }
            update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);

            // check the registration-key size
            if (strlen(trim($registration_key)) != 19) {
                debug_log('Invalid SimSage registration-key' );
                add_error( $this->errors, 'Invalid SimSage registration-key', $type = 'error');

            } else {
                // try and sign-into SimSage given the user's key
                $url = simsage_join_urls( $this->api_server, '/api/auth/sign-in-registration-key' );
                debug_log("sign-in url:" . $url);
                $json = simsage_get_json(wp_remote_post($url,
                    array('timeout' => SIMSAGE_JSON_POST_TIMEOUT, 'headers' => array('accept' => 'application/json', 'API-Version' => '1', 'Content-Type' => 'application/json'),
                        'body' => '{"registrationKey": "' . trim($registration_key) . '"}')));
                debug_log(sanitize_text_field(print_r($json, true)));
                $error_str = simsage_check_json_response( $this->api_server, $json );
                // no error?
                if ($error_str == "") {
                    $body = simsage_get_json($json["body"]); // convert to an object
                    if (!isset($body['kbId']) || !isset($body['sid']) || !isset($body['plan'])  || !isset($body['server']) ||
                        !isset($body['id']) || !isset($body['email'])) {
                        debug_log('Invalid SimSage response (missing return values in valid response).  Please upgrade your plugin.' );
                        add_error( $this->errors, 'Invalid SimSage response (missing return values in valid response).  Please upgrade your plugin.',
                                            $type = 'error');

                    } else {
                        // set the account data we just got back (store it)
                        $plugin_options["simsage_account"] = simsage_sanitize_registration_response( $body );
                        // set our defaults (if not already set) for search and the bot and the site
                        $this->set_defaults( $plugin_options );
                        // save settings
                        update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);
                        // set the current site and upload the current WP content as is as well as any synonyms, and QAs
                        update_simsage( $this->errors );
                        // setup other parts of the plugin according to plan
                        $this->add_admin_menus();
                        // show we've successfully connected
                        debug_log('Successfully retrieved your SimSage account information.' );
                        add_error( $this->errors, "Successfully retrieved your SimSage account information.", $type = 'info');
                    }
                } else {
                    debug_log('SimSage error: ' . $error_str );
                    add_error( $this->errors, $error_str, $type = 'error');
                }
            }
        }
    }


    /**
     * Run a validation on the user supplied questions and answers
     * return false if there is something wrong with either questions or answers provided
     * and add set settings errors for each error
     * @return bool true if the QA parameters are all valid to save
     */
    private function validate_qas() {
        // General save: save all parameters
        $plugin_options = get_option( SIMSAGE_PLUGIN_NAME );
        $existing_qa = array();
        if ( isset($plugin_options["simsage_qa"]) ) {
            $existing_qa = $plugin_options["simsage_qa"];
        }
        // check all the questions and answers are to our liking
        $has_error = false;
        foreach ($existing_qa as $id => $qa) {
            $error_str = simsage_is_valid_bot_qa_pair($id, $qa["question"], $qa["answer"], $qa["context"]);
            if ( $error_str != null ) {
                add_error( $this->errors, $error_str, $type = 'error');
                $has_error = true;
            }
        }
        return !$has_error;
    }


    /**
     * Validate the synonyms are valid as entered by the user
     * Add settings errors for each error, and return false if they are not valid
     * @return bool true if the synonyms are good to save
     */
    private function validate_synonyms() {
        // General save: save all parameters
        $plugin_options = get_option( SIMSAGE_PLUGIN_NAME );
        $existing_synonyms = array();
        if ( isset($plugin_options["simsage_synonyms"]) ) {
            $existing_synonyms = $plugin_options["simsage_synonyms"];
        }
        // check all the synonyms are correct
        $has_errors = false;
        foreach ($existing_synonyms as $id => $synonym) {
            $error_str = simsage_is_valid_synonym($id, $synonym["words"]);
            if ( $error_str != null ) {
                add_error( $this->errors, $error_str, $type = 'error');
                $has_errors = true;
            }
        }
        return !$has_errors;
    }


    /**
     * Check a save of the items in the search-tab are all valid and within range and save the values when this is the case
     *
     * @param $form_params array the form values to check
     * @param $check_keys bool check the keys must exist if true
     * @return bool  return true on success, false on failure
     */
    private function check_form_parameters( $form_params, $check_keys ) {
        foreach ($form_params as $key => $value) {
            if ( !isset($this->plugin_defaults[$key]) ) {
                if ( $check_keys ) {
                    add_error( $this->errors, 'We encountered an unknown value on the form:' . $key . ", cannot process this form.", $type = 'error');
                    return false;
                }
            } else {
                // get the default for this form parameter
                $default = $this->plugin_defaults[$key];
                // check it is within range if it's default isn't 0 or 1
                if ( $default["value"] != 0 && $default["value"] != 1 ) {
                    $sanitized_value = sanitize_text_field($value);
                    if ( $sanitized_value < $default["min"] || $sanitized_value > $default["max"] ) {
                        $err_str = 'The value for ' . $default["name"] . ' must be between ' . $default["min"] . ' and ' . $default["max"];
                        add_error( $this->errors, $err_str, $type = 'error');
                        return false;  // abort
                    } // if value within min and max

                } // if not a checkbox

            } // else if this is a known item

        } // for each value

        // we now have a set of valid values - save them as part of our settings
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        foreach ($form_params as $key => $value) {
            if ( isset($this->plugin_defaults[$key]) ) {
                $plugin_options[$key] = sanitize_text_field($value);
            }
        }
        update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);

        return true;
    }


    /**
	 * Register all of the hooks related to the admin area functionality
	 */
	private function init() {
		// Save/Update plugin options.
		add_action( 'admin_init', array( $this, 'update_plugin_options' ) );
	}


    /**
     * Set all our defaults as required if they're not set yet for the sites, search, and the bot
     *
     * @param $options  array the set of options to check and amend
     */
    private function set_defaults( $options ) {
        $options["simsage_styling"] = ""; // default value for styling
        foreach ($this->plugin_defaults as $key => $value) {
            if ( !isset( $options[$key]) ) {
                $options[$key] = $value["value"];
            }
        }
    }


    /**
     * find a default value for a given item or return 0
     *
     * @param $key      string the key to look for in the plugin_defaults
     * @param $field    string the field's value to get, one of {"value", "min", "max"}
     * @return integer|float the default value if found, otherwise 0
     */
    private function get_default_field( $key, $field ) {
        if ( isset($this->plugin_defaults[$key]) ) {
            $data = $this->plugin_defaults[$key];
            if ( isset($data[$field]) ) {
                return sanitize_text_field($data[$field]);
            } else {
                debug_log("get_default_field: field not found: " . sanitize_text_field($key) . "[" . sanitize_text_field($field) . "]");
            }
        } else {
            debug_log("get_default_field: key not found: " . sanitize_text_field($key));
        }
        return 0;
    }


    /**
     * Close a SimSage account
     *
     * @param $email            string the user's email address
     * @param $organisationId   string your user id (also the SimSage organisationId)
     * @param $kbId             string your site's id (called a knowledge-base Id in SimSage)
     * @param $sid              string a changeable SimSage security id for this site
     * @param $password         string the user's account password
     * @return bool return true on success
     */
    private function close_simsage_account( $email, $organisationId, $kbId, $sid, $password ) {
        debug_log("closing account " . $email . ", org: " . $organisationId . ", kb: " . $kbId);
        $url = simsage_join_urls( $this->api_server, '/api/auth/wp-close-account' );
        $bodyStr = '{"organisationId": "' . $organisationId . '", "kbId": "' . $kbId . '", "sid": "' . $sid .
                    '", "password": "' . $password . '", "email": "' . $email . '"}';
        $json = simsage_get_json(wp_remote_post($url,
            array('timeout' => SIMSAGE_JSON_POST_TIMEOUT, 'headers' => array('accept' => 'application/json', 'API-Version' => '1', 'Content-Type' => 'application/json'),
                  'body' => $bodyStr)));
        $error_str = simsage_check_json_response( $this->api_server, $json );
        if ($error_str != "") {
            add_error( $this->errors, $error_str, $type = 'error');
            debug_log('ERROR: simsage-close-account-error:' . $error_str);
            return false;
        }
        return true;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////

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

        // and the required styles for operator.css
        wp_register_style( 'simsage-analytics-style', plugins_url( 'assets/css/data.css', __FILE__ ) );
        // jQuery date-picker styling
        wp_register_style( 'jquery-ui-style', plugins_url( 'assets/css/jquery-ui.min.css', __FILE__ ) );
        // and the required styles for operator.css
        wp_register_style( 'simsage-operator-style', plugins_url( 'assets/css/operator.css', __FILE__ ) );

        wp_enqueue_style( 'jquery-ui-style' );
        wp_enqueue_style( 'simsage-analytics-style' ); // add our style-sheet (assets/css/data.css)
        wp_enqueue_style( 'simsage-operator-style' ); // add our style-sheet (assets/css/operator.css)

        wp_enqueue_script( 'd3-script-1', plugins_url( 'assets/js/d3.min.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'simsage-ops-script-1', plugins_url( 'assets/js/sockjs.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'simsage-ops-script-2', plugins_url( 'assets/js/stomp.js', __FILE__ ), array('jquery'), '1.0', true );
        // Load the WP datepicker script
        wp_enqueue_script( 'jquery-ui-datepicker' );

        wp_enqueue_script( 'simsage-ops-script-4', plugins_url( 'assets/js/simsage-common.js', __FILE__ ), array('jquery'), '1.0', true );

        wp_enqueue_script( 'simsage-analytics-script-2', plugins_url( 'assets/js/setup-data.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'simsage-analytics-script-1', plugins_url( 'assets/js/simsage-data.js', __FILE__ ), array('jquery'), '1.0', true );

        wp_enqueue_script( 'ops-script-1', plugins_url( 'assets/js/operator.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_enqueue_script( 'ops-script-2', plugins_url( 'assets/js/setup-operator.js', __FILE__ ), array('jquery'), '1.0', true );

        // this is the html of the admin page, rendered in the context of this class
        include_once SIMSAGE_PLUGIN_DIR . 'inc/simsage_admin_view.php';
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
