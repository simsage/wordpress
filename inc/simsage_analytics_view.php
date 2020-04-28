<?php
/**
 * the "view" of the SimSage analytics page
 */
?>



<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <style>
        .tab-cursor { cursor: pointer; }
    </style>

	<?php
	$options = get_option( PLUGIN_NAME );
    // when we have selected a site, this variable will be set
    $has_sites = isset($options['simsage_site']);
    ?>

    <script lang="js">
        // set an image base for all our templates to use (url bases for images)
        image_base = "<?php echo $this->asset_folder ?>";
        server = "<?php echo $this->get_account_setting("server") ?>";

        // the settings for this application - no trailing / on the base_url please
        settings = {
            // api version of ws_base
            api_version: 1,
            // the service layer end-point, set after logging in
            base_url: server + 'api',
            // the organisation's id to search
            organisationId: "<?php echo $this->get_account_setting("id") ?>",
            // the knowledge base's Id (selected site) and security id (sid)
            kbId: "<?php echo $this->get_site_setting("kbId") ?>",
            // the operator uses the SecurityID to verify themselves, do not expose it to your web users!
            sid: "<?php echo $this->get_site_setting("sid") ?>",
        };

    </script>

    <div class="analytics-area">

        <h2 class="nav-tab-wrapper">
            <span id="tab_keywords" onclick="analytics.select_tab('keywords')" class="nav-tab tab-cursor nav-tab-active">Keywords</span>
            <span id="tab_searches" onclick="analytics.select_tab('searches')" class="nav-tab tab-cursor">Search Access</span>
            <span id="tab_logs" onclick="analytics.select_tab('logs')" class="nav-tab tab-cursor">Download</span>
        </h2>

        <div id='layout'>

            <div id='div_keywords' class="container">
                <svg id="keyword-analytics" />
            </div>

            <div id='div_searches' class="container" style="display: none;">
                <svg id="search-analytics" />
            </div>

            <div id='div_logs' style="display: none;">
            </div>

        </div>

    </div>

</div>
