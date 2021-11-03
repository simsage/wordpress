<?php

/**
 * misc SimSage utilities for helping the plugin
 *
 */


/**
 * check both php and wp versions are as we need them to be or die if they're not appropriate
 * and leave the user with a message advising them what to do
 *
 */
function simsage_check_versions() {
    global $wp_version;

    // php language version required
    $php = '7.0';
    // wordpress system version required
    $wp  = '5.0';



    // check PhP
    if ( version_compare( PHP_VERSION, $php, '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        wp_die(
            '<p>' .
            sprintf(
                __( 'This plugin can not be activated because it requires a PHP version greater than %1$s. Your PHP version can be updated by your hosting company.', 'simsage-plugin' ),
                $php
            )
            . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . __( 'go back', 'simsage-plugin' ) . '</a>'
        );
    }

    // check WordPress
    if ( version_compare( $wp_version, $wp, '<' ) ) {
        deactivate_plugins( basename( __FILE__ ) );
        wp_die(
            '<p>' .
            sprintf(
                __( 'This plugin can not be activated because it requires a WordPress version greater than %1$s. Please go to Dashboard &#9656; Updates to get the latest version of WordPress .', 'simsage-plugin' ),
                $php
            )
            . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . __( 'go back', 'simsage-plugin' ) . '</a>'
        );
    }
}


/**
 * write to the debug log on the system
 */
if ( ! function_exists('debug_log')) {
	function debug_log( $log ) {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( "SIMSAGE:" . print_r( $log, true ) );
        } else {
			error_log( "SIMSAGE:" . $log );
		}
	}
}


/**
 * Add an error to the errors array
 *
 * @param $errors   array the errors array
 * @param $message  string the message to display
 * @param $type     string the type: error, info
 */
function add_error( &$errors, $message, $type ) {
    error_log( "SIMSAGE: [" . $type . "]: " . $message );
    $count = count( $errors ) + 1;
    array_push( $errors, array( "id" => $count,
                        "message" => esc_html__( $message ),
                        "type" => esc_html__( $type ) ) );
}


/**
 * convert a data object to a json object
 * @param $data string|object
 * @return mixed
 */
function simsage_get_json( $data ) {
	if ( gettype($data) == "string" ) {
		return json_decode($data, true, 512);
	} else {
		return json_decode(json_encode($data), true, 512);
	}
}


/**
 * get the list of urls to ignore for indexing by SimSage
 *
 * @return array a list of items (or empty) that is the ignore list
 */
function get_ignore_urls() {
    $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
    if ( isset($plugin_options["simsage_ignore_url_list"]) ) {
        return array_filter( $plugin_options['simsage_ignore_url_list'],
            function($value) { return !is_null($value) && $value !== ''; } );
    }
    return array();
}


/**
 * query word-press' content and add all published items to an archive
 *
 * @param $registration_key string the system's registration key
 * @param $archive_file resource an archive file to write to
 * @param $num_docs int the maximum number of allowed documents for this site as per plan
 * @param $ignore_urls array an array of URLs that are to be ignored (not indexed)
 * @param $errors array an array of error objects to add to if necessary
 * @return string the combined md5s of the content
 */
function simsage_add_wp_contents_to_archive( $registration_key, $archive_file, $num_docs, $ignore_urls, &$errors ) {
    global $wpdb;
    $query = "SELECT * FROM $wpdb->posts WHERE post_status = 'publish'";
    $results = $wpdb->get_results($query);
    $counter = 1;
    $ignore_counter = 0;
    $md5_str = md5( $registration_key );

    // write out ignore fields?  Must come before WP_DATA below
    if ( count( $ignore_urls ) > 0 ) {
        fwrite( $archive_file, SIMSAGE_DOC_IGNORE_DATA . "\n", strlen(SIMSAGE_DOC_IGNORE_DATA) + 1 );
        foreach ($results as $row) {
            $obj = $row;
            if ( in_array( get_permalink( $obj ), $ignore_urls) ) { // write out items that aren't supposed to be there
                $str = get_permalink( $obj ) . "\n";
                fwrite($archive_file, $str, strlen($str));
                $md5_str = md5($md5_str . $str);
            }
        }
    }

    // write the marker to file
    fwrite( $archive_file, SIMSAGE_DOC_WP_DATA . "\n", strlen(SIMSAGE_DOC_WP_DATA) + 1 );
    debug_log("md5 rego-key:" . $md5_str);
    foreach ($results as $row) {
        $obj = $row;
        if ( !in_array( get_permalink( $obj ), $ignore_urls ) ) { // filter out items that aren't supposed to be there
            // get the author for this item
            $author = get_user_by('id', $obj->post_author);
            $author_name = '';
            if ($author && $author->data) {
                if ($author->data->user_nicename)
                    $author_name = $author->data->user_nicename;
                else if ($author->data->user_email)
                    $author_name = $author->data->user_email;
            }
            // make sure author has no nasty characters in it
            $author_name = str_replace("|", " ", $author_name);
            $author_name = sanitize_text_field(str_replace("\n", " ", $author_name));

            // format: url | title | author | mimeType | created | last-modified | data
            $str = get_permalink( $obj ) . "|" . sanitize_title($obj->post_title) . "|" . sanitize_text_field($author_name) . "|text/html|";
            $str = $str . sanitize_text_field(strtotime($obj->post_date_gmt)) . "000|";
            $str = $str . sanitize_text_field(strtotime($obj->post_modified_gmt)) . "000|" . $counter . ".html;base64,";
            // legacy - we don't write the content anymore - as it is fetched using the URL
            $base64Str = base64_encode("<html lang='en'></html>");
            // no CRs please
            $base64Str = str_replace("\n", "", $base64Str);
            $str = $str . $base64Str . "\n";
            // write the item to file
            fwrite($archive_file, $str, strlen($str));
            $counter += 1;
            $md5_str = md5($md5_str . $str);

            if ($counter > $num_docs) {  // we've reached the limit
                // warn the user they have exceeded the allocation for number of pages on their plan
                add_error( $errors,"Your WordPress post count " .
                    "exceeds the maximum allocated number of posts for your plan (" . $num_docs . ").  " .
                    "We will upload the first " . $num_docs . " pages only.", $type = 'error');
                break;
            }
        } else {
            $ignore_counter += 1;
        }
    }

    debug_log("added " . $counter . " pages to zip, ignoring " . $ignore_counter . " pages, md5 " . $md5_str);
    return $md5_str;
}


/**
 * query word-press' content and return a list of all URLs SimSage would look at
 *
 * @return array a list of URLs
 */
function simsage_get_wp_contents() {
    global $wpdb;
    $query = "SELECT * FROM $wpdb->posts WHERE post_status = 'publish'";
    $results = $wpdb->get_results($query);
    $result_list = array();
    foreach ($results as $row) {
        $obj = $row;
        array_push( $result_list, get_permalink( $obj ) ); // translate object to permalink (what the back-end sees)
    }
    return $result_list;
}


/**
 * Add all QA items to an archive file passed in as a string in marked by SIMSAGE_DOC_BOT_DATA
 *
 * @param $archive_file resource the file to write to
 * @param $qa_list array a list of Question and Answer items
 * @param $num_qas int the maximum number of QAs allowed
 * @return string the md5 of the content (or empty string)
 */
function simsage_add_bot_qas_to_archive($archive_file, $qa_list, $num_qas ) {
    $str = "";
    $counter = 0;
    fwrite( $archive_file, SIMSAGE_DOC_BOT_DATA . "\n", strlen(SIMSAGE_DOC_BOT_DATA) + 1 );
    foreach ($qa_list as $qa) {
        if ( strlen(trim($qa["question"])) > 0 && strlen(trim($qa["answer"])) > 0 ) {
            // format: id | question | answer | context | link \n
            $q = str_replace( "\\", "", $qa["question"]);
            $a = str_replace( "\\", "", $qa["answer"]);
            $c = str_replace( "\\", "", $qa["context"]);
            $l = str_replace( "\\", "", $qa["link"]);
            $str .= sanitize_text_field($qa["id"]) . "|" . sanitize_text_field($q) . "|" . sanitize_text_field($a) . "|" . sanitize_text_field($c) . "|" . sanitize_text_field($l) . "\n";
            $counter += 1;
            if ( $counter > $num_qas ) { // exit when we've reached the maximum
                break;
            }
        }
    }
    if ( strlen($str) > 0 ) {
        fwrite( $archive_file, $str, strlen($str) );
        return md5( $str );
    }
    return "";
}


/**
 * Add all Synonyms to an archive file passed in as a string marked SIMSAGE_DOC_SYNONYM_DATA
 *
 * @param $archive_file resource an archive file to write to
 * @param $synonym_list array a list of synonym items
 * @return string the md5 of the content or empty string
 */
function simsage_add_synonyms_to_archive($archive_file, $synonym_list ) {
    $str = "";
    fwrite( $archive_file, SIMSAGE_DOC_SYNONYM_DATA . "\n", strlen(SIMSAGE_DOC_SYNONYM_DATA) + 1 );
    foreach ($synonym_list as $synonym) {
        if ( strlen(trim($synonym["words"])) > 0 ) {
            // format: url | title | mimeType | created | last-modified | data
            $words = str_replace( "\\", "", $synonym["words"]);
            $str .= sanitize_text_field($synonym["id"]) . "|" . sanitize_text_field($words) . "\n";
        }
    }
    if ( strlen($str) > 0 ) {
        fwrite( $archive_file, $str, strlen($str) );
        return md5( $str );
    }
    return "";
}


/**
 * helper - check a string is valid for either a bot question or answer
 * @param $str
 * @return string|null return a string with an error, or null if there is none
 */
function simsage_is_valid_bot_str($str ) {
    $invalid_chars = array( "(", ")", "|", "[", "]", "{", "}");
    if ( trim($str) == "" ) return "text is empty";
    if ( strlen( trim($str) ) > SIMSAGE_MAX_STRING_LENGTH ) return "string too long (maximum length allowed is " . SIMSAGE_MAX_STRING_LENGTH . " characters)";
    foreach ($invalid_chars as $ic) {
        if (strpos($str, $ic)) return "string must not contain " . sanitize_text_field($ic) . " character(s)";
    }
    return null;
}


/**
 * helper - check a string is valid for bot context
 * @param $str
 * @return string|null return a string with an error, or null if there is none
 */
function simsage_is_valid_context_str($str ) {
    $invalid_chars = array( "(", ")", "|", "[", "]", "{", "}");
    if ( strlen( trim($str) ) > SIMSAGE_MAX_STRING_LENGTH ) return "string too long (maximum length allowed is " . SIMSAGE_MAX_STRING_LENGTH . " characters)";
    foreach ($invalid_chars as $ic) {
        if (strpos($str, $ic)) return "string must not contain " . $ic . " character(s)";
    }
    return null;
}


/**
 * check if the text in the question / answer pair is correct / valid
 * $param $id int the id of the question answer pair
 * @param $id string a unique id for this QA pair
 * @param $question string the question's text
 * @param $answer string the answer's text
 * @return string|null return a string with an error, or null if there is none
 */
function simsage_is_valid_bot_qa_pair($id, $question, $answer, $context ) {
    $error1 = simsage_is_valid_bot_str($question);
    if ( $error1 != null) return "Question " . sanitize_text_field($id) . ": " . $error1;
    $error2 = simsage_is_valid_bot_str($answer);
    if ( $error2 != null) return "Answer " . sanitize_text_field($id) . ": " . $error2;
    $error3 = simsage_is_valid_context_str($context);
    if ( $error3 != null) return "Context " . sanitize_text_field($id) . ": " . $error3;
    return null;
}


/**
 * helper - check a string is valid synonym:
 *   must not end in . -
 *   must contain . - a..z A..Z or 0..9
 *
 * @param $str
 * @return string|null return a string with an error, or null if there is none
 */
function simsage_is_valid_synonym_str($str ) {
    if ( trim($str) == "" ) return "text is empty";
    $words = explode(",", sanitize_text_field($str));
    if ( count($words) > 2 ) return "no more than two words for a synonym";
    foreach ($words as $word) {
        if ( strlen( trim($word) ) > 20 ) return "word too long (maximum length per word is 20 characters)";
        if ( strlen( trim($word) ) < 2 ) return "word too short, synonyms must be at least 2 characters";
        $characters = str_split( trim( $word ) );
        $last_ch = "";
        foreach ( $characters as $ch ) {
            if ( $ch != '-' && $ch != "." && $ch != ' ' && !(
                ( $ch >= 'a' && $ch <= 'z' ) || ( $ch >= 'A' && $ch <= 'Z' ) || ( $ch >= '0' && $ch <= '9' ) )) {
                return "words must only contain letters, numbers, hyphens (-) and full-stops";
            }
            $last_ch = $ch;
        }
        if ( $last_ch == '_' || $last_ch == '-' || $last_ch == "." ) {
            return "Last character of a word must be a letter or a number.  Full-stops or hyphens are not allowed.";
        }
    }
    return null;
}


/**
 * check if the text of the synonyms is correct
 * $param $id int the id of the synonym
 *
 * @param $id string a unique id for this synonym
 * @param $synonym string the question's text
 * @return string|null return a string with an error, or null if there is none
 */
function simsage_is_valid_synonym($id, $synonym ) {
    if ( trim($synonym) == "" ) return "Synonym " . sanitize_text_field($id) . " string is empty";
    $words = explode(",", sanitize_text_field($synonym));
    if ( count($words) == 1 ) return "Synonym " . sanitize_text_field($id) . " must have comma separated words that are synonymous to it";
    foreach ($words as $word) {
        $error1 = simsage_is_valid_synonym_str(sanitize_text_field($word));
        if ( $error1 != null) return "Synonym " . sanitize_text_field($id) . ": " . $error1;
    }
    return null;
}


/**
 * Join two urls together with a / in between
 * @param $url1 string first part of the url
 * @param $url2 string second part of the url
 * @return string the joined url
 */
function simsage_join_urls($url1, $url2 ) {
    if ( substr( $url1, strlen($url1) - 1, 1 ) === "/" && substr( $url1, 0, 1 ) === '/' ) {
        return $url1 . substr($url2, 1);
    } else if ( substr( $url1, strlen($url1) - 1, 1 ) === "/" || substr( $url1, 0, 1 ) === '/' ) {
        return $url1 . $url2;
    }
    return $url1 . "/" . $url2;
}


/**
 * @param $server string the server we're trying to talk to
 * @param $json array the data returned by SimSage from the server
 * @return string an empty string if no error was found, otherwise a description of the error
 */
function simsage_check_json_response( $server, $json ) {
    if ( isset($json["error"]) ) {
        $error = print_r( sanitize_text_field($json["error"]), true);
        if ( $error != "" ) {
            // more friendly error messages
            if ( strpos($error, "cURL error 28:") !== false || strpos($error, "cURL error 7:") !== false) {
                return "SimSage Remote Upload Server (" . sanitize_text_field($server) . ") not responding";
            }
            return sanitize_text_field($server) . ": " . $error;
        }

    } else if ( isset( $json["errors"] ) ) {
        $json_error = $json["errors"];
        if ( isset( $json_error["http_request_failed"] ) ) {
            $hrf = $json_error["http_request_failed"];
            if ( isset( $hrf[0] ) ) {
                return sanitize_text_field($server) . ": " . $hrf[0];
            }
            $error = print_r( sanitize_text_field($json_error["http_request_failed"]), true);
            return sanitize_text_field($server) . ": " . $error;

        } else {
            $error = print_r(sanitize_text_field($json["errors"]), true);
            if ($error != "") {
                // more friendly error messages
                if (strpos($error, "cURL error 28:") !== false || strpos($error, "cURL error 7:") !== false) {
                    return "SimSage Remote Upload Server (" . sanitize_text_field($server) . ") not responding";
                }
                return sanitize_text_field($server) . ": " . $error;
            }
        }

    } else if ( isset($json["body"]) ) {
        // simsage itself has a specific internal error?
        $body = simsage_get_json($json["body"]);
        if ( isset($body["error"]) ) {
            $error = sanitize_text_field($body["error"]);
            if ($error != "") {
                return sanitize_text_field($server) . ": " . $error;
            }
        }

    } else if ( isset($json["response"]) ) {
        // finally, check the HTTP response code is within 200..299
        $response = simsage_get_json($json["response"]);
        if ( isset($response["code"]) ) {
            $response_code = sanitize_text_field($response["code"]);
            if ($response_code < 200 || $response_code > 299) {
                return "SimSage server (" . sanitize_text_field($server) . ") returned response code " . $response_code;
            }
        }
    }
    return "";
}


/**
 * Access the knowledge-base for the current user (or null if dne)
 * @return array|null return the knowledgeBase for this user
 */
function simsage_get_kb() {
    $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
    if ( isset($plugin_options["simsage_account"]) ) {
        $account = $plugin_options["simsage_account"];
        if ( isset($account["kbId"]) && isset($account["sid"]) ) {
            return array("kbId" => sanitize_text_field($account["kbId"]),
                         "sid" => sanitize_text_field($account["sid"]));
        }
    }
    return null;
}


/**
 * Access the plan for the current user (or null if dne)
 * @return array|null return the plan for this user
 */
function simsage_get_plan() {
    $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
    if ( isset($plugin_options["simsage_account"]) ) {
        $account = $plugin_options["simsage_account"];
        if ( isset( $account["plan"] ) ) {
            return $account["plan"];
        }
    }
    return null;
}



/**
 * get the user's id (organisationId) to use from our settings
 *
 * @return string|null the user's id
 */
function get_organisationId() {
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
function get_archive_md5() {
    $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
    if ( isset($plugin_options["archive_md5"]) ) {
        return sanitize_text_field($plugin_options["archive_md5"]);
    }
    return "";
}


/**
 * get the SimSage server to use from our settings
 *
 * @return string|null the SimSage server to use
 */
function get_server() {
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
 * Update the md5 last seen for the content of this site
 *
 * @param $archive_md5 string the new md5 for the file
 */
function update_archive_md5( $archive_md5 ) {
    $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
    $plugin_options["archive_md5"] = sanitize_text_field($archive_md5);
    update_option(SIMSAGE_PLUGIN_NAME, $plugin_options);
}


/**
 * get the user's email to use from our settings
 *
 * @return string|null the user's email address
 */
function get_email() {
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
 * Access the registration key for the current user (or empty string)
 * @return string return this user's subscription / registration key
 */
function simsage_get_registration_key() {
    $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
    if ( isset($plugin_options["simsage_registration_key"]) ) {
        return sanitize_text_field($plugin_options["simsage_registration_key"]);
    }
    return "";
}


/**
 * make sure the twice daily job type exists
 */
function simsage_setup_cron_schedule() {
    add_filter( 'cron_schedules', 'simsage_setup_archive_job_schedule' );
}


/**
 * @param $schedules array the schedules
 * @return mixed array the new schedule
 */
function simsage_setup_archive_job_schedule($schedules ) {
    if(!isset($schedules["twicedaily"])){
        $schedules["twicedaily"] = array(
            'interval' => 43200,
            'display' => __('Twice Daily'));
    }
    return $schedules;
}


/**
 * setup SimSage to execute itself twice daily
 * @param $admin object the admin class
 */
function simsage_setup_cron_job( $admin ) {
    if ( ! wp_next_scheduled( 'simsage_twicedaily' ) ) {
        wp_schedule_event(time(), 'twicedaily', 'simsage_twicedaily');
    }
    add_action('simsage_twicedaily', 'simsage_cron_upload_archive' );
}



/**
 * Create a ZIP compressed version of in_filename and write it to out_filename
 *
 * @param $in_filename  string the file to read and compress
 * @param $out_filename string the gzip file to create from in_filename
 * @return bool true if successful in creating the archive
 */
function simsage_compress_file($in_filename, $out_filename) {
    $in_file = fopen($in_filename, "rb");
    if ($in_file !== FALSE) {
        $out_file = fopen($out_filename, "wb");
        if ($out_file !== FALSE) {
            // write gzip header
            fwrite($out_file, "\x1F\x8B\x08\x08".pack("V", filemtime($in_filename))."\0\xFF", 10);
            // write the original file name
            $out_name = str_replace("\0", "", basename($in_filename));
            fwrite($out_file, $out_name."\0", 1+strlen($out_name));
            // add the deflate filter using default compression level
            $filter = stream_filter_append($out_file, "zlib.deflate", STREAM_FILTER_WRITE, -1);
            // set up the CRC32 hashing context
            $hash_context = hash_init("crc32b");
            // turn off the time limit
            if (!ini_get("safe_mode")) set_time_limit(0);
            $con = TRUE;
            $file_size = 0;
            while (($con !== FALSE) && !feof($in_file)) {
                // deflate works best with buffers >32K
                $con = fread($in_file, 64 * 1024);
                if ($con !== FALSE) {
                    hash_update($hash_context, $con);
                    $str_len = strlen($con);
                    $file_size += $str_len;
                    fwrite($out_file, $con, $str_len);
                }
            }
            // remove the deflate filter
            stream_filter_remove($filter);
            // write the CRC32 value
            // hash_final is a string, not an integer
            $crc = hash_final($hash_context, TRUE);
            // need to reverse the hash_final string so it's little endian
            fwrite($out_file, $crc[3].$crc[2].$crc[1].$crc[0], 4);
            // write the original uncompressed file size
            fwrite($out_file, pack("V", $file_size), 4);
            fclose($out_file);

            return TRUE;
        }
        fclose($in_file);
    }
    return FALSE;
}


/**
 * Return a sanitized array version of the JSON plan object
 *
 * @param $plan array a plan array
 * @return array the sanitized data
 */
function simsage_sanitize_plan( $plan ) {
    // operatorEnabled removed
    return array(
        "id" => isset($plan['id']) ? sanitize_text_field( $plan['id'] ) : "",
        "name" => isset($plan['name']) ? sanitize_text_field( $plan['name'] ) : "",
        "price" => isset($plan['price']) ? (int)sanitize_text_field( $plan['price'] ) : 0,
        "freeMonths" => isset($plan['freeMonths']) ? (int)sanitize_text_field( $plan['freeMonths'] ) : 0,
        "numDocs" => isset($plan['numDocs']) ? (int)sanitize_text_field( $plan['numDocs'] ) : 0,
        "numQA" => isset($plan['numQA']) ? (int)sanitize_text_field( $plan['numQA'] ) : 0,
        "operatorEnabled" => isset($plan['operatorEnabled']) ? (bool)sanitize_text_field($plan['operatorEnabled']) : false,
        "maxQueriesPerDay" => isset($plan['maxQueriesPerDay']) ? (int)sanitize_text_field( $plan['maxQueriesPerDay'] ) : 0,
        "upfrontCost" => isset($plan['upfrontCost']) ? (int)sanitize_text_field( $plan['upfrontCost'] ) : 0
    );
}


/**
 * Return a sanitized array version of the JSON registration response
 *
 * @param $response array the registration response to sanitize
 * @return array the sanitized data
 */
function simsage_sanitize_registration_response( $response ) {
    return array(
        "id" => isset($response['id']) ? sanitize_text_field( $response['id'] ) : "",
        "organisation" => isset($response['organisation']) ? sanitize_text_field( $response['organisation'] ) : "",
        "firstname" => isset($response['firstname']) ? sanitize_text_field( $response['firstname'] ) : "",
        "surname" => isset($response['surname']) ? sanitize_text_field( $response['surname'] ) : "",
        "phone" => isset($response['phone']) ? sanitize_text_field( $response['phone'] ) : "",
        "email" => isset($response['email']) ? sanitize_email( $response['email'] ) : "",
        "server" => isset($response['server']) ? sanitize_text_field( $response['server'] ) : "",
        "registrationKey" => isset($response['registrationKey']) ? sanitize_text_field( $response['registrationKey'] ) : "",
        "kbId" => isset($response['kbId']) ? sanitize_text_field( $response['kbId'] ) : "",
        "sid" => isset($response['sid']) ? sanitize_text_field( $response['sid'] ) : "",
        "trialEnd" => isset($response['trialEnd']) ? (int)sanitize_text_field( $response['trialEnd'] ) : 0,
        "plan" => simsage_sanitize_plan( $response['plan'] )
    );
}


/**
 * return the correct server to talk to
 *
 * @return array the appropriate API and Registration server urls
 */
function simsage_get_servers() {
    if ( SIMSAGE_USE_DEV ) {
        return array("api" => "http://" . SIMSAGE_DEV_IP . ":8088", "portal" => "http://" . SIMSAGE_DEV_IP .":4205");
    } else {
        return array("api" => "https://api.simsage.ai", "portal" => "https://portal.simsage.ai");
    }
}

/**
 * Get the overrideable template location. This will look for the file firstly at `<theme_dir>/simsage/<slug>.php`.
 * Failing that it will find the file in the local plugin directory
 *
 * @param $slug string of the template
 * @return string template location
 */
function simsage_get_overrideable_template( string $slug  ): string {
    $plugin_dir = SIMSAGE_PLUGIN_DIR;
    $theme_file = "simsage/$slug.php";

    if ( $overridden_template = locate_template( $theme_file ) ) {
        /*
         * locate_template() returns path to file.
         * if either the child theme or the parent theme have overridden the template.
         */
        return $overridden_template;
    } else {
        /*
         * If neither the child nor parent theme have overridden the template,
         * load from the <plugin_root>/inc directory
         */
        return "{$plugin_dir}inc/$slug.php";
    }
}

/**
 * Load template that can be overridden by placing the same slug within the `<theme_dir>/simsage` directory.
 *
 * @param $slug
 * @param array $args
 */
function simsage_load_overrideable_template( $slug, array $args = array() ) {
    $template = simsage_get_overrideable_template( $slug );
    load_template( $template, false, $args );
}

