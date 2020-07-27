<?php
   /*
   Plugin Name: SimSage Search
   Plugin URI: https://simsage.ai/
   description: Add SimSage Semantic Search and bots to your WordPress site.
   Version: 1.0
   Author: Rock de Vocht
   Author URI: https://simsage.ai/
   License: GPL2
   */

// define the location of the plugin directory for all includes
define( 'PLUGIN_DIR', dirname(__FILE__) . '/' );
define( 'PLUGIN_NAME', 'simsage-search' );

// SimSage registration-api-server address
define( 'SIMSAGE_API_SERVER', 'https://api.simsage.ai');
// define( 'SIMSAGE_API_SERVER', 'http://192.168.1.132:8088');
// define( 'SIMSAGE_API_SERVER', 'http://192.168.1.89:8088');
// define( 'SIMSAGE_API_SERVER', 'https://pp-rego-cloud.simsage.nz');

// SimSage registration-ui-server address
define( 'SIMSAGE_REGO_SERVER', 'https://simsage.ai');
// define( 'SIMSAGE_REGO_SERVER', 'http://localhost:4205');
// define( 'SIMSAGE_REGO_SERVER', 'https://pp-rego.simsage.nz');

// SimSage special content file-names - do not change these
// these are used to transmit Bot, synonym, and semantic information to SimSage
// alongside other content from WordPress
define( 'DOC_BOT_DATA', '-simsage-bot.txt');
define( 'DOC_SYNONYM_DATA', '-simsage-synonyms.txt');
define( 'DOC_SEMANTIC_DATA', '-simsage-semantics.txt');

// maximum length of a bot question or answer string
define( 'MAX_STRING_LENGTH', 256 );

// set timeout for json posts (in seconds) must not exceed PHP max_execution_time
define( 'JSON_POST_TIMEOUT', 10 );

// include the main search functionality class
include_once( PLUGIN_DIR . 'simsage_search.php' );
// include the admin settings page and menu-setup for WordPress of this plugin
include_once( PLUGIN_DIR . 'simsage_admin.php' );
// include the SimSage operator interface
include_once( PLUGIN_DIR . 'simsage_operator.php' );
// include the SimSage analytics interface
include_once( PLUGIN_DIR . 'simsage_analytics.php' );

// create the main search class to use throughout
$search = new simsage_search();

// create the admin panel and its functionality
$admin = new simsage_admin();

// create the SimSage operator
$operator = new simsage_operator();

// create SimSage analytics
$analytics = new simsage_analytics();

// add the pages for the SimSage plugin
$admin->prepare_admin_menus( $operator, $analytics );
$admin->add_admin_menus();

// setup hooks into the main class for plugin activation / de-activation
register_activation_hook(__FILE__, array($search, 'plugin_activate'));
register_deactivation_hook(__FILE__, array($search, 'plugin_deactivate'));
