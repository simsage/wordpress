<?php
/**
 * the "view" of the SimSage plugin
 */
?>

<?php
$plan = simsage_get_plan();
$options = get_option(SIMSAGE_PLUGIN_NAME);
$has_account = isset($options['simsage_account'] ) && isset($options['simsage_account']['id']);
$has_kb = $has_account && isset($options['simsage_account']['kbId']);
$has_access = ($plan != null);
// does this plan have operator access?
$operator_has_access = ($plan != null && isset($plan['operatorEnabled']) && $plan['operatorEnabled']);
// when we have selected a site, this variable will be set
$has_sites = (simsage_get_kb() != null);
// and is the operator enabled?
$using_bot = !isset($options["simsage_use_bot"]) || $options["simsage_use_bot"] != '0';
$active_tab = '';
if( isset( $_GET[ 'tab' ] ) ) {
    $active_tab = sanitize_text_field($_GET[ 'tab' ]);
} // end if

// add the nonce, option_page, action and referer.
settings_fields( SIMSAGE_PLUGIN_NAME );
do_settings_sections( SIMSAGE_PLUGIN_NAME );

// list of bot items (or initial empty array)
$qa_list = isset($options['simsage_qa']) ? $options['simsage_qa'] : array();
// list of synonyms
$synonym_list = isset($options['simsage_synonyms']) ? $options['simsage_synonyms'] : array();
?>

<script lang="js">

    all_urls = [<?php echo '"'.implode('","', simsage_get_wp_contents()).'"' ?>];
    ignore_urls = [<?php echo '"'.implode('","', get_ignore_urls()).'"' ?>];
    available_urls = [];

    // setup available urls
    for (let i in all_urls) {
        if (all_urls.hasOwnProperty(i)) {
            let url = all_urls[i];
            if (!ignore_urls.includes(url)) {
                available_urls.push(url);
            }
        }
    }
    // set an image base for all our templates to use (url bases for images)
    image_base = "<?php echo sanitize_text_field($this->asset_folder) ?>";
    server = "<?php echo sanitize_text_field($this->get_account_setting("server")) ?>";

    // set the active tab
    active_tab = "<?php echo sanitize_text_field($active_tab) ?>";

    // the settings for this application - no trailing / on the base_url please

    settings = {
        // the service layer end-point, set after logging in
        base_url: server,
        // the service layer end-point, set after logging in
        ops_base_url: server + 'api',
        // api version of ws_base
        api_version: 1,
        // web sockets platform endpoint for comms
        ws_base: server + 'ws-api',
        // the organisation's id to search
        organisationId: "<?php echo sanitize_text_field($this->get_account_setting("id")) ?>",
        // the knowledge base's Id (selected site) and security id (sid)
        kbId: "<?php echo sanitize_text_field($this->get_site_setting("kbId")) ?>",
        // the operator uses the SecurityID to verify themselves, do not expose it to your web users!
        sid: "<?php echo sanitize_text_field($this->get_site_setting("sid")) ?>",
        // bot settings
        bot_enabled: <?php echo $using_bot ? "true" : "false" ?>,
        // valid image types
        image_types: [".jpg", ".jpeg", ".png", ".gif", ".svg"]
    };

</script>



<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <style>
        fieldset {margin-bottom: 20px;}
        .location-label { font-weight: 600; font-size: 1.1em; margin-top: 10px; text-transform: uppercase; margin-bottom: 10px; }
        .label-success { font-weight: 600; font-size: 1.1em; margin-top: 10px; margin-bottom: 20px; line-height: 20px; }
        .radio_label { margin-right: 4px; font-weight: bold;}
        .tab-cursor { cursor: pointer; }
        .tabbed-display { margin-top: 20px; margin-left: 10px; }
        .tab-disabled { color: #ccc; cursor: default; pointer-events: none; }
        .two-lists-side-by-side {
            display: inline-block;
            width: 840px; height: 400px;
            margin: 10px 0 0 20px !important;
            cursor: default;
        }
        .boxes { float: left; }
        .box-1 { float: left; margin: 0 20px 0 0 !important; width: 380px; height: 390px; }
        .box-2 { float: left; margin: 0 0 0 0 !important; width: 380px; height: 390px; }
        .filter { margin-bottom: 10px; }
        .available-list { padding: 2px; height: 370px; max-height: 370px; overflow: auto; border: 1px solid #878787; border-radius: 2px; }
        .ignore-list { padding: 2px; height: 370px; max-height: 370px; overflow: auto; border: 1px solid #878787; border-radius: 2px; }
        .instructions { font-size: 14px; margin-bottom: 10px;}
        .title { font-size: 12px; font-style: italic; margin-bottom: 10px;}
        .url { font-size: 12px; cursor: pointer;}
        .spacing-bottom { height: 120px; }
    </style>

    <div id="icon-themes" class="icon32"></div>

    <?php foreach ($this->errors as $error) { ?>
        <div id="setting-error-error" class="notice notice-<?php echo $error["type"]; ?> settings-error is-dismissible id-<?php echo $error["id"]; ?>">
            <p><strong><?php echo $error["message"]; ?></strong></p>
            <button type="button" class="notice-dismiss" onclick='jQuery(".id-<?php echo $error["id"]; ?>").hide()'>
                <span class="screen-reader-text">Dismiss this notice.</span></button>
        </div>
    <?php } ?>

    <?php if ( SIMSAGE_USE_DEV ) { ?>
        <div class="label-success">
            DEVELOPMENT: <?php echo "http://" . SIMSAGE_DEV_IP . ":8088"; ?><br />
        </div>
    <?php } ?>

    <?php if ( !$has_account && ($active_tab == 'account' || $active_tab == '') ) { ?>
        <div class="label-success">
            Please enter your SimSage registration key below.<br />
            <a href="<?php echo $this->get_portal_server(); ?>/#/create?origin=plugin" target="_blank">Register here</a> if you don't have an account.
        </div>
    <?php } ?>

    <div class="nav-tab-wrapper">
        <a href="?page=simsage&tab=account" class="nav-tab <?php echo ($active_tab == 'account' || $active_tab == '') ? 'nav-tab-active' : ''; ?>">Account</a>
        <a href="?page=simsage&tab=search" class="nav-tab <?php echo $active_tab == 'search' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Search</a>
        <a href="?page=simsage&tab=filters" class="nav-tab <?php echo $active_tab == 'filters' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Content Filters</a>
        <a href="?page=simsage&tab=styling" class="nav-tab <?php echo $active_tab == 'styling' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Styling</a>

        <?php if ( $has_sites && $has_access ) { ?>
            <a href="?page=simsage&tab=keywords" class="nav-tab <?php echo $active_tab == 'keywords' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Keywords</a>
            <a href="?page=simsage&tab=searches" class="nav-tab <?php echo $active_tab == 'searches' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Search Access</a>
            <a href="?page=simsage&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Download</a>
            <a href="?page=simsage&tab=qna" class="nav-tab <?php echo $active_tab == 'qna' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Q&A</a>
            <a href="?page=simsage&tab=synonyms" class="nav-tab <?php echo $active_tab == 'synonyms' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Synonyms</a>
            <a href="?page=simsage&tab=semantics" class="nav-tab <?php echo $active_tab == 'semantics' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Semantics</a>
        <?php } ?>

        <?php if ( $has_access && $has_sites && $using_bot ) { ?>
            <a href="?page=simsage&tab=operator" class="nav-tab <?php echo $active_tab == 'operator' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Operator</a>
        <?php } ?>

    </div>

    <form method="post" id="adminForm" onkeydown="return event.key != 'Enter';" name="<?php echo SIMSAGE_PLUGIN_NAME; ?>_search_options">

        <div id="busy" class="busy" style="display: none;">
            <div class="busy-image"><img id="hourglass" src="" alt="hourglass" class="busy-image-size"></div>
        </div>

        <!-- error message display bar -->
        <div class="error-dialog" style="display: none;">
            <span class="close-button" onclick="this.parentElement.style.display='none'; data.close_error();">&times;</span>
            <div class="error-text"></div>
        </div>

        <!-- info message display bar -->
        <div class="info-dialog" style="display: none;">
            <span class="close-button" onclick="this.parentElement.style.display='none'; data.close_error();">&times;</span>
            <div class="info-text"></div>
        </div>

        <?php if ( $has_sites && $has_access && ( $active_tab == 'keywords' || $active_tab == 'searches'  || $active_tab == 'logs' ) ) { ?>
            <div class='analytics-area'>
                <div class="date-picker-box">
                    <label>
                        <input type="text" id="txtDatePicker" class="datepicker tab-cursor" name="datepicker" value="" readonly />
                    </label>
                    <button type="button" onclick="data.getAnalytics()" class="button" title="Reload/Refresh statistical data">refresh</button>
                </div>
            </div>
        <?php } ?>


        <?php if ( $active_tab == 'account' || $active_tab == '' ) { ?>
            <div class="tabbed-display">

                <!-- check if account has been set - in which case we have a valid setup -->
                <fieldset>
                    <div style="margin-bottom: 10px;">NB. Registration-keys are different for different locations, make sure you select your correct location first!</div>
                    <label>
                        <input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_registration_key]" type="text"
                               class="input-field" id="simsage_registration_key" maxlength="20"
                               value="<?php echo (isset($options['simsage_registration_key']) && $options['simsage_registration_key'] != '') ? $options['simsage_registration_key'] : ''; ?>"
                               placeholder="your SimSage Registration Key"/>
                        <span class="description">Please enter your SimSage Registration-key</span>
                    </label>
                </fieldset>

                <fieldset>
                    <label>
                        <span class="description">don't have a Registration-key?&nbsp;&nbsp;<a href="<?php echo $this->get_portal_server(); ?>/#/create?origin=plugin" target="_blank">Register here</a></span>
                    </label>
                    <br /><br />
                    <div>Changed plans?  Click the 'Connect to SimSage' button below to refresh.</div>
                </fieldset>

                <input type="hidden" name="action" value="sign-in">
            </div>
            <div>
                <div style="float: left; margin-right: 20px">
                    <?php submit_button( 'Connect to SimSage', 'primary', 'submit', true ); ?>
                </div>
                <?php if ( $has_account ) { ?>
                    <div id="btnPreClose" style="float: left;">
                        <p class="submit">
                            <span class="button" onclick="document.getElementById('btnCloseAccount').style.display='';
                                                          document.getElementById('btnPreClose').style.display='none';">
                                                          Close my SimSage Account</span>
                        </p>
                    </div>
                <?php } ?>
            </div>
            <br clear="both" />
            <div id="btnCloseAccount" style="display: none">
                <span>
                    <b>CAREFUL</b>!<br/><br/>
                    Are you sure you want to CLOSE your SimSage account?<br/>
                    This will REMOVE all your personal data from our systems,<br/>
                    We will stop charging your credit card, and it will stop this plugin from working!<br/><br/>
                    <b>this action cannot be undone!</b><br/><br/>
                </span>
                <label>
                    <input type="password" name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_password]"
                           class="input-field" value="" maxlength="100"
                           placeholder="your SimSage account password"/>
                    <span class="description">Please enter your SimSage Password</span>
                </label>
                <?php submit_button( 'Close my SimSage Account', 'secondary', 'submit', true ); ?>
            </div>

        <?php } ?>


        <?php if ($active_tab == 'search') { ?>
            <div class="tabbed-display">

                <fieldset>
                    <label>
                        <span class="radio_label">no</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_override_default_search]" type="radio" value="0" <?php echo (!isset($options['simsage_override_default_search']) || $options['simsage_override_default_search'] != '1') ? 'checked' : ''; ?> />
                        <span class="radio_label">yes</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_override_default_search]" type="radio" value="1" <?php echo (isset($options['simsage_override_default_search']) && $options['simsage_override_default_search'] == '1') ? 'checked' : ''; ?> />
                        <span class="description">Override the default WordPress Search.</span>
                    </label>
                </fieldset>

                <input type="hidden" name="action" value="update-search">
            </div>
            <?php submit_button( 'update Search Settings', 'primary','submit', true ); ?>

        <?php } ?>



        <?php if ($active_tab == 'filters') { ?>
            <div class="tabbed-display">
                <div class="two-lists-side-by-side">

                    <div class="instructions">Tell SimSage what <b>not</b> to index.  On the left-hand side you find a list with items that SimSage will look at.
                        Clicking on an item in this list will move it to the other list.  The right-hand side list contains items that will purposely ignored
                        by SimSage.  Click the <b>update Filter Settings</b> button below when you've made your changes to save this information.
                    </div>

                    <div class="boxes">

                        <div class="box-1">
                            <br clear="both" />
                            <div class="filter">
                                <label>
                                    <input type="text" class="filter-available" placeholder="filter urls" value="" onkeyup="render_lists();" />
                                </label>
                            </div>
                            <br clear="both" />
                            <div class="title">Pages indexed by SimSage</div>
                            <div class="available-list">
                            </div>
                        </div>

                        <div class="box-2">
                            <br clear="both" />
                            <div class="filter">
                                <label>
                                    <input type="text" class="filter-ignored" placeholder="filter urls" value="" onkeyup="render_lists();" />
                                </label>
                            </div>
                            <br clear="both" />
                            <div class="title">Pages ignored by SimSage</div>
                            <div class="ignore-list">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <input type="hidden" name="action" value="update-filter">
            <input type="hidden" name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_ignore_url_list]"
                   class="ignore-list-value" value="">
            <div class="spacing-bottom">&nbsp;</div>

            <?php submit_button( 'update Filter Settings', 'primary','submit', true ); ?>

        <?php } ?>


        <?php if ($active_tab == 'styling') { ?>

            <!-- let the user introduce a new css class for styling -->
            <fieldset>
                <div style="margin-top: 20px; margin-bottom: 10px;">Introduce an optional series of css classes (separated by spaces) to the top level of the search-controls</div>
                <label>
                    <input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_styling]" type="text"
                           class="input-field" id="simsage_styling" maxlength="100"
                           value="<?php echo (isset($options['simsage_styling']) && $options['simsage_styling'] != '') ? $options['simsage_styling'] : ''; ?>"
                           placeholder="css classes"/>
                    <span class="description">optional css classes to apply</span>
                </label>
            </fieldset>

            <fieldset>
                <label>
                    <input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_search_width]" type="text"
                           class="input-field" id="simsage_search_width" maxlength="4"
                           value="<?php echo (isset($options['simsage_search_width']) && $options['simsage_search_width'] != '') ? $options['simsage_search_width'] : '500'; ?>"
                           placeholder="the width (in pixels) of the search text-field" />
                    <span class="description">the width (in pixels) of the search text-field</span>
                </label>
            </fieldset>

            <input type="hidden" name="action" value="styling">

            <?php submit_button( 'update Styling', 'primary','submit', true ); ?>

        <?php } ?>



        <?php if ($active_tab == 'keywords') { ?>
            <!-- graphical display items -->
            <div class="analytics-area">
                <div id='layout'>
                    <!-- keyword analytics tab -->
                    <div class='margin-top'></div>
                    <div id='div_keywords' class="container">
                        <svg id="keyword-analytics" />
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if ($active_tab == 'searches') { ?>
            <!-- graphical display items -->
            <div class="analytics-area">
                <div id='layout'>
                    <!-- search analytics tab -->
                    <div class='margin-top'></div>
                    <div id='div_searches' class="container" style="display: none;">
                        <svg id="search-analytics" />
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if ($active_tab == 'logs') { ?>
            <div class="analytics-area">
                <div class='margin-top'></div>
                <div id='div_logs'>
                    <div class="button-row">
                        <button type="button" onclick="data.dlOperatorConversations()" class="button button-style ss-button">Operator Conversation Spreadsheet</button>
                        <span class="button-help-text">Download a Spreadsheet containing all conversations between Operators and Clients for the selected month.</span>
                    </div>
                    <div class="button-row">
                        <button type="button" onclick="data.dlQueryLog()" class="button button-style ss-button">Search & Query Log Spreadsheet</button>
                        <span class="button-help-text">Download a log of what people have been searching / asking on this site for the selected month.</span>
                    </div>
                    <div class="button-row">
                        <button type="button" onclick="data.dlLanguageCustomizations()" class="button button-style ss-button" title="Download a Spreadsheet of all QA Pairs and Language Customizations">Content Spreadsheet</button>
                        <span class="button-help-text">Download a SimSage QA / language Spreadsheet containing all your customized content (not month specific).</span>
                    </div>
                    <div class="button-row">
                        <button type="button" onclick="data.dlContentAnalysis()" class="button button-style ss-button" title="Download a Content Analysis Spreadsheet">Content Analysis Spreadsheet</button>
                        <span class="button-help-text">Download a Spreadsheet containing all currently crawled content and a Semantic analysis for each item (not month specific).</span>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php if ($active_tab == 'qna') { ?>
            <!-- Q&A editor / adder -->
            <div id="qna-edit" class="qna-editor" style="display: none;">
                <div class="qna-title"></div>
                <div class="qna-control"><label class="qna-label">question <br/><input class="input-text mi-q1" type="text" alt="question" title="question" placeholder="question" /></label></div>
                <div class="qna-control"><label class="qna-label">alternative <br/><input class="input-text mi-q2" type="text" alt="alternative" title="alternative question (optional)" placeholder="alternative question (optional)" /></label></div>
                <div class="qna-control"><label class="qna-label">answer text<br/>
                        <textarea rows="3" class="mi-answer" cols="50" title="the answer" placeholder="the answer"></textarea>
                    </label>
                </div>
                <div class="qna-control"><label class="qna-label">links<br/>
                        <textarea rows="3" cols="50" class="mi-links" title="links" placeholder="links"></textarea>
                    </label>
                </div>
                <div class="qna-buttons-container">
                    <div class="qna-buttons">
                        <button type="button" class="ss-button" onclick="data.mindItemDialogClose()">cancel</button>
                        <button type="button" class="ss-button" onclick="data.mindItemDialogSave()">save</button>
                    </div>
                </div>
            </div>

            <!-- Q&A tab -->
            <div id='div_qna' class="qna">
                <div class="simsage-find-box">
                    <div class="find-label">find items in the mind</div>
                    <div class="find-dialog">
                        <label><input type="text" value="" spellcheck="false" autoFocus class="find-text-style mind-item-find-text"
                                      onKeyUp="data.handleMindItemKey(event.keyCode)" onChange="data.setMindItemFilter(event.target.value)" /></label>
                    </div>
                    <div class="find-image-box ss-button">
                        <img class="find-image ss-button"
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
                        <tbody id="mindItemList">
                        </tbody>
                        <tr class="pagination-bar-tr">
                            <td colspan="3">
                                <div id="mindItemPagination" class="pagination-bar"></div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="bottom-action-bar">
                            <span class="upload-control">
                                    <label id="upload-files-label" for="upload-files" class="button ss-button">Select Excel Spreadsheet</label>
                                    <input id="upload-files" type="file" accept=".xls,.xlsx" style="display: none;" onchange="data.handleUploadChange(event)" /></label>
                                <button type="button" id="upload-button" class="button upload-button" disabled onClick="data.uploadMindItems()">upload</button>
                            </span>
                                <span>
                                <button type="button" class="button export-button" title="export existing SimSage Q&A and Synonym information" onClick="data.dlLanguageCustomizations()">export</button>
                            </span>
                                <span class="delete-button ss-button" title="delete all mind-items" onClick="data.deleteAllMindItems()">
                                <img src="<?php echo $this->asset_folder . 'images/delete.svg'?>" class="delete-button-image" alt="delete" />
                            </span>
                                <span class="add-button ss-button" title="add a new mind-item" onClick="data.addMindItem()">
                                <img src="<?php echo $this->asset_folder . 'images/add.svg'?>" class="add-button-image" alt="add" />
                            </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php } ?>

        <?php if ($active_tab == 'synonyms') { ?>
            <!-- Synonym editor / adder -->
            <div id="synonym-edit" class="synonym-editor" style="display: none;">
                <div class="synonym-title"></div>
                <div class="synonym-control">
                    <label class="synonym-label">synonyms <br/>
                        <textarea rows="14" class="syn-words" cols="50" title="synonyms separated by commas" placeholder="synonyms separated by commas"></textarea>
                    </label>
                </div>
                <div class="synonym-buttons-container">
                    <div class="synonym-buttons">
                        <button type="button" class="ss-button" onclick="data.synonymDialogClose()">cancel</button>
                        <button type="button" class="ss-button" onclick="data.synonymDialogSave()">save</button>
                    </div>
                </div>
            </div>

            <!-- Synonyms tab -->
            <div id='div_synonyms' class="qna">
                <div class="simsage-find-box">
                    <div class="find-label">find synonyms</div>
                    <div class="find-dialog">
                        <label><input type="text" value="" spellcheck="false" autoFocus class="find-text-style synonym-find-text"
                                      onKeyUp="data.handleSynonymKey(event.keyCode)" onChange="data.setSynonymFilter(event.target.value)" /></label>
                    </div>
                    <div class="find-image-box ss-button">
                        <img class="find-image ss-button"
                             onClick="data.getSynonyms()"
                             src="<?php echo $this->asset_folder . 'images/dark-magnifying-glass.svg'?>" title="search" alt="search"/>
                    </div>
                </div>
                <div>
                    <table class="simsage-find-table">
                        <thead>
                        <tr class="table-header">
                            <td class="id-column">id</td>
                            <td class="question-column">synoynms</td>
                            <td class="action-column">actions</td>
                        </tr>
                        </thead>
                        <tbody id="synonymList">
                        </tbody>
                        <tr class="pagination-bar-tr">
                            <td colspan="3">
                                <div id="synonymPagination" class="pagination-bar"></div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="bottom-action-bar">
                            <span class="add-button ss-button" title="add a new synonym" onClick="data.addSynonym()">
                                <img src="<?php echo $this->asset_folder . 'images/add.svg'?>" class="add-button-image" alt="add" />
                            </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php } ?>

        <?php if ($active_tab == 'semantics') { ?>
            <!-- Semantic editor / adder -->
            <div id="semantic-edit" class="semantic-editor" style="display: none;">
                <div class="semantic-title"></div>
                <div class="semantic-control">
                    <label class="sem-label">word <br/>
                        <input class="input-text sem-word" type="text" alt="word" title="word" placeholder="word" />
                    </label>
                </div>
                <div class="semantic-control">
                    <label class="sem-label">semantic <br/>
                        <input class="input-text sem-semantic" type="text" alt="semantic" title="semantic" placeholder="semantic" />
                    </label>
                </div>
                <div class="semantic-buttons-container">
                    <div class="semantic-buttons">
                        <button type="button" class="ss-button" onclick="data.semanticDialogClose()">cancel</button>
                        <button type="button" class="ss-button" onclick="data.semanticDialogSave()">save</button>
                    </div>
                </div>
            </div>

            <!-- Semantics tab -->
            <div id='div_semantics' class="qna">
                <div class="simsage-find-box">
                    <div class="find-label">find semantics</div>
                    <div class="find-dialog">
                        <label><input type="text" value="" spellcheck="false" autoFocus class="find-text-style semantic-find-text"
                                      onKeyUp="data.handleSemanticKey(event.keyCode)" onChange="data.setSemanticFilter(event.target.value)" /></label>
                    </div>
                    <div class="find-image-box ss-button">
                        <img class="find-image ss-button"
                             onClick="data.getSemantics()"
                             src="<?php echo $this->asset_folder . 'images/dark-magnifying-glass.svg'?>" title="search" alt="search"/>
                    </div>
                </div>
                <div>
                    <table class="simsage-find-table">
                        <thead>
                        <tr class="table-header">
                            <td class="sem-id-column">word</td>
                            <td class="sem-question-column">semantic</td>
                            <td class="action-column">actions</td>
                        </tr>
                        </thead>
                        <tbody id="semanticList">
                        </tbody>
                        <tr class="pagination-bar-tr">
                            <td colspan="3">
                                <div id="semanticPagination" class="pagination-bar"></div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="bottom-action-bar">
                            <span class="add-button ss-button" title="add a new semantic" onClick="data.addSemantic()">
                                <img src="<?php echo $this->asset_folder . 'images/add.svg'?>" class="add-button-image" alt="add" />
                            </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php } ?>


        <?php if ($active_tab == 'operator') { ?>

            <div class="operator-area operator-display">

                <div id="chatButtons" class="operator-buttons-top">
                <span class="menu-button">
                    <button type="button" id="btnReady" type="button" class="operator-button" disabled
                            title="Signal that you are ready to go and converse with customers."
                            onClick="operator_ready()">
                        <span class="operator-button-icon"><svg class="" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15c1.66 0 2.99-1.34 2.99-3L15 6c0-1.66-1.34-3-3-3S9 4.34 9 6v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 15 6.7 12H5c0 3.42 2.72 6.23 6 6.72V22h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"></path></svg></span>
                        <span class="operator-button-text">Ready</span>
                    </button>
                </span>
                    <span class="menu-button">
                    <button type="button" id="btnBreak" type="button" class="operator-button" disabled
                            title="take a break, stop participating in conversations while you have a break."
                            onClick="operator_take_break()">
                        <span class="operator-button-icon"><svg class="MuiSvgIcon-root operator-button-icon" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"></path></svg></span>
                        <span class="operator-button-text">Break</span>
                    </button>
                </span>
                    <span class="menu-button">
                    <button type="button" id="btnBanUser" type="button" class="operator-button operator-button-margin-left" disabled
                            title="The current conversation is abusive or bad spirited, ban this user from the system."
                            onClick="confirm_ban_user()">
                        <span class="operator-button-icon"><svg class="MuiSvgIcon-root operator-button-icon" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><circle cx="15" cy="8" r="4"></circle><path d="M23 20v-2c0-2.3-4.1-3.7-6.9-3.9l6 5.9h.9zm-11.6-5.5C9.2 15.1 7 16.3 7 18v2h9.9l4 4 1.3-1.3-21-20.9L0 3.1l4 4V10H1v2h3v3h2v-3h2.9l2.5 2.5zM6 10v-.9l.9.9H6z"></path></svg></span>
                        <span class="operator-button-text">Ban User</span>
                    </button>
                </span>
                    <span class="menu-button">
                    <button type="button" id="btnNextUser" type="button" class="operator-button operator-button-margin-left" disabled
                            title="We have finished the current conversation and are ready for a next one."
                            onClick="operator_next_user()">
                        <span class="operator-button-icon"><svg class="MuiSvgIcon-root operator-button-icon" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.99 2c-5.52 0-10 4.48-10 10s4.48 10 10 10 10-4.48 10-10-4.48-10-10-10zm3.61 6.34c1.07 0 1.93.86 1.93 1.93 0 1.07-.86 1.93-1.93 1.93-1.07 0-1.93-.86-1.93-1.93-.01-1.07.86-1.93 1.93-1.93zm-6-1.58c1.3 0 2.36 1.06 2.36 2.36 0 1.3-1.06 2.36-2.36 2.36s-2.36-1.06-2.36-2.36c0-1.31 1.05-2.36 2.36-2.36zm0 9.13v3.75c-2.4-.75-4.3-2.6-5.14-4.96 1.05-1.12 3.67-1.69 5.14-1.69.53 0 1.2.08 1.9.22-1.64.87-1.9 2.02-1.9 2.68zM11.99 20c-.27 0-.53-.01-.79-.04v-4.07c0-1.42 2.94-2.13 4.4-2.13 1.07 0 2.92.39 3.84 1.15-1.17 2.97-4.06 5.09-7.45 5.09z"></path></svg></span>
                        <span class="operator-button-text">Next User</span>
                    </button>
                </span>
                </div>

                <div style="float: right; margin-top: -60px; margin-right: 50px;">
                    <span class="operator-button-icon"><svg class="MuiSvgIcon-root operator-button-icon" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.99 2c-5.52 0-10 4.48-10 10s4.48 10 10 10 10-4.48 10-10-4.48-10-10-10zm3.61 6.34c1.07 0 1.93.86 1.93 1.93 0 1.07-.86 1.93-1.93 1.93-1.07 0-1.93-.86-1.93-1.93-.01-1.07.86-1.93 1.93-1.93zm-6-1.58c1.3 0 2.36 1.06 2.36 2.36 0 1.3-1.06 2.36-2.36 2.36s-2.36-1.06-2.36-2.36c0-1.31 1.05-2.36 2.36-2.36zm0 9.13v3.75c-2.4-.75-4.3-2.6-5.14-4.96 1.05-1.12 3.67-1.69 5.14-1.69.53 0 1.2.08 1.9.22-1.64.87-1.9 2.02-1.9 2.68zM11.99 20c-.27 0-.53-.01-.79-.04v-4.07c0-1.42 2.94-2.13 4.4-2.13 1.07 0 2.92.39 3.84 1.15-1.17 2.97-4.06 5.09-7.45 5.09z"></path></svg></span>
                    <span style="float: right; margin-top: 2px; margin-left: 4px;"><span id="botCount">0</span> active users</span>
                </div>

                <div id="chat1">
                    <div id="conversationList" class="operator-conversation-area">
                    </div>
                    <div class="operator-reply-area">
                    <span class="operator-reply-text-box">
                        <input id="txtResponse" class="operator-reply-text" type="text" disabled maxlength="200"
                               onkeyup="operator_key_press(event, this.value)" placeholder="your reply" aria-label="response" />
                    </span>
                        <span class="operator-reply-button-box">
                        <button type="button" type="button" id="btnChat" class="operator-button" value="chat" disabled
                                title="your response (available when connected to a user)" onClick="reply_click(null)">
                            Reply
                        </button>
                    </span>
                    </div>
                </div>


                <div id="learningSection" class="learnings" style="display: none;">
                    <div>
                        <span class="learn-label">question: </span>
                        <span id="txtQuestion" class="question-text"></span>
                    </div>
                    <br clear="both" />
                    <div>
                        <span class="learn-label">answer: </span>
                        <span id="txtAnswer" class="answer-text"></span>
                    </div>
                    <br clear="both" />
                    <div id="twoSection">
                        <input type="button" class="button learn-button" value="Cancel" onClick="clearQA()" />
                        <input type="button" class="button learn-button" value="Teach SimSage" onClick="teach()" />
                    </div>
                    <div id="oneSection" class="learn-buttons">
                        <input type="button" class="button learn-button" value="clear" onClick="clearQA()" />
                    </div>
                </div>

                <div id="previousAnswerSection" class="previous-answer" style="display: none;">
                    <div class="previous-answer-title">a previous answer to this question exists</div>
                    <div id="txtPreviousAnswer" class="previous-answer-answer"></div>
                    <div class="previous-answer-buttons">
                        <input type="button" class="button learn-button" value="don't use" onClick="dont_use()" />
                        <input id="btnPreviousAnswer" type="button" class="button learn-button" value="use in 5 secs" onClick="use()" />
                    </div>
                </div>

            </div>
        <?php } ?>



    </form>


    <script lang="js">

        // create an instance of this class
        data = null;
        ops = null;

        // filter a list by a text string provided by control_class
        function filter_list(list, control_class) {
            let text = jQuery(control_class).val();
            let filtered_list = [];
            for (let i in list) {
                if (list.hasOwnProperty(i)) {
                    if (list[i].indexOf(text) >= 0) {
                        filtered_list.push(list[i]);
                    }
                }
            }
            return filtered_list;
        }

        // draw the available and ignore lists
        function render_lists() {
            jQuery(".available-list").html(render_list(filter_list(available_urls, ".filter-available"), "deselect_item"));
            jQuery(".ignore-list").html(render_list(filter_list(ignore_urls, ".filter-ignored"), "select_item"));
            jQuery(".ignore-list-value").val(ignore_urls.join("|"));
        }

        /**
         * replace < and > in a string to make it html safe
         * @param str the string to act on
         * @return the escaped string
         */
        function esc_html(str) {
            if (typeof str === 'string' || str instanceof String) {
                return str
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
            }
            return str;
        }

        function render_list(list, fn) {
            let str = "";
            for (let i in list) {
                if (list.hasOwnProperty(i)) {
                    let item = list[i];
                    str += "<div class=\"url\" title=\"move " + item + "\" onclick=\"" + fn + "('" + item + "')\">" + item + "</div>";
                }
            }
            return str;
        }

        function deselect_item(url) {
            let index = available_urls.indexOf(url);
            if (index >= 0) {
                available_urls.splice(index, 1);
                ignore_urls.push(url);
                render_lists();
            }
        }

        function select_item(url) {
            let index = ignore_urls.indexOf(url);
            if (index >= 0) {
                ignore_urls.splice(index, 1);
                available_urls.push(url);
                render_lists();
            }
        }

        // run this on document ready when all elements are available
        jQuery(document).ready(function() {
            if (active_tab === 'filters') {
                render_lists();

            // prevent form post on CR in form fields
            jQuery(window).keydown(function(event){
                if(event.keyCode === 13) {
                    event.preventDefault();
                    return false;
                }
            });

            } else if (active_tab === 'keywords' || active_tab === 'searches' ||
                active_tab === 'logs' || active_tab === 'qna' || active_tab === 'synonyms' ||
                active_tab === 'semantics') {

                data = new SimsageData(an_update_ui);
                data.tab = active_tab;

                // get our analytics
                if (active_tab === 'keywords' || active_tab === 'searches') {
                    data.getAnalytics();
                } else if (active_tab === 'qna') {
                    data.getMindItems();
                } else if (active_tab === 'synonyms') {
                    data.getSynonyms();
                } else if (active_tab === 'semantics') {
                    data.getSemantics();
                }

                // setup the jQuery date picker
                jQuery(".datepicker").datepicker({
                    changeMonth: true,
                    changeYear: true,
                    showButtonPanel: true,
                    dateFormat: 'MM yy',
                    onClose: function (dateText, inst) {
                        const date = new Date(inst.selectedYear, inst.selectedMonth, 1);
                        jQuery(this).datepicker('setDate', date);
                        data.set_date(date);
                    }
                }).datepicker('setDate', new Date());

            } else if (active_tab === 'operator') {
                // create an instance of the Operator Class
                ops = new Operator();

                // connect to our web-sockets for two way conversations
                ops.init_simsage();
                // setup operator timer ticks
                window.setInterval(() => operator_present_tick(), operator_wait_timeout_in_ms);
            }

        });

    </script>


</div>
