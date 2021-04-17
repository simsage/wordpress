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
        // never show advanced filter on default search override
        show_advanced_filter: false,
    };
</script>

<div class="default-search-bar">

    <!-- search box -->
    <form class="default-search-form" onkeydown="return event.key != 'Enter';" title="Search">
        <input type="text" value="" autocomplete="off" class="default-search-text search-text-<?php echo $this->search_counter ?>" maxlength="100"
               placeholder="Search ..." onkeyup="simsage.search_typing(event, 'search-text-<?php echo $this->search_counter ?>')">
    </form>

</div>
