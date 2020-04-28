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

        <!-- error message display bar -->
        <div class="error-dialog">
            <span class="close-button" onclick="this.parentElement.style.display='none'; analytics.close_error();">&times;</span>
            <div class="error-text"></div>
        </div>

        <div class="date-picker-box">
        <label><input type="text" class="datepicker tab-cursor" name="datepicker" value="" readonly /></label>
            <button onclick="analytics.getAnalytics()" class="button" title="Reload/Refresh statistical data">refresh</button>
        </div>

        <h2 class="nav-tab-wrapper">
            <span id="tab_keywords" onclick="analytics.select_tab('keywords')" class="nav-tab tab-cursor">Keywords</span>
            <span id="tab_searches" onclick="analytics.select_tab('searches')" class="nav-tab tab-cursor">Search Access</span>
            <span id="tab_logs" onclick="analytics.select_tab('logs')" class="nav-tab tab-cursor nav-tab-active">Download</span>
        </h2>

        <div id='layout'>

            <div id='div_keywords' class="container">
                <svg id="keyword-analytics" />
            </div>


            <div id='div_searches' class="container" style="display: none;">
                <svg id="search-analytics" />
            </div>

        </div>

        <div id='div_logs' style="display: none;">
            <div class="button-row">
                <button onclick="analytics.dlOperatorConversations()" class="button button-style">Operator Conversation Spreadsheet</button>
                <span class="button-help-text">Download a Spreadsheet containing all conversations between Operators and Clients.</span>
            </div>
            <div class="button-row">
                <button onclick="analytics.dlQueryLog()" class="button button-style">Search & Query Log Spreadsheet</button>
                <span class="button-help-text">Download a log of what people have been searching / asking on this site.</span>
            </div>
            <div class="button-row">
                <button onclick="analytics.dlLanguageCustomizations()" class="button button-style" title="Download a Spreadsheet of all QA Pairs and Language Customizations">Content Spreadsheet</button>
                <span class="button-help-text">Download a SimSage QA / language Sreadsheet containing all your customized content.</span>
            </div>
            <div class="button-row">
                <button onclick="analytics.dlContentAnalysis()" class="button button-style" title="Download a Content Analysis Spreadsheet">Content Analysis Spreadsheet</button>
                <span class="button-help-text">Download a Spreadsheet containing all crawled content and a Semantic analysis for each item.</span>
            </div>
        </div>

    </div>

</div>
