<?php
/**
 * SimSage Search Box for rendering
 */
?>

<script lang="js">
    // set an image base for all our templates to use (url bases for images)
    image_base = "<?php echo $this->asset_folder ?>";
    server = "<?php echo $this->get_account_setting("server") ?>";

    // the settings for this application - no trailing / on the base_url please
    // it is imperative that we do not expose the SID here (securityID), as it is what protects your account from malicious use
    settings = {
        // api version of ws_base
        api_version: 1,
        // the service layer end-point, set after logging in
        base_url: server + 'api',
        // web sockets platform endpoint for comms
        ws_base: server + 'ws-api',
        // the organisation's id to search
        organisationId: "<?php echo $this->get_account_setting("id") ?>",
        // the knowledge base's Id (selected site) and security id (sid)
        kbId: "<?php echo $this->get_site_setting("kbId") ?>",
        // search settings
        page_size: <?php echo $this->get_user_value("simsage_page_size", 3) ?>,
        fragment_count: <?php echo $this->get_user_value("simsage_fragment_size", 3) ?>,
        max_word_distance: <?php echo $this->get_user_value("simsage_word_distance", 20) ?>,
        use_spelling_suggest: false,
        context_label: "<?php echo $this->context ?>",
        context_match_boost: <?php echo $this->context_boost ?>,
        // bot settings
        bot_enabled: <?php echo $this->get_user_boolean_value("simsage_use_bot", true) ?>,
        // bot sensitivity - controls the A.I's replies - we suggest you don't change it!
        bot_threshold: <?php echo $this->get_user_value("bot_threshold", 0.8125) ?>,
        // email settings
        ask_email: <?php echo $this->get_user_boolean_value("simsage_ask_email", false) ?>,
        // show the advanced filters?
        show_advanced_filter: <?php echo $this->get_user_boolean_value("simsage_adv_filter", true) ?>,
        // show the operator connect button?
        can_contact_ops_direct: <?php echo $this->get_user_boolean_value("use_operator", true) ?>,
        // image types for link name display
        image_types: [".jpg", ".jpeg", ".png", ".gif", ".svg"],
    };

</script>

<table class="search-table">

    <!-- ******************************************************************************* -->
    <!-- [ search box | search button | search options | spacer | chat ] -->
    <tr class="search-row">
        <!-- left bubble -->
        <td class="left-bubble">
        </td>
        <!-- search box -->
        <td class="search-text-box">
            <label class="search-text-label" title="ask SimSage">
                <input type="text" value="" class="search-text" maxlength="100" onkeyup="search_typing(event, this.value)">
            </label>
        </td>
        <!-- search clear (cross) -->
        <td class="search-button-box" title="clear your query" onclick="clear_search()">
            <span class="search-button-span-box">
                <img src="<?php echo $this->asset_folder . 'images/light-close.svg'?>" alt="select" class="clear-button-image">
            </span>
        </td>
        <!-- search button (magnification glass) -->
        <td class="search-button-box" title="query SimSage">
                <span class="search-button-span-box" onclick="do_search()">
                    <img src="<?php echo $this->asset_folder . 'images/magnification-glass.svg'?>" alt="select" class="search-button-image">
                </span>
        </td>
        <td class="search-grey-divider">
            <div class="vl"></div>
        </td>
        <!-- search options button and chevron -->
        <td class="search-options-button" title="Search options" onclick="show_filter(this)">
            <span class="search-options-text">Search Options</span>
        </td>
        <td class="search-options-chevron-box" title="Search options">
            <div class="search-options-chevron-image-box" onclick="click_filter()">
                <img src="<?php echo $this->asset_folder . 'images/chevron-down.svg'?>" alt="select" class="search-options-chevron">
            </div>

            <!-- ************************** -->
            <!-- advanced search filter box -->
            <div class="filter-box-view" style="display: none;" onclick="nop()">
                <div class="filter-box" >
                    <div class="speech-bubble">
                        <div class="title">
                            <span class="search-by-text" title="Search by:">SEARCH BY:</span>
                            <span onclick="close_filter()" title="Close" class="close-box">
                                    <img src="<?php echo $this->asset_folder . 'images/close.svg'?>" class="close-image" alt="close" />
                                    <span class="close-text">Close</span>
                                </span>
                        </div>
                        <table class="filter-table">
                            <tr class="tr-1">

                                <td class="col-1 sign-in-box" style="display: none;">
                                    <span class="category-text">Sign-in</span>
                                    <div class="category-item">
                                        <label class="sign-in-sel">
                                            <select name="sign-in" class="category-select dd-sign-in">
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td class="col-2 sign-in-text" style="display: none;">
                                        <span class="clear-text" title="sign-in"
                                              onclick="show_sign_in()">sign-in</span>
                                </td>
                                <td class="col-2 sign-out-text" style="display: none;">
                                    <span class="clear-text" title="sign-out" onclick="do_sign_out()">sign-out</span>
                                </td>

                                <td class="col-1">
                                    <span class="category-text">Document Type</span>
                                    <div class="category-item">
                                        <label class="document-type-sel">
                                            <select name="document-type" class="category-select" onchange="">
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
                                    <span class="clear-text" title="Clear Document Type" onclick="reset_selection('document-type-sel')">Clear</span>
                                </td>

                                <td class="col-1">
                                    <span class="category-text">Knowledge Base</span>
                                    <div class="category-item">
                                        <label class="knowledge-base-sel">
                                            <select name="knowledge-base" class="category-select dd-knowledge-base"
                                                    onchange="do_change_kb()">
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td class="col-2">
                                </td>

                                <td class="col-1">
                                    <span class="category-text">Source</span>
                                    <div class="category-item">
                                        <label class="source-sel">
                                            <select name="source" class="category-select dd-source">
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td class="col-2">
                                    <span class="clear-text" title="Clear Source" onclick="reset_selection('source-sel')">Clear</span>
                                </td>

                                <td class="col-1" colspan="2">
                                    <span class="category-text">Title</span>
                                    <div class="category-item">
                                        <label>
                                            <input type="text" maxlength="100" placeholder="Enter Title" class="category-input title-text" title="Enter Title">
                                        </label>
                                    </div>
                                </td>

                                <td class="col-1" colspan="2">
                                    <span class="category-text">URL</span>
                                    <div class="category-item">
                                        <label>
                                            <input type="text" maxlength="100" placeholder="Enter URL" class="category-input url-text" title="Enter URL">
                                        </label>
                                    </div>
                                </td>

                                <td class="col-1" colspan="2">
                                    <span class="category-text">Author</span>
                                    <div class="category-item">
                                        <label>
                                            <input type="text" maxlength="100" placeholder="Enter Author" class="category-input author-text" title="Enter Author">
                                        </label>
                                    </div>
                                </td>

                            </tr>
                        </table>
                    </div>
                    <div class="bar-bottom">
                            <span class="clear-text-box">
                                <span class="clear-all-text" title="Clear all" onclick="clear_all()">Clear all</span>
                            </span>
                        <span class="done-button" title="Done" onclick="close_filter()">
                                <span class="done-text">Done</span>
                            </span>
                    </div>
                </div>
            </div>


        </td>
        <!-- right bubble -->
        <td class="right-bubble">
        </td>
        <!-- blank space between control and contact us -->
        <td class="blank-space-box">
            <span class="blank-space">&nbsp;</span>
        </td>
        <td class="left-bubble-small">
        </td>
        <!-- chat with us button online -->
        <td class="chat-with-us"  title="Chat with us" onclick="click_chat()">
            <span class="chat-with-us-image-box">
                <img src="<?php echo $this->asset_folder . 'images/chat.svg'?>" alt="select" class="chat-with-us-image">
            </span>
            <!-- ************************* -->
            <!-- the operator/bot chat box -->
            <div class="operator-chat-box-view" style="display: none;" onclick="nop()">
                <table class="operator-chat-box">
                    <tr class="speech-bubble">
                        <td colspan="2" class="operator-close-width">
                            <div class="div-close" onclick="close_chat()" title="Close chat">
                                <span class="close-text">Close chat</span>
                                <span><img src="<?php echo $this->asset_folder . 'images/close.svg'?>" class="close-image" alt="close" /></span>
                            </div>
                            <table class="chat-table">
                            </table>
                        </td>
                    </tr>
                    <tr class="chat-bar-bottom">
                        <td class="chat-text-box">
                            <label class="chat-box-text">
                                <input type="text" placeholder="Type your message" class="chat-text" title="Type your message"
                                       maxlength="200" onkeyup="chat_typing(event, this.value)">
                            </label>
                        </td>
                        <td class="chat-button" title="Send" onclick="do_chat()">
                            <span class="chat-send-text">Send</span>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
        <td class="chat-with-us-second"  title="Chat with us" onclick="show_chat(this)">
            <span class="chat-with-us-text">
                Chat
            </span>
        </td>
        <td class="right-bubble-small">
        </td>
    </tr>


    <!-- ****************************** -->
    <!-- Detail view of a specific item -->
    <tr class="search-row search-details-view" style="display: none">
        <td colspan="13">
            <div class="search-details" >
                <div class="header">
                    <span class="details-text" title="details">DETAILS</span>
                    <span onclick="close_details()" title="Close" class="close-box">
                        <img src="<?php echo $this->asset_folder . 'images/close.svg'?>" class="close-image" alt="close" />
                        </span>
                    <span onclick="close_details()" title="Close" class="close-box">
                            <span class="close-text">Close</span>
                        </span>
                </div>
                <div class="spacer"></div>
                <div class="detail-table"></div>
            </div>
        </td>
    </tr>



    <!-- ********************* -->
    <!-- Search result display -->
    <tr class="search-results" style="display: none;">
        <td colspan="13">
            <div class="pagination-box">
            </div>
            <div class="clear"></div>
            <table class="search-result-table">
                <tr style="width: 100%;">
                    <!-- this is where the search results are rendered into -->
                    <td class="search-results-td">
                    </td>
                    <!-- this is where the categories of semantics go -->
                    <td class="category-items-td">
                    </td>
                </tr>
            </table>
            <div class="clear"></div>
            <div class="pagination-box">
            </div>
            <div class="clear"></div>
        </td>
    </tr>


    <!-- ***************** -->
    <!-- Sign-in dialog box -->
    <tr class="search-sign-in" style="display: none;">
        <td>
            <div class="search-sign-in-area">
                <div class="header">
                    <span class="title sign-in-title" title="Sign-in">sign-in</span>
                    <span onclick="close_sign_in()" title="Close" class="close-box">
                        <span class="close-text">Close</span>
                        <img src="<?php echo $this->asset_folder . 'images/close.svg'?>" class="close-image" alt="close" />
                    </span>
                </div>
                <span class="category-text">username</span>
                <div class="category-item">
                    <label>
                        <input type="text" maxlength="100" placeholder="your username" class="category-input user-name" title="your username">
                    </label>
                </div>
                <span class="category-text">password</span>
                <div class="category-item">
                    <label>
                        <input type="password" maxlength="80" placeholder="your password" class="category-input password" title="your password">
                    </label>
                </div>
                <div class="spacer"></div>
            </div>
            <div class="bar-bottom">
            <span class="clear-text-box">
            </span>
                <span class="sign-in-button" title="sign-in" onclick="do_sign_in()">
                <span class="sign-in-text">sign-in</span>
            </span>
            </div>
        </td>
    </tr>



    <!-- ***************** -->
    <!-- NO Search results -->
    <tr class="no-search-results" style="display: none;">
        <td>
            <div class="no-results-found-box">
                <span class="no-results-found">No Results found for</span>
                <span class="not-found-words">&nbsp</span>
                <span onclick="close_no_results()" title="Close" class="close-box">
                    <span class="close-text">Close</span>
                    <img src="<?php echo $this->asset_folder . 'images/close.svg'?>" class="close-image" alt="close" />
                </span>
            </div>
            <div class="no-results-text">
                If you would like us to follow up on your query by email, please enter your email address and we'll
                get back to you with a response within 24 hours
            </div>
            <!-- email text box -->
            <div>
            <span class="email-text-box">
                <label class="email-address-text">
                    <input type="text" placeholder="Enter your email address" title="Enter your email address"
                           maxlength="100" onkeypress="email_typing(event)" class="email-text">
                </label>
            </span>
                <span class="send-button float-left" title="Send" onclick="do_email()">
                    <span class="send-text">Send</span>
                </span>
            </div>
            <!-- chat to one of our operators -->
            <div class="chat-spacer"></div>
            <div class="chat-to-operator">
                Or chat to one of our operators
            </div>
            <!-- chat with us button online -->
            <div class="online">
            <span class="chat-with-us-online" title="Chat with us" onclick="click_chat()">
                <span class="chat-with-us-image-box">
                    <img src="<?php echo $this->asset_folder . 'images/chat.svg'?>" alt="select" class="chat-with-us-image">
                </span>
                <span class="chat-with-us-text-box-online">
                    Chat
                </span>
            </span>
            </div>
            <div class="chat-spacer"></div>
        </td>
    </tr>





    <!-- ***************** -->
    <!-- Error dialog box -->
    <tr class="error-dialog-box" style="display: none;">
        <td class="error-dialog" >
            <div class="header">
                <span class="title" title="an error occurred">ERROR</span>
                <span onclick="close_error()" title="Close" class="close-box">
                    <span class="close-text">Close</span>
                    <img src="<?php echo $this->asset_folder . 'images/close.svg'?>" class="close-image" alt="close" />
                </span>
            </div>
            <div class="error-text"></div>
            <div class="error-spacer"></div>
        </td>
    </tr>


</table>
