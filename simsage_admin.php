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
        "simsage_adv_filter" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Show Advanced Filter"),
        "simsage_use_bot" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Include responses from the SimSage A.I. Bot"),
        "bot_threshold" => array( "value" => 0.8125, "min" => 0.0, "max" => 1.0, "name" => "SimSage A.I. Bot threshold")
    );

    // references to the operator and data classes for menu setup after registration
    private $operator = null;
    private $data = null;

    // the cloud-servers to talk to
    private $api_server = "";

    // constructor
    public function __construct() {
        // initialize this control - add the required actions
        $this->init();
    }


    /**
     * add the administration menu for this plugin to wordpress
     */
    public function add_plugin_admin_menu() {
        add_options_page(
            __( 'SimSage Settings', SIMSAGE_PLUGIN_NAME ), // page title.
            __( 'SimSage Settings', SIMSAGE_PLUGIN_NAME ), // menu title.
            'manage_options', // capability.
            SIMSAGE_PLUGIN_NAME, // menu_slug.
            array( $this, 'load_settings_page' )
        );
    }


    /**
     * register operator and data classes for menu setup later
     *
     * @param $operator simsage_operator the operator class
     * @param $data simsage_data the data class
     */
    public function prepare_admin_menus( $operator, $data ) {
        $this->operator = $operator;
        $this->data = $data;
    }


    /**
     * Callback to save the plugin options
     */
    public function update_plugin_options() {
	    if ( isset($_POST['action']) ) { // get the required (hidden) action field's value
		    $action = sanitize_text_field($_POST['action']); // get the action
		    debug_log("action:" . $action );
		    $plugin_parameters = $_POST[SIMSAGE_PLUGIN_NAME]; // get settings array
		    if ($action == 'sign-in') {
		    	// perform a sign-in
		    	$this->do_sign_in($_POST, sanitize_text_field($plugin_parameters['simsage_registration_key']));
		    } else if ($action == 'update-search') {
		    	// do an update to the search posted values, items sanitized inside this function
			    $this->check_form_parameters($plugin_parameters, true);
            }
	    }
    }


    /**
     * load the admin menu page
     */
    public function load_settings_page() {
        // check user capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
        }
        // this is the html of the admin page, rendered in the context of this class
        include_once SIMSAGE_PLUGIN_DIR . 'inc/simsage_admin_view.php';
    }


    /**
     * run the archive upload job
     */
    function cron_upload_archive() {
        debug_log("CRON: SimSage uploading archive");
        // just save the pages
        if ( $this->update_simsage() ) {
            debug_log("CRON: cron_upload_archive(): success");
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
        debug_log( 'add_menus' );
        add_menu_page(
            __('SimSage Operator', SIMSAGE_PLUGIN_NAME), // page title.
            __('SimSage Operator', SIMSAGE_PLUGIN_NAME), // menu title.
            'manage_options', // capability.
            "simsage-operator", // menu_slug.
            array($this->operator, 'load_settings_page')
        );
        add_menu_page(
            __('SimSage Data', SIMSAGE_PLUGIN_NAME), // page title.
            __('SimSage Data', SIMSAGE_PLUGIN_NAME), // menu title.
            'manage_options', // capability.
            "simsage-data", // menu_slug.
            array($this->data, 'load_settings_page')
        );
    }


    /**
     * return the url of the portal server to use for SimSage
     *
     * @return string the portal server's URL
     */
    public function get_portal_server() {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        $servers = simsage_get_servers( $plugin_options );
        if ( isset($servers["portal"]) ) {
            return $servers["portal"];
        }
        return "";
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
                add_settings_error('simsage_settings', 'invalid_password', 'Invalid SimSage password (too short)', $type = 'error');

            } else {
                // the user wants to close their account - make sure all values are valid, all values are sanitized
                $organisationId = $this->get_organisationId();
                $kb = simsage_get_kb();
                $email = $this->get_email();
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
                    add_settings_error('simsage_settings', 'success',
                        "We've removed all your SimSage account information.  You can now safely remove this plugin.  Thank you for using SimSage!",
                        $type = 'info');
                }
            }

        } else if ( $cmd == 'update location' ) {
            $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
            $server_location = 0;
            if ( isset($post_data[SIMSAGE_PLUGIN_NAME]["simsage_server_location"]) ) {
                $server_location = sanitize_text_field( $post_data[SIMSAGE_PLUGIN_NAME]["simsage_server_location"] );
            }
            $plugin_options["simsage_server_location"] = $server_location;
            // save settings
            update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);
            // get the correct servers to talk to
            $servers = simsage_get_servers( $plugin_options );
            $this->api_server = $servers["api"];

        } else if ( $cmd == 'Connect to SimSage' ) {

            $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
            $server_location = 0;
            if ( isset($post_data[SIMSAGE_PLUGIN_NAME]["simsage_server_location"]) ) {
                $server_location = sanitize_text_field( $post_data[SIMSAGE_PLUGIN_NAME]["simsage_server_location"] );
            }
            $plugin_options["simsage_server_location"] = $server_location;

            // get the correct servers to talk to
            $servers = simsage_get_servers( $plugin_options );
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
                add_settings_error('simsage_settings', 'invalid_registration_key', 'Invalid SimSage registration-key', $type = 'error');

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
                        add_settings_error('simsage_settings', 'invalid_response',
                                            'Invalid SimSage response (missing return values in valid response).  Please upgrade your plugin.',
                                            $type = 'error');

                    } else {
                        // set the account data we just got back (store it)
                        $plugin_options["simsage_account"] = simsage_sanitize_registration_response( $body );
                        // set our defaults (if not already set) for search and the bot and the site
                        $this->set_defaults( $plugin_options );
                        // save settings
                        update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);
                        // set the current site and upload the current WP content as is as well as any synonyms, and QAs
                        $this->update_simsage();
                        // setup other parts of the plugin according to plan
                        $this->add_admin_menus();
                        // show we've successfully connected
                        add_settings_error('simsage_settings', 'success',
                            "Successfully retrieved your SimSage account information.",
                            $type = 'info');
                    }
                } else {
                    add_settings_error('simsage_settings', 'error', $error_str, $type = 'error');
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
                add_settings_error('simsage_settings', 'simsage_bot_qa', $error_str, $type = 'error');
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
                add_settings_error('simsage_settings', 'simsage_synonyms', $error_str, $type = 'error');
                $has_errors = true;
            }
        }
        return !$has_errors;
    }


    /**
     * Check all the settings are valid, create a ZIP of the current content and various language changes
     * and send them all to SimSage for processing
     *
     * @return bool success, or false if anything went wrong
     */
    private function update_simsage() {
        $plan = simsage_get_plan();
        $kb = simsage_get_kb();
        if ( $plan != null && $kb != null ) {
            $organisationId = $this->get_organisationId();
            if ($organisationId == null) {
                if ( function_exists('add_settings_error') )
                    add_settings_error('simsage_settings', 'invalid_id', 'The user\'s id cannot be found.  Please login to SimSage again.', $type = 'error');
                else
                    debug_log('ERROR: simsage-invalid-id: the user\'s id cannot be found.  Please login to SimSage again.');
                return false;
            }
            $server = $this->get_server();
            if ($server == null) {
                if ( function_exists('add_settings_error') )
                    add_settings_error('simsage_settings', 'invalid_server', 'The SimSage server settings cannot be found.  Please login to SimSage again.', $type = 'error');
                else
                    debug_log('ERROR: simsage-invalid-server: SimSage server settings cannot be found.  Please login to SimSage again.');
                return false;
            }

            // and index / re-index the data associated with this site
            $file_md5 = $this->create_content_archive( $plan );
            $filename = $file_md5[0];
            $file_md5 = $file_md5[1];
            if ($filename != null) {
                // check its md5
                $md5_sum = $this->get_archive_md5();
                debug_log("wrote zip to:" . $filename . ", old md5:" . $md5_sum . ", current md5:" . $file_md5);
                if ( $md5_sum != $file_md5 ) {
                    debug_log('site content has changed (md5) (' . $file_md5 . ')');

                    if (!$this->upload_archive( $server, $organisationId, $kb["kbId"], $kb["sid"], $filename )) {
                        return false;
                    }

                    // update the md5 for the next run
                    $this->update_archive_md5( $file_md5 );

                    if (function_exists('add_settings_error'))
                        add_settings_error('simsage_settings', 'uploaded', 'Content Successfully uploaded to SimSage', $type = 'info');
                    else
                        debug_log('SUCCESS: simsage archive uploaded.');

                } else {
                    add_settings_error('simsage_settings', 'simsage_up_to_date', 'Site content already synchronized with SimSage (no changes detected, nothing uploaded)', $type = 'info');
                    debug_log('not uploading site: content has not changed since last (' . $file_md5 . ')');
                }

                // remove the file after use
                if (!unlink($filename)) {
                    debug_log("warning: could not delete file \"" . $filename . "\"");
                }

                return true;

            } else {
                debug_log("zip failed");
                return false;
            }
        } else {
            debug_log("could not get plan / kb");
            return false;
        }
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
                    add_settings_error('simsage_settings', 'invalid_value', 'We encountered an unknown value on the form:' . $key . ", cannot process this form.", $type = 'error');
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
                        add_settings_error('simsage_settings', 'invalid_value', $err_str, $type = 'error');
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
		// Admin menu for the plugin.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
	}


    /**
     * get the SimSage server to use from our settings
     *
     * @return string|null the SimSage server to use
     */
    private function get_server() {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account["server"]) ) {
                return sanitize_text_field($account["server"]);
            }
        }
        return null;
    }


    /**
     * get the user's id (organisationId) to use from our settings
     *
     * @return string|null the user's id
     */
    private function get_organisationId() {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account["id"]) ) {
                return sanitize_text_field($account["id"]);
            }
        }
        return null;
    }


    /**
     * get the archive's last known md5 checksum for change detection
     *
     * @return string the last known md5 stored
     */
    private function get_archive_md5() {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options["archive_md5"]) ) {
            return sanitize_text_field($plugin_options["archive_md5"]);
        }
        return "";
    }


    /**
     * Update the md5 last seen for the content of this site
     *
     * @param $archive_md5 string the new md5 for the file
     */
    private function update_archive_md5( $archive_md5 ) {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        $plugin_options["archive_md5"] = sanitize_text_field($archive_md5);
        update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);
    }


    /**
     * get the user's email to use from our settings
     *
     * @return string|null the user's email address
     */
    private function get_email() {
        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account["email"]) ) {
                return sanitize_email($account["email"]);
            }
        }
        return null;
    }


    /**
     * Set all our defaults as required if they're not set yet for the sites, search, and the bot
     *
     * @param $options  array the set of options to check and amend
     */
    private function set_defaults( $options ) {
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
     * Create a gzip file of all the content we're to send to SimSage in the temp folder of this machine
     * return its file-name on success, or null on failure (and sets a settings-error in that case)
     * Queries the WordPress database (wpdb) for its content
     *
     * @param $plan array your subscription plan
     * @param $include_pages bool include the page content in the archive
     * @param $include_bot bool include the bot QA content in the archive
     * @param $include_synonyms bool include the synonyms in the archive
     * @return array the filename to the zip-file in its temporary file location and its md5 sum or (null, null)
     */
	private function create_content_archive( $plan ) {
	    if ( $plan != null ) {
	        $registration_key = simsage_get_registration_key();

            $filename = tempnam(get_temp_dir(), "simsage");
            $archive_file = fopen($filename, "wb");

            $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
            $num_docs = (int)$plan["numDocs"];
            $num_qas = (int)$plan["numQA"];
            if ($archive_file) {
                debug_log("starting " . $filename);

                $content_md5 = simsage_add_wp_contents_to_archive( $registration_key, $archive_file, $num_docs );
                // done writing the archive file
                fclose( $archive_file );

                // compress it!
                $filename_compressed = tempnam(get_temp_dir(), "simsage-compressed");
                if ( simsage_compress_file( $filename, $filename_compressed ) ) {

                    // remove the archive file - we're done with it!
                    if (!unlink( $filename )) {
                        debug_log("warning: could not delete file \"" . $filename . "\"");
                    }

                    debug_log("finished writing " . $filename_compressed . ", content md5s: " . $content_md5);
                    return array($filename_compressed, $content_md5);

                } else {
                    // failed - remove the archive file
                    if (!unlink( $filename )) {
                        debug_log("warning: could not delete file \"" . $filename . "\"");
                    }
                }

            } else {
                add_settings_error('simsage_settings', 'simsage_archive_error', "Failed to create archive file(s) (could not create a temporary file on your system)", $type = 'error');
            }
        } else {
            add_settings_error('simsage_settings', 'simsage_archive_error', "invalid plan (null)", $type = 'error');
        }
        return array(null, null);
    }


    /**
     * Create a zip file of all the content that needs to be indexed by SimSage and send it to
     * SimSage for processing
     *
     * @param $server           string the SimSage server to talk to
     * @param $organisationId   string your user id (also the SimSage organisationId)
     * @param $kbId             string your site's id (called a knowledge-base Id in SimSage)
     * @param $sid              string a changeable SimSage security id for this site
     * @param $filename         string the location of the zipped archive to upload to SimSage
     * @return bool return true on success
     */
    private function upload_archive( $server, $organisationId, $kbId, $sid, $filename ) {
        debug_log("uploading archive " . $filename . ", to: " . $server . ", org: " . $organisationId . ", kb: " . $kbId);
        $fileContent = file_get_contents($filename);
        $data = ";base64," . base64_encode($fileContent);
        $url = simsage_join_urls($server, '/api/crawler/document/upload/archive');
        $bodyStr = '{"organisationId": "' . $organisationId . '", "kbId": "' . $kbId . '", "sid": "' . $sid . '", "sourceId": 1, "data": "' . $data . '"}';
        $json = simsage_get_json(wp_remote_post($url,
            array('timeout' => SIMSAGE_JSON_DATA_UPLOAD_TIMEOUT, 'headers' => array('accept' => 'application/json', 'API-Version' => '1', 'Content-Type' => 'application/json'),
                'body' => $bodyStr)));
        $error_str = simsage_check_json_response( $server, $json );
        if ( strpos( $error_str, "not time yet ") ) {
            add_settings_error('simsage_settings', 'simsage_upload_error', "Content upload scheduled for later", $type = 'info');
            return false;

        } else {
            if ($error_str != "") {
                if (function_exists('add_settings_error'))
                    add_settings_error('simsage_settings', 'simsage_upload_error', $error_str, $type = 'error');
                else
                    debug_log('ERROR: simsage-upload-error:' . $error_str);
                return false;
            }
        }
        return true;
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
            if ( function_exists('add_settings_error') )
                add_settings_error('simsage_settings', 'simsage_close_account', $error_str, $type = 'error');
            else
                debug_log('ERROR: simsage-close-account-error:' . $error_str);
            return false;
        }
        return true;
    }


}
