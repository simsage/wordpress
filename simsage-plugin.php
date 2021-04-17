<?php
   /*
   Plugin Name: SimSage Search
   Plugin URI: https://simsage.ai/
   description: Add SimSage Semantic Search and operator support to your WordPress site.
   Version: 1.4.0
   Author: Rock de Vocht
   Author URI: https://github.com/peter3125
   License: GPL2
   */

// define the location of the plugin directory for all includes
define( 'SIMSAGE_PLUGIN_DIR', dirname(__FILE__) . '/' );
define( 'SIMSAGE_PLUGIN_NAME', 'simsage-search' );

// SimSage special content file-names - do not change these
// these are used to transmit QA, synonym, and semantic information to SimSage
// alongside other content from WordPress
define( 'SIMSAGE_DOC_WP_DATA', '-simsage-wp.txt');
define( 'SIMSAGE_DOC_BOT_DATA', '-simsage-bot.txt');
define( 'SIMSAGE_DOC_SYNONYM_DATA', '-simsage-synonyms.txt');
define( 'SIMSAGE_DOC_SEMANTIC_DATA', '-simsage-semantics.txt');
define( 'SIMSAGE_DOC_IGNORE_DATA', '-simsage-ignore.txt');
// enable usage of test environment
define( 'SIMSAGE_USE_TEST', true );

// maximum length of a QA question or answer string
define( 'SIMSAGE_MAX_STRING_LENGTH', 256 );

// set timeout for json posts (in seconds) must not exceed PHP max_execution_time
define( 'SIMSAGE_JSON_POST_TIMEOUT', 10 );
// the timeout for json data archive uploads
define( 'SIMSAGE_JSON_DATA_UPLOAD_TIMEOUT', 15 );

// include the main search functionality class
include_once( SIMSAGE_PLUGIN_DIR . 'simsage_search.php' );
// include the admin settings page and menu-setup for WordPress of this plugin
include_once( SIMSAGE_PLUGIN_DIR . 'simsage_admin.php' );

// create the main search class to use throughout
$search = new simsage_search();

// create the admin panel and its functionality
$admin = new simsage_admin();

// add the pages for the SimSage plugin
$admin->add_admin_menus();
// setup auto-update for content
simsage_setup_cron_schedule();
simsage_setup_cron_job( $admin );

// setup hooks into the main class for plugin activation / de-activation
register_activation_hook(__FILE__, array($search, 'plugin_activate'));
register_deactivation_hook(__FILE__, array($search, 'plugin_deactivate'));
