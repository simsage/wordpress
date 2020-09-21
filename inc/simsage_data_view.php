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

    <div class="analytics-area">
        <h2>SimSage Data</h2>
    </div>

    <?php
    // set when connected to SimSage
    $has_sites = (get_kb() != null);
    ?>

    <?php if ( $has_sites ) { ?>

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
            <span class="close-button" onclick="this.parentElement.style.display='none'; data.close_error();">&times;</span>
            <div class="error-text"></div>
        </div>

        <div class="date-picker-box">
        <label><input type="text" id="txtDatePicker" class="datepicker tab-cursor" name="datepicker" value="" readonly /></label>
            <button onclick="data.getAnalytics()" class="button" title="Reload/Refresh statistical data">refresh</button>
        </div>

        <h2 class="nav-tab-wrapper">
            <span id="tab_keywords" onclick="data.select_tab('keywords')" class="nav-tab tab-cursor nav-tab-active">Keywords</span>
            <span id="tab_searches" onclick="data.select_tab('searches')" class="nav-tab tab-cursor">Search Access</span>
            <span id="tab_logs" onclick="data.select_tab('logs')" class="nav-tab tab-cursor">Download</span>
            <span id="tab_qna" onclick="data.select_tab('qna')" class="nav-tab tab-cursor">Q&A</span>
            <span id="tab_synonyms" onclick="data.select_tab('synonyms')" class="nav-tab tab-cursor">Synonyms</span>
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
                <button onclick="data.dlOperatorConversations()" class="button button-style">Operator Conversation Spreadsheet</button>
                <span class="button-help-text">Download a Spreadsheet containing all conversations between Operators and Clients for the selected month.</span>
            </div>
            <div class="button-row">
                <button onclick="data.dlQueryLog()" class="button button-style">Search & Query Log Spreadsheet</button>
                <span class="button-help-text">Download a log of what people have been searching / asking on this site for the selected month.</span>
            </div>
            <div class="button-row">
                <button onclick="data.dlLanguageCustomizations()" class="button button-style" title="Download a Spreadsheet of all QA Pairs and Language Customizations">Content Spreadsheet</button>
                <span class="button-help-text">Download a SimSage QA / language Spreadsheet containing all your customized content (not month specific).</span>
            </div>
            <div class="button-row">
                <button onclick="data.dlContentAnalysis()" class="button button-style" title="Download a Content Analysis Spreadsheet">Content Analysis Spreadsheet</button>
                <span class="button-help-text">Download a Spreadsheet containing all currently crawled content and a Semantic analysis for each item (not month specific).</span>
            </div>
        </div>

        <div id='div_qna' class="qna" style="display: none;">
            <div class="simsage-find-box">
                <div class="find-label">find items in the mind</div>
                <div class="find-dialog">
                    <label><input type="text" value="" spellcheck="false" autoFocus class="find-text-style"
                           onKeyUp="data.handleMindItemKey(event.keyCode)" onChange="data.setMindItemFilter(event.target.value)" /></label>
                </div>
                <div class="find-image-box">
                    <img class="find-image"
                         onClick="data.getMindItems()"
                         src="<?php echo $this->asset_folder . 'images/dark-magnifying-glass.svg'?>" title="search" alt="search"/>
                </div>
            </div>
            <div>
                <table class="simsage-find-table">
                    <thead>
                        <tr class="table-header">
                            <td class="id-column">id</td>
                            <td class="question-column">question</td>
                            <td class="action-column">actions</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3">
                                <span>
                                    upload
                                </span>
                                <span>
                                    export
                                </span>
                                <span>
                                    new
                                </span>
                                <span>
                                    delete all
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id='div_synonyms' style="display: none;">
        </div>


    </div>

    <?php } else { ?>
        <div class="analytics-area">
            <div class="label-success">Please <a href="/wp-admin/options-general.php?page=simsage-search">configure</a> your SimSage plugin first.</div>
        </div>
    <?php } ?>

</div>
