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
    <div class="search-bar">

        <!-- search options button, menu and chevron -->
        <div class="search-options-button no-select" title="Search options" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.toggle_menu()">
            <div class="search-options-button-inner">
                <span class="search-options-text">Search options</span>
                <!-- hidden needed by SimSage logic -->
                <input type="hidden" class="time-stamp" value="0" />
                <div class="search-options-chevron-image-box">&#x2304;</div>
            </div>

            <!-- ************************** -->
            <!-- advanced search filter box -->
            <div class="filter-box-view" style="display: none;" onclick="simsage.nop()" >
                <div class="filter-box" >
                    <div class="speech-bubble">
                        <div class="title">
                            <span class="search-by-text" title="Search by:">SEARCH BY:</span>
                            <span onclick="simsage.hide_menu(null)" title="Close" class="close-box">
                            <span tabindex="0" onkeyup="if (activation(event)) simsage.hide_menu(event)" class="close-image">&times;</span>
                            <span class="close-text">Close</span>
                        </span>
                        </div>
                        <table class="filter-table">
                            <tr class="tr-1">
                                <td class="col-1 sign-in-box" style="display: none;">
                                    <span class="category-text">Sign-in</span>
                                    <div class="category-item">
                                        <label class="sign-in-sel select-wrapper">
                                            <select name="sign-in" class="category-select dd-sign-in" tabindex="0"
                                                    onchange="simsage.do_change_domain()">
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td class="col-2 sign-in-text" style="display: none;">
                                        <span class="clear-text" title="sign-in" tabindex="0"
                                              onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.show_sign_in()">sign-in</span>
                                </td>
                                <td class="col-2 sign-out-text" style="display: none;">
                                    <span class="clear-text" title="sign-out" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.do_sign_out()">sign-out</span>
                                </td>

                                <td class="col-1">
                                    <span class="category-text">Document Type</span>
                                    <div class="category-item">
                                        <label class="document-type-sel select-wrapper">
                                            <select name="document-type" class="category-select" onchange="" tabindex="0">
                                                <option value="">All Document Types</option>
                                                <option value="html,htm">Web</option>
                                                <option value="doc,docx">Word</option>
                                                <option value="pdf">PDF</option>
                                                <option value="xls,xlsx">Excel</option>
                                                <option value="jpg,jpeg,png,gif">Images</option>
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td class="col-2">
                                    <span class="clear-text" title="Clear Document Type" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.reset_document_type()">Clear</span>
                                </td>

                                <td class="col-1 knowledge-base-selector" style="display: none;">
                                    <span class="category-text">Knowledge Base</span>
                                    <div class="category-item">
                                        <label class="knowledge-base-sel select-wrapper">
                                            <select name="knowledge-base" class="category-select dd-knowledge-base" tabindex="0"
                                                    onchange="simsage.on_change_kb()">
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td class="col-2 knowledge-base-selector" style="display: none;">
                                </td>

                                <td class="col-1" colspan="2">
                                    <span class="category-text">Source</span>
                                    <div class="category-item">
                                        <label class="source-sel select-wrapper">
                                            <select name="source" class="category-select dd-source" onchange="simsage.on_change_source()" tabindex="0">
                                            </select>
                                        </label>
                                    </div>
                                </td>

                                <td class="col-1" colspan="2">
                                    <span class="category-text">Title</span>
                                    <div class="category-item">
                                        <label>
                                            <input type="text" autocomplete="false" value="" maxlength="100" placeholder="Enter Title" class="category-input title-text" title="Enter Title">
                                        </label>
                                    </div>
                                </td>

                                <td class="col-1" colspan="2">
                                    <span class="category-text">URL</span>
                                    <div class="category-item">
                                        <label>
                                            <input type="text" value="" autocomplete="false" maxlength="100" placeholder="Enter URL" class="category-input url-text" title="Enter URL">
                                        </label>
                                    </div>
                                </td>

                                <td class="col-1" colspan="2">
                                    <span class="category-text">Author</span>
                                    <div class="category-item">
                                        <label>
                                            <input type="text" value="" autocomplete="false" maxlength="100" placeholder="Enter Author" class="category-input author-text" title="Enter Author">
                                        </label>
                                    </div>
                                </td>

                            </tr>

                            <tr>
                                <td class="col-1" colspan="2">&nbsp;</td>
                            </tr>

                        </table>
                    </div>
                    <div class="bar-bottom">
                    <span class="clear-text-box">
                        <span class="clear-all-text" title="Clear all" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.clear_all()">Clear all</span>
                    </span>
                        <span class="done-button" title="Done" onclick="simsage.hide_menu()">
                        <span tabindex="0" onkeyup="if (activation(event)) simsage.hide_menu(event)" class="done-text">Done</span>
                    </span>
                    </div>
                </div>
            </div>
        </div>


        <!-- search box -->
        <div class="search-box-container">
            <form class="search-form">
                <div class="search-text-box">
                    <label class="search-text-label" title="ask SimSage">
                        <input type="text" value="" autocomplete="false" class="search-text" maxlength="100" onkeyup="simsage.search_typing(event)">
                    </label>
                </div>

                <!-- search clear (cross) -->
                <div class="clear-search-button-box" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" title="clear your query" onclick="simsage.clear_search()">
                <span class="search-button-span-box">
                    <span class="clear-button-image">&times;</span>
                </span>
                </div>

                <!-- search button -->
                <button class="search-button-box" title="query SimSage" onclick="simsage.do_search()">
                    <span class="search-button-box-text">
                        SEARCH
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- remove float left -->
    <br clear="both" />

</div>
