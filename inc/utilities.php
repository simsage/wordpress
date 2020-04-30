<?php

/**
 * misc utilities for helping the plugin
 *
 */


/**
 * check both php and wp versions are as we need them to be or die if they're not appropriate
 * and leave the user with a message advising them what to do
 *
 */
function check_versions() {
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
			error_log( print_r( $log, true ) );
        } else {
			error_log( $log );
		}
	}
}


/**
 * convert a data object to a json object
 * @param $data string|object
 * @return mixed
 */
function get_json( $data ) {
	if (gettype($data) == "string") {
		return json_decode($data, true, 512);
	} else {
		return json_decode(json_encode($data), true, 512);
	}
}


/**
 * query word-press' content and add all published items to a zip archive
 *
 * @param $zip ZipArchive a zip archive to add items to
 */
function add_wp_contents_to_zip( $zip ) {
    global $wpdb;
    $query = "SELECT * FROM $wpdb->posts WHERE post_status = 'publish'";
    $results = $wpdb->get_results($query);
    $counter = 1;
    foreach ($results as $row) {
        $obj = $row;
        // format: url | title | mimeType | created | last-modified | data
        $str = $obj->guid . "|" . $obj->post_title . "|text/html|";
        $str = $str . strtotime($obj->post_date_gmt) . "000|";
        $str = $str . strtotime($obj->post_modified_gmt) . "000|" . $counter . ".html;base64,";
        // construct our "html" base64 string for SimSage
        $base64Str = base64_encode("<html lang='en'>" . $obj->post_content . "</html>");
        // no CRs please
        $base64Str = str_replace("\n", "", $base64Str);
        $str = $str . $base64Str . "\n";
        $zip->addFromString($counter . ".html", $str);
        $counter += 1;
    }
}


/**
 * Add all bot QA items to the zip file passed in as a string in a file called DOC_BOT_DATA
 *
 * @param $zip ZipArchive a zip archive to add items to
 * @param $qa_list array a list of Question and Answer items
 */
function add_bot_qas_to_zip( $zip, $qa_list ) {
    $str = "";
    foreach ($qa_list as $qa) {
        // format: url | title | mimeType | created | last-modified | data
        $str .= $qa["id"] . "|" . $qa["question"] . "|" . $qa["answer"] . "|" . $qa["link"] . "\n";
    }
    $zip->addFromString(DOC_BOT_DATA, $str);
}


/**
 * Add all Synonyms to the zip file passed in as a string in a file called DOC_SYNONYM_DATA
 *
 * @param $zip ZipArchive a zip archive to add items to
 * @param $synonym_list array a list of synonym items
 */
function add_synonyms_to_zip( $zip, $synonym_list ) {
    $str = "";
    foreach ($synonym_list as $synonym) {
        // format: url | title | mimeType | created | last-modified | data
        $str .= $synonym["id"] . "|" . $synonym["words"] . "\n";
    }
    $zip->addFromString(DOC_SYNONYM_DATA, $str);
}


/**
 * helper - check a string is valid for either a bot question or answer
 * @param $str
 * @return string|null return a string with an error, or null if there is none
 */
function is_valid_bot_str( $str ) {
    $invalid_chars = array( "(", ")", "|", "[", "]", "{", "}");
    if ( trim($str) == "" ) return "string is empty";
    if ( strlen( trim($str) ) > MAX_STRING_LENGTH ) return "string too long (maximum length allowed is " . MAX_STRING_LENGTH . " characters)";
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
function is_valid_bot_qa_pair( $id, $question, $answer ) {
    $error1 = is_valid_bot_str($question);
    if ( $error1 != null) return "Question " . $id . ": " . $error1;
    $error2 = is_valid_bot_str($answer);
    if ( $error2 != null) return "Answer " . $id . ": " . $error2;
    return null;
}


/**
 * helper - check a string is valid synonym
 * @param $str
 * @return string|null return a string with an error, or null if there is none
 */
function is_valid_synonym_str( $str ) {
    $invalid_chars = array( "(", ")", "|", "[", "]", "{", "}", "-", ",", "_");
    if ( trim($str) == "" ) return "string is empty";
    $words = explode(",", $str);
    if ( count($words) > 2 ) return "no more than two words for a synonym";
    foreach ($words as $word) {
        if ( strlen( trim($word) ) > 20 ) return "word too long (maximum length per word is 20 characters)";
        foreach ($invalid_chars as $ic) {
            if (strpos($str, $ic)) return "string must not contain " . $ic . " character(s)";
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
function is_valid_synonym( $id, $synonym ) {
    if ( trim($synonym) == "" ) return "Synonym " . $id . " string is empty";
    $words = explode(",", $synonym);
    if ( count($words) == 1 ) return "Synonym " . $id . " must have comma separated words that are synonymous to it";
    foreach ($words as $word) {
        $error1 = is_valid_synonym_str($word);
        if ( $error1 != null) return "Synonym " . $id . ": " . $error1;
    }
    return null;
}


/**
 * Join two urls together with a / in between
 * @param $url1 string first part of the url
 * @param $url2 string second part of the url
 * @return string the joined url
 */
function join_urls( $url1, $url2 ) {
    if ( substr( $url1, strlen($url1) - 1, 1 ) === "/" && substr( $url1, 0, 1 ) === '/' ) {
        return $url1 . substr($url2, 1);
    } else if ( substr( $url1, strlen($url1) - 1, 1 ) === "/" || substr( $url1, 0, 1 ) === '/' ) {
        return $url1 . $url2;
    }
    return $url1 . "/" . $url2;
}


/**
 * @param $json array the data returned by SimSage from the server
 * @return string an empty string if no error was found, otherwise a description of the error
 */
function check_simsage_json_response($json) {
    // debug_log( print_r($json, true) );
    if ( isset($json["error"]) ) {
        $error = print_r( $json["error"], true);
        if ( $error != "" ) {
            // more friendly error messages
            if ( strpos($error, "cURL error 28:") !== false || strpos($error, "cURL error 7:") !== false) {
                return "SimSage Remote Upload Server not responding";
            }
            return $error;
        }

    } else if ( isset($json["errors"]) ) {
        $error = print_r( $json["errors"], true);
        if ( $error != "" ) {
            // more friendly error messages
            if ( strpos($error, "cURL error 28:") !== false || strpos($error, "cURL error 7:") !== false) {
                return "SimSage Remote Upload Server not responding";
            }
            return $error;
        }

    } else if ( isset($json["body"]) ) {
        // simsage itself has a specific internal error?
        $body = get_json($json["body"]);
        if ( isset($body["error"]) ) {
            $error = $body["error"];
            if ($error != "") {
                return $error;
            }
        }

    } else if ( isset($json["response"]) ) {
        // finally, check the HTTP response code is within 200..299
        $response = get_json($json["response"]);
        if ( isset($response["code"]) ) {
            $response_code = $response["code"];
            if ($response_code < 200 || $response_code > 299) {
                return "SimSage server return response code " . $response_code;
            }
        }
    }
    return "";
}


/**
 * Access the knowledge-base for the current user (or null if dne)
 * @return array|null return the knowledgeBase for this user
 */
function get_kb() {
    $plugin_options = get_option(PLUGIN_NAME);
    if ( isset($plugin_options["simsage_account"]) ) {
        $account = $plugin_options["simsage_account"];
        if ( isset( $account["knowledgeBase"] ) ) {
            return $account["knowledgeBase"];
        }
    }
    return null;
}


/**
 * Access the plan for the current user (or null if dne)
 * @return array|null return the plan for this user
 */
function get_plan() {
    $plugin_options = get_option(PLUGIN_NAME);
    if ( isset($plugin_options["simsage_account"]) ) {
        $account = $plugin_options["simsage_account"];
        if ( isset( $account["plan"] ) ) {
            return $account["plan"];
        }
    }
    return null;
}
