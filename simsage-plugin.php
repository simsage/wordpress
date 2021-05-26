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
define( 'SIMSAGE_USE_TEST', false );

// maximum length of a QA question or answer string
define( 'SIMSAGE_MAX_STRING_LENGTH', 256 );

// set timeout for json posts (in seconds) must not exceed PHP max_execution_time
define( 'SIMSAGE_JSON_POST_TIMEOUT', 10 );
// the timeout for json data archive uploads
define( 'SIMSAGE_JSON_DATA_UPLOAD_TIMEOUT', 15 );

// default page slug
define( 'SIMSAGE_DEFAULT_SEARCH_PAGE_SLUG', "simsage-search");

function simsage_query_vars( $vars ) {
    $vars[] = 'simsage_search';
    return $vars;
}

add_filter( 'query_vars', 'simsage_query_vars' );

// include the main search functionality class
include_once( SIMSAGE_PLUGIN_DIR . 'simsage_search.php' );
// include the admin settings page and menu-setup for WordPress of this plugin
include_once( SIMSAGE_PLUGIN_DIR . 'simsage_admin.php' );
// include gutenberg block code
require_once( SIMSAGE_PLUGIN_DIR . 'src/init.php' );

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

// Called when plugin is activated
function simsage_check_and_add_search_page() {
    global $wpdb;
    $page_slug = apply_filters( 'simsage_search_page_slug', SIMSAGE_DEFAULT_SEARCH_PAGE_SLUG );

    // insert a new search-result page if it doesn't exist yet
    if ( null === $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = '$page_slug'", 'ARRAY_A' ) ) {
        $current_user = wp_get_current_user();
        // create post object
        $page = array(
            'post_title'  => __( 'SimSage Search' ),
            'post_status' => 'publish',
            'post_author' => $current_user->ID,
            'post_type'   => 'page',
        );

        // insert the post into the database
        wp_insert_post( $page );
    }
}

add_action('init', 'simsage_check_and_add_search_page');

//
function simsage_inject_search_results( $content ) {
    $page_slug = apply_filters( 'simsage_search_page_slug', SIMSAGE_DEFAULT_SEARCH_PAGE_SLUG );
    $override = apply_filters( 'simsage_search_page_override', true );

    if ( $override && $page_slug === get_post()->post_name ) {
        return do_shortcode('[simsage-static-results]');
    }

    return $content;
}

add_filter('the_content', 'simsage_inject_search_results');

function simsage_filter_query( $query ) {
  if ( is_search() ) {
    $plugin_options = get_option( SIMSAGE_PLUGIN_NAME );

    if (array_key_exists( "simsage_override_default_search", $plugin_options )
        && $plugin_options["simsage_override_default_search"]
        && simsage_get_kb() != null) {
      $query->is_search = false;
      $search_query = $query->query_vars['s'];
      $search_page_slug = apply_filters( 'simsage_search_page_slug', SIMSAGE_DEFAULT_SEARCH_PAGE_SLUG );
      wp_redirect("$search_page_slug?simsage_search=$search_query");
    }
  }
}

add_filter('parse_query', 'simsage_filter_query');
