<?php
/**
 * SimSage Search Rendering
 */
?>

<script lang="js">
    // set an image base for all our templates to use (url bases for images)
    server = "<?php echo $args['account_server']; ?>";

    // the settings for this application - no trailing / on the base_url please
    // it is imperative that we do not expose the SID here (securityID), as it is what protects your account from malicious use
    settings = {
        // the service layer end-point, change "<server>" to ... (no / at end)
        base_url: server,
        // the organisation's id to search - all sanitized
        organisation_id: "<?php echo $args['account_id']; ?>",
        // this is the WordPress plugin
        is_wordpress: true,
        // the knowledge base's Id (selected site) and security id (sid)
        kbId: "<?php echo $args['site_kbId']; ?>",
        // do we have an operator by plan?
        operator_enabled: <?php echo $args['operator_enabled']; ?>,
        context_label: "<?php echo $args['context_enabled']; ?>",
        context_match_boost: <?php echo $args['context_match_boost']; ?>,
        // QA sensitivity - controls the A.I's replies - we suggest you don't change it!
        bot_threshold: <?php echo $args['bot_threshold']; ?>,
    };
</script>

<div class="simsage-search <?php echo $args['simsage_classes']; ?>">

    <style>
        .search-form input[type=search] {
            background: #fff url(<?php echo $args['asset_folder'] ?>/images/dark-magnifying-glass.svg) no-repeat 9px center;
            width: <?php echo $args['simsage_search_width'] ?>px !important;
        }
    </style>

    <div class="search-bar">

        <!-- search box -->
        <div class="search-box-container">
            <form class="search-form" onkeydown="return event.key != 'Enter';" title="Search">
                <input type="search" value="" autocomplete="off" class="search-text search-text-<?php echo $args['search_counter']; ?>" maxlength="100"
                       placeholder="Search ..." onkeyup="simsage.search_typing(event, 'search-text-<?php echo $args['search_counter']; ?>')">
            </form>
        </div>

    </div>

    <!-- remove float left -->
    <br clear="both" />

</div>
