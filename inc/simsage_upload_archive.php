<?php

include SIMSAGE_PLUGIN_DIR . 'inc/simsage_utilities.php';


/**
 * run the archive upload job
 */
function simsage_cron_upload_archive() {
    debug_log("CRON: SimSage uploading archive");
    // just save the pages
    $temp_errors = array();
    if ( update_simsage( $temp_errors ) ) {
        debug_log("CRON: simsage_cron_upload_archive(): success");
    }
}


/**
 * Create a gzip file of all the content we're to send to SimSage in the temp folder of this machine
 * return its file-name on success, or null on failure (and sets a settings-error in that case)
 * Queries the WordPress database (wpdb) for its content
 *
 * @param $plan array your subscription plan
 * @package $errors array an error array
 * @return array the filename to the zip-file in its temporary file location and its md5 sum or (null, null)
 */
function create_content_archive( $plan, &$errors ) {
    if ( $plan != null ) {
        $registration_key = simsage_get_registration_key();

        $filename = tempnam(get_temp_dir(), "simsage");
        $archive_file = fopen($filename, "wb");

        $plugin_options = get_option(SIMSAGE_PLUGIN_NAME);
        $num_docs = (int)$plan["numDocs"];
        $num_qas = (int)$plan["numQA"];
        if ($archive_file) {
            debug_log("starting " . $filename);

            $content_md5 = simsage_add_wp_contents_to_archive( $registration_key, $archive_file, $num_docs, get_ignore_urls(), $errors );
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
            add_error( $errors, "Failed to create archive file(s) (could not create a temporary file on your system)", $type = 'error');
            debug_log("Failed to create archive file(s) (could not create a temporary file on your system)");
        }
    } else {
        add_error( $errors, "invalid plan (null)", $type = 'error' );
        debug_log("invalid plan (null)");
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
 * @param $errors           array of error object
 * @return bool return true on success
 */
function upload_archive( $server, $organisationId, $kbId, $sid, $filename, &$errors ) {
    debug_log("uploading archive " . $filename . ", to: " . $server . ", org: " . $organisationId . ", kb: " . $kbId);
    $fileContent = file_get_contents($filename);
    $data = ";base64," . base64_encode($fileContent);
    $url = simsage_join_urls($server, '/api/crawler/document/upload/archive');
    $bodyStr = '{"organisationId": "' . $organisationId . '", "kbId": "' . $kbId . '", "sid": "' . $sid . '", "sourceId": 1, "data": "' . $data . '"}';
    $json = simsage_get_json(wp_remote_post($url,
        array('timeout' => SIMSAGE_JSON_DATA_UPLOAD_TIMEOUT,
            'headers' => array('accept' => 'application/json', 'API-Version' => '1', 'Content-Type' => 'application/json'),
            'body' => $bodyStr)));
    $error_str = simsage_check_json_response( $server, $json );
    if ( strpos( $error_str, "not time yet ") ) {
        add_error( $errors, "Content upload scheduled for later", $type = 'info');
        debug_log('ERROR: simsage-upload-error: Content upload scheduled for later');
        return false;

    } else {
        if ($error_str != "") {
            add_error( $errors, $error_str, $type = 'error' );
            debug_log('ERROR: simsage-upload-error:' . $error_str);
            return false;
        }
    }
    return true;
}


/**
 * Check all the settings are valid, create a ZIP of the current content and various language changes
 * and send them all to SimSage for processing
 *
 * @param $errors array an array of error object items
 * @return bool success, or false if anything went wrong
 */
function update_simsage( &$errors ) {
    debug_log("update SimSage content");
    $plan = simsage_get_plan();
    $kb = simsage_get_kb();
    if ( $plan != null && $kb != null ) {
        $organisationId = get_organisationId();
        if ($organisationId == null) {
            add_error( $errors, 'The user\'s id cannot be found.  Please login to SimSage again.', $type = 'error' );
            debug_log('ERROR: simsage-invalid-id: the user\'s id cannot be found.  Please login to SimSage again.');
            return false;
        }
        $server = get_server();
        if ($server == null) {
            add_error( $errors, 'The SimSage server settings cannot be found.  Please login to SimSage again.', $type = 'error');
            debug_log('ERROR: simsage-invalid-server: SimSage server settings cannot be found.  Please login to SimSage again.');
            return false;
        }

        // and index / re-index the data associated with this site
        $file_md5 = create_content_archive( $plan, $errors );
        $filename = $file_md5[0];
        $file_md5 = $file_md5[1];
        if ($filename != null) {
            // check its md5
            $md5_sum = get_archive_md5();
            debug_log("wrote zip to:" . $filename . ", old md5:" . $md5_sum . ", current md5:" . $file_md5);

            if ( !upload_archive( $server, $organisationId, $kb["kbId"], $kb["sid"], $filename, $errors ) ) {
                return false;
            }

            // update the md5 for the next run
            update_archive_md5( $file_md5 );

            add_error( $errors, 'Content Successfully uploaded to SimSage', $type = 'info');
            debug_log('SUCCESS: simsage archive uploaded.');

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

