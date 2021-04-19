<?php
/**
 * SimSage Search Rendering
 */
?>

<script lang="js">
    // set an image base for all our templates to use (url bases for images)
    server = "<?php echo sanitize_text_field($this->get_account_setting("server")) ?>";

    // the settings for this application - no trailing / on the base_url please
    // it is imperative that we do not expose the SID here (securityID), as it is what protects your account from malicious use
    settings = {
        // the service layer end-point, change "<server>" to ... (no / at end)
        base_url: server,
        // the organisation's id to search - all sanitized
        organisation_id: "<?php echo sanitize_text_field($this->get_account_setting("id")) ?>",
        // this is the WordPress plugin
        is_wordpress: true,
        // the knowledge base's Id (selected site) and security id (sid)
        kbId: "<?php echo sanitize_text_field($this->get_site_setting("kbId")) ?>",
        // do we have an operator by plan?
        operator_enabled: <?php echo $this->get_plan_boolean_value("operatorEnabled", true) ?>,
        context_label: "<?php echo sanitize_text_field($this->context) ?>",
        context_match_boost: <?php echo sanitize_text_field($this->context_boost) ?>,
        // QA sensitivity - controls the A.I's replies - we suggest you don't change it!
        bot_threshold: <?php echo $this->get_user_value("bot_threshold", 0.8125) ?>,
        // show the advanced filters?
        show_advanced_filter: <?php echo $this->get_user_boolean_value("simsage_adv_filter", false) ?>,
    };
</script>

<div class="simsage-search <?php echo $this->get_user_value("simsage_styling", "") ?>">

    <style>
        .search-form input[type=search] {
            background: #fff url(<?php echo $this->asset_folder ?>/images/dark-magnifying-glass.svg) no-repeat 9px center;
            padding-left: 40px !important;
            width: <?php echo $this->get_user_value("simsage_search_width", 500) ?>px !important;
            border-color: #a0a0a0;
            color: #000;
            -webkit-border-radius: 10em;
            -moz-border-radius: 10em;
            border-radius: 10em;
        }
        .search-form input[type=search]:hover {
            outline: none;
        }
        .search-form input[type=search]:focus {
            cursor: auto;
            outline: none;
        }
    </style>

    <div class="search-bar">

        <!-- search box -->
        <div class="search-box-container">
            <form class="search-form" onkeydown="return event.key != 'Enter';" title="Search">
                <input type="search" value="" autocomplete="off" class="search-text search-text-<?php echo $this->search_counter ?>" maxlength="100"
                       placeholder="Search ..." onkeyup="simsage.search_typing(event, 'search-text-<?php echo $this->search_counter ?>')">
            </form>
        </div>

    </div>

    <!-- remove float left -->
    <br clear="both" />

</div>
