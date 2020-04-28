<?php

/**
 * Provide a admin control panel for the plugin
 *
 * The 'simsage_admin' class is a class that sets-up the plugin's
 * required settings/parameters and maintains them through a panel
 * in the WordPress site Settings -> SimSage Settings page
 *
 * @link       https://simsage.ai
 * @since      1.0.0
 *
 * @package    simsage-plugin
 * @subpackage simsage-plugin/inc/simsage_admin_view.php
 */

class simsage_admin
{
    // plugin defaults - these are the defaults for this plugin's admin values
    private $plugin_defaults = array(
        "simsage_page_size" => array( "value" => 3, "min" => 1, "max" => 10, "name" => "Page Size"),
        "simsage_fragment_size" => array( "value" => 3, "min" => 1, "max" => 10, "name" => "Fragment Size"),
        "simsage_word_distance" => array( "value" => 20, "min" => 0, "max" => 100, "name" => "Word Distance"),
        "simsage_override_default_search" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Override default WordPress Search"),
        "simsage_ask_email" => array( "value" => 0, "min" => 0, "max" => 1, "name" => "Ask User for an Email Address"),
        "simsage_adv_filter" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Show Advanced Filter"),
        "use_operator" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Show the Operator Button"),
        "simsage_use_bot" => array( "value" => 1, "min" => 0, "max" => 1, "name" => "Include responses from the SimSage A.I. Bot"),
        "bot_threshold" => array( "value" => 0.8125, "min" => 0.0, "max" => 1.0, "name" => "SimSage A.I. Bot threshold")
    );


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
            __( 'SimSage Settings', PLUGIN_NAME ), // page title.
            __( 'SimSage Settings', PLUGIN_NAME ), // menu title.
            'manage_options', // capability.
            PLUGIN_NAME, // menu_slug.
            array( $this, 'load_settings_page' )
        );
    }


    /**
     * Callback to save the plugin options
     */
    public function update_plugin_options() {
	    if ( isset($_POST['action']) ) { // get the required (hidden) action field's value
		    $action = $_POST['action']; // get the action
		    debug_log("action:" . $action );
		    if ($action == 'sign-in') {
		    	// perform a sign-in
		    	$this->do_sign_in($_POST[PLUGIN_NAME]['simsage_registration_key']);
		    } else if ($action == 'update-search') {
		    	// do an update to the search posted values
			    $this->check_form_parameters($_POST[PLUGIN_NAME], true);
		    } else if ($action == 'update-bot') {
			    // do an update to the bot posted values
			    $this->update_bot($_POST);
		    } else if ($action == 'update-synonyms') {
                // do an update to the synonym posted values
                $this->update_synonyms($_POST);
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
        include_once PLUGIN_DIR . 'inc/simsage_admin_view.php';
    }


    /**
     * receive an update/create post event (save)
     *
     */
    public function save_post( $new_status, $old_status, $post ) {
        // wait for something new to be published (updated or otherwise)
        if ( $new_status == 'publish' ) {
            // make sure the plugin has been setup before we react
            $plugin_options = get_option(PLUGIN_NAME);
            // this is where the data should travel to (if setup)
            if (isset($plugin_options["simsage_site"])) {
                debug_log("save_post(): start");
                if ($this->update_simsage($plugin_options["simsage_site"])) {
                    debug_log("save_post(): success");
                }
            }
        }
    }

    /*****************************************************************************
     *
     * private helper functions
     *
     */


    /**
     * perform a sign-in using the user supplied data from the admin form
     *
     * @param $registration_key string your SimSage registration key
     */
    private function do_sign_in( $registration_key ) {
        $plugin_options = get_option( PLUGIN_NAME );

        // save the user-name parameter but not the password for security reasons
        $plugin_options["simsage_registration_key"] =  $registration_key;
        // remove the account if it was set
        if ( isset($plugin_options["simsage_account"]) ) {
            unset($plugin_options["simsage_account"]);
        }
        // remove the selected site if it was previously set
        if ( isset($plugin_options["simsage_site"]) ) {
            unset($plugin_options["simsage_site"]);
        }
        update_option(PLUGIN_NAME, $plugin_options);

        // check the registration-key size
        if ( strlen( trim( $registration_key ) ) != 19 ) {
            add_settings_error('simsage_settings', 'invalid_registration_key', 'Invalid SimSage registration-key', $type = 'error');
        } else {
            // try and sign-into SimSage given the user's credentials
            $json = get_json(wp_remote_post( join_urls(SIMSAGE_API_SERVER, '/api/auth/sign-in-registration-key'),
                array('headers' => array('accept' => 'application/json', 'API-Version' => '1', 'Content-Type' => 'application/json'),
                    'body' => '{"registrationKey": "' . trim($registration_key) . '"}')));
            $error_str = check_simsage_json_response($json);

            if ( $error_str == "" ) {
                $body = get_json( $json["body"] ); // convert to an object
                if ( !isset($body['sites']) ) {
                    add_settings_error('simsage_settings', 'invalid_response', 'Invalid SimSage response.  Please upgrade your plugin.', $type = 'error');

                } else {
                    // set the account data we just got back (store it)
                    $plugin_options["simsage_account"] = $body;
                    // set our defaults (if not already set) for search and the bot and the site
                    $this->set_defaults( $plugin_options );
                    // save settings
                    update_option( PLUGIN_NAME, $plugin_options );
                    // set the current site and upload the current WP content as is
                    $this->setup_site();
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


    /**
     * Update the bot tab's items, remove items, add new items,
     * and check the fields are as we'd like them to be
     *
     * @param $post_data array the post parameters
     */
    private function update_bot($post_data) {
        $cmd = $post_data["submit"];
        $params = $post_data[PLUGIN_NAME];
        // add a new QA row if there aren't any empty ones
        if ($cmd == 'add') {
            $existing_qa = array();
            if ( isset($params["simsage_qa"]) ) {
                $existing_qa = $params["simsage_qa"];
            }
            $id = 1;
            $has_empty = false;
            $new_params = array();
            foreach ($existing_qa as $qa) {
                if (trim($qa["question"]) == "" || trim($qa["answer"]) == "") {
                    $has_empty = true;
                }
                // copy the item into a new-set of parameters, re-creating the array
                $qa["id"] = $id;
                $qa["link"] = "";
                $new_params[$id] = $qa;
                $id += 1;
            }
            if (!$has_empty) {
                // add a new entry
                $new_params[$id] = array("id" => $id, "question" => "", "answer" => "", "link" => "");
                // update all entries
                $plugin_options = get_option( PLUGIN_NAME );
                $plugin_options["simsage_qa"] = $new_params;
                update_option(PLUGIN_NAME, $plugin_options);
            } else {
                add_settings_error('simsage_settings', 'simsage_bot_qa', 'Please fill-out all existing Questions and Answers before adding new rows.', $type = 'error');
            }

        } else if ( substr( $cmd, 0, 6) == "remove" ) {
            // remove an existing row from the cells
            $id = intval( trim(substr( $cmd, 7) ) );
            $plugin_options = get_option( PLUGIN_NAME );
            $existing_qa = $plugin_options["simsage_qa"];
            unset( $existing_qa[$id] );

            $next_id = 1;
            $new_params = array();
            foreach ($existing_qa as $qa) {
                // copy the item into a new-set of parameters, re-creating the array
                $qa["id"] = $next_id;
                $qa["link"] = "";
                $new_params[$next_id] = $qa;
                $next_id += 1;
            }
            $plugin_options["simsage_qa"] = $new_params;
            update_option(PLUGIN_NAME, $plugin_options);

        } else {
            // General save: save all parameters
            $plugin_options = get_option( PLUGIN_NAME );
            $existing_qa = array();
            if ( isset($params["simsage_qa"]) ) {
                $existing_qa = $params["simsage_qa"];
            }
            // check all the questions and answers are to our liking
            $id = 1;
            $new_params = array();
            foreach ($existing_qa as $qa) {
                // copy the item into a new-set of parameters, re-creating the array
                $qa["id"] = $id;
                $qa["link"] = "";
                $new_params[$id] = $qa;
                $id += 1;
            }
            // can we save it?
            $plugin_options["simsage_qa"] = $new_params;
            update_option(PLUGIN_NAME, $plugin_options);
            // check the form's other bot parameters are valid (the threshold)
            $this->check_form_parameters($params, false);
            // validate the form
            $this->validate_qas();
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
        $plugin_options = get_option( PLUGIN_NAME );
        $existing_qa = array();
        if ( isset($plugin_options["simsage_qa"]) ) {
            $existing_qa = $plugin_options["simsage_qa"];
        }
        // check all the questions and answers are to our liking
        $has_error = false;
        foreach ($existing_qa as $id => $qa) {
            $error_str = is_valid_bot_qa_pair($id, $qa["question"], $qa["answer"]);
            if ( $error_str != null ) {
                add_settings_error('simsage_settings', 'simsage_bot_qa', $error_str, $type = 'error');
                $has_error = true;
            }
        }
        return !$has_error;
    }


    /**
     * The user adds, removes, or saves synoynms
     * @param $post_data
     */
    private function update_synonyms($post_data) {
        $cmd = $post_data["submit"];
        $params = array();
        if ( isset($post_data[PLUGIN_NAME]) ) {
            $params = $post_data[PLUGIN_NAME];
        }
        // add a new synonym row if there aren't any empty ones
        if ($cmd == 'add') {
            $existing_synonyms = array();
            if ( isset($params["simsage_synonyms"]) ) {
                $existing_synonyms = $params["simsage_synonyms"];
            }
            $id = 1;
            $has_empty = false;
            $new_params = array();
            foreach ($existing_synonyms as $synonym) {
                if (trim($synonym["words"]) == "") {
                    $has_empty = true;
                }
                // copy the item into a new-set of parameters, re-creating the array
                $synonym["id"] = $id;
                $new_params[$id] = $synonym;
                $id += 1;
            }
            if (!$has_empty) {
                // add a new entry
                $new_params[$id] = array("id" => $id, "words" => "");
                // update all entries
                $plugin_options = get_option( PLUGIN_NAME );
                $plugin_options["simsage_synonyms"] = $new_params;
                update_option(PLUGIN_NAME, $plugin_options);

            } else {
                add_settings_error('simsage_settings', 'simsage_synonyms', 'Please fill-out all existing Synoynms before adding new rows.', $type = 'error');
            }

        } else if ( substr( $cmd, 0, 6) == "remove" ) {
            // remove an existing row from the cells
            $id = intval( trim(substr( $cmd, 7) ) );
            $plugin_options = get_option( PLUGIN_NAME );
            $existing_synonyms = $plugin_options["simsage_synonyms"];
            unset( $existing_synonyms[$id] );

            $next_id = 1;
            $new_params = array();
            foreach ($existing_synonyms as $synonym) {
                // copy the item into a new-set of parameters, re-creating the array
                $synonym["id"] = $next_id;
                $new_params[$next_id] = $synonym;
                $next_id += 1;
            }
            $plugin_options["simsage_synonyms"] = $new_params;
            update_option(PLUGIN_NAME, $plugin_options);

        } else {
            // General save: save all parameters
            $plugin_options = get_option( PLUGIN_NAME );
            $existing_synonyms = array();
            if ( isset($params["simsage_synonyms"]) ) {
                $existing_synonyms = $params["simsage_synonyms"];
            }
            // check all the questions and answers are to our liking
            $id = 1;
            $new_params = array();
            foreach ($existing_synonyms as $synonym) {
                // copy the item into a new-set of parameters, re-creating the array
                $synonym["id"] = $id;
                $new_params[$id] = $synonym;
                $id += 1;
            }
            // can we save it?
            $plugin_options["simsage_synonyms"] = $new_params;
            update_option(PLUGIN_NAME, $plugin_options);
            // and run the validation checks
            $this->validate_synonyms();
        }
    }


    /**
     * Validate the synonyms are valid as entered by the user
     * Add settings errors for each error, and return false if they are not valid
     * @return bool true if the synonyms are good to save
     */
    private function validate_synonyms() {
        // General save: save all parameters
        $plugin_options = get_option( PLUGIN_NAME );
        $existing_synonyms = array();
        if ( isset($plugin_options["simsage_synonyms"]) ) {
            $existing_synonyms = $plugin_options["simsage_synonyms"];
        }
        // check all the synonyms are correct
        $has_errors = false;
        foreach ($existing_synonyms as $id => $synonym) {
            $error_str = is_valid_synonym($id, $synonym["words"]);
            if ( $error_str != null ) {
                add_settings_error('simsage_settings', 'simsage_synonyms', $error_str, $type = 'error');
                $has_errors = true;
            }
        }
        return !$has_errors;
    }


    /**
     * @param $site array the SimSage site to update
     * @return bool success
     */
    private function update_simsage( $site ) {
        // save the selected site
        $plugin_options = get_option(PLUGIN_NAME);
        $plugin_options["simsage_site"] = $site;
        update_option(PLUGIN_NAME, $plugin_options);

        $organisationId = $this->get_organisationId();
        if ( $organisationId == null ) {
            add_settings_error('simsage_settings', 'invalid_id', 'The user\'s id cannot be found.  Please login to SimSage again.', $type = 'error');
            return false;
        }
        $server = $this->get_server();
        if ( $server == null ) {
            add_settings_error('simsage_settings', 'invalid_server', 'The SimSage server settings cannot be found.  Please login to SimSage again.', $type = 'error');
            return false;
        }

        // make sure both the bot and synonyms validate
        if (!$this->validate_qas() || !$this->validate_synonyms()) {
            add_settings_error('simsage_settings', 'invalid_data', 'Please fix the above errors!', $type = 'error');
            return false;
        }

        // and index / re-index the data associated with this site
        $filename = $this->create_content_zip();
        if ($filename != null) {
            debug_log("wrote zip to:" . $filename);
            if ( !$this->upload_archive($server, $organisationId, $site["kbId"], $site["sid"], $filename) ) {
                return false;
            }
            // remove the file after use
            if ( !unlink( $filename ) ) {
                debug_log("warning: could not delete file \"" . $filename . "\"");
            }
            return true;

        } else {
            debug_log("zip failed");
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
    private function check_form_parameters($form_params, $check_keys) {
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
                    if ( $value < $default["min"] || $value > $default["max"] ) {
                        $err_str = 'The value for ' . $default["name"] . ' must be between ' . $default["min"] . ' and ' . $default["max"];
                        add_settings_error('simsage_settings', 'invalid_value', $err_str, $type = 'error');
                        return false;  // abort
                    } // if value within min and max

                } // if not a checkbox

            } // else if this is a known item

        } // for each value

        // we now have a set of valid values - save them as part of our settings
        $plugin_options = get_option(PLUGIN_NAME);
        foreach ($form_params as $key => $value) {
            if ( isset($this->plugin_defaults[$key]) ) {
                $plugin_options[$key] = $value;
            }
        }
        update_option(PLUGIN_NAME, $plugin_options);

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
        // and any future updates are captured
        add_action( 'transition_post_status', array( $this, 'save_post' ), 10, 3 );
	}


    /**
     * set the active site after sign-in from your SimSage account
     */
	private function setup_site() {
        $plugin_options = get_option(PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account["sites"]) ) {
                $this->update_simsage( $account["sites"][0] );
            }
        }
        return null;
    }


    /**
     * get the SimSage server to use from our settings
     *
     * @return string|null the SimSage server to use
     */
    private function get_server() {
        $plugin_options = get_option(PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account["server"]) ) {
                return $account["server"];
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
        $plugin_options = get_option(PLUGIN_NAME);
        if ( isset($plugin_options["simsage_account"]) ) {
            $account = $plugin_options["simsage_account"];
            if ( isset($account["id"]) ) {
                return $account["id"];
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
                return $data[$field];
            } else {
                debug_log("get_default_field: field not found: " . $key . "[" . $field . "]");
            }
        } else {
            debug_log("get_default_field: key not found: " . $key);
        }
        return 0;
    }


    /**
     * Create a zip file of all the content we're to send to SimSage in the temp folder of this machine
     * return its file-name on success, or null on failure (and sets a settings-error in that case)
     * Queries the WordPress database (wpdb) for its content
     *
     * @return string the filename to the zip-file in its temporary file location
     */
	private function create_content_zip() {
        $zip = new ZipArchive();
        $plugin_options = get_option( PLUGIN_NAME );
        $filename = tempnam(get_temp_dir(),  "simsage");
        if ($zip->open($filename, ZipArchive::CREATE)) {
            debug_log("starting " . $filename);
            // add our bot teachings for SimSage
            $qa_list = array();
            if ( isset($plugin_options["simsage_qa"]) ) {
                $qa_list = $plugin_options["simsage_qa"];
            }
            if ( count($qa_list) > 0 ) {
                add_bot_qas_to_zip($zip, $qa_list);
            }
            // add our synonyms for SimSage
            $synonyms_list = array();
            if ( isset($plugin_options["simsage_synonyms"]) ) {
                $synonyms_list = $plugin_options["simsage_synonyms"];
            }
            if ( count($synonyms_list) > 0 ) {
                add_synonyms_to_zip($zip, $synonyms_list);
            }
            // add WordPress content to our zip file to send to SimSage
            add_wp_contents_to_zip($zip);
            // done!
            $zip->close();
            debug_log("finished writing " . $filename);
            return $filename;

        } else {
            add_settings_error('simsage_settings', 'simsage_archive_error', "Failed to create archive file(s) (could not create a temporary file on your system)", $type = 'error');
        }
        return null;
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
        $url = join_urls($server, '/api/crawler/document/upload/archive');
        $bodyStr = '{"organisationId": "' . $organisationId . '", "kbId": "' . $kbId . '", "sid": "' . $sid . '", "sourceId": 1, "data": "' . $data . '"}';
        $json = get_json(wp_remote_post( $url,
            array('headers' => array('accept' => 'application/json', 'API-Version' => '1', 'Content-Type' => 'application/json'),
                'body' => $bodyStr)));
        $error_str = check_simsage_json_response($json);
        if ( $error_str != "" ) {
            add_settings_error('simsage_settings', 'simsage_upload_error', $error_str, $type = 'error');
            return false;
        }
        return true;
    }




}
