<?php
/**
 * SimSage Search Result Rendering
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
    };
</script>

<div class="simsage-search <?php echo $this->get_user_value("simsage_styling", "") ?>">

    <!-- ************************** -->
    <!-- bot reply speech bubble -->
    <div class="bot-box-view" style="display: none;">
        <div class="bot-box" >
            <div class="speech-bubble">
                <span onclick="simsage.close_bot()" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" title="Close" class="close-box">
                    <span class="close-image">&times;</span>
                </span>
                <div class="title">
                    <span class="bot-image-box">
                        <span class="bot-image">&#x1F464;</span>
                    </span>
                    <span class="bot-label-text" title="Bot">Bot,&nbsp; date-time</span>
                </div>
                <div class="bot-text">
                    <span class="bot-reply-text"></span>
                </div>
                <div class="bot-links">
                </div>
            </div>
        </div>
    </div>

    <div class="dialog-parent">

        <!-- ************************** -->
        <!-- spelling suggest speech bubble -->
        <div class="spelling-box-view" style="display: none;">
            <div class="spelling-box" >
                <div class="speech-bubble">
                <span tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.close_spelling_suggestion()" title="Close" class="close-box">
                    <span class="close-image">&times;</span>
                </span>
                    <div class="title">
                        <span class="spelling-label-text" title="Did you mean...">Did you mean, ?</span>
                    </div>
                    <div class="button-box">
                        <button class="yes-button" value="yes" title="yes" onclick="simsage.use_spelling_suggestion()">yes</button>
                        <button class="no-button" value="no" title="no" onclick="simsage.close_spelling_suggestion()">no</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ***************** -->
        <!-- Error dialog box -->
        <div class="error-dialog-box" style="display: none;">
            <div class="error-dialog" >
                <div class="header">
                    <span class="title" title="an error occurred">ERROR</span>
                    <span tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.close_error()" title="Close" class="close-box">
                    <span class="close-text">Close</span>
                    <span class="close-image">&times;</span>
                </span>
                </div>
                <div class="error-text"></div>
                <div class="error-spacer"></div>
            </div>
        </div>


        <!-- ***************** -->
        <!-- Sign-in dialog box -->
        <div class="search-sign-in" style="display: none;">
            <div class="search-sign-in-area">
                <div class="header">
                    <span class="title sign-in-title" title="Sign-in">sign-in</span>
                    <span tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.close_sign_in()" title="Close" class="close-box">
                <span class="close-text">Close</span>
                <span class="close-image">&times;</span>
            </span>
                </div>
                <span class="category-text">username</span>
                <div class="category-item">
                    <label>
                        <input type="text" maxlength="100" placeholder="your username" class="category-input sign-in-user-name" title="your username">
                    </label>
                </div>
                <span class="category-text">password</span>
                <div class="category-item">
                    <label>
                        <input type="password" maxlength="80" placeholder="your password" class="category-input sign-in-password" title="your password">
                    </label>
                </div>
                <div class="spacer"></div>
            </div>
            <div class="bar-bottom">
            <span class="clear-text-box">
            </span>
                <span tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" class="sign-in-button" title="sign-in" onclick="simsage.do_sign_in()">
                <span class="sign-in-text">sign-in</span>
            </span>
            </div>
        </div>


        <!-- ***************** -->
        <!-- NO Search results -->
        <div class="no-search-results" style="display: none;">
            <div class="no-results-found-box">
                <span class="no-results-found">No Results found for</span>
                <span class="not-found-words">&nbsp;</span>
                <span tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.close_no_search_results()" title="Close" class="close-box">
            <span class="close-text">Close</span>
            <span class="close-image">&times;</span>
        </span>
            </div>
            <div class="ask-email-box">
                <div class="no-results-text">
                    If you would like us to follow up on your query by email, please enter your email address and we'll
                    get back to you with a response within 24 hours
                </div>
                <!-- email text box -->
                <div>
                <span class="email-text-box">
                    <label class="email-address-text">
                        <input type="text" placeholder="Enter your email address" title="Enter your email address"
                               maxlength="100" onkeypress="simsage.email_typing(event)" class="email-text">
                    </label>
                </span>
                    <span class="send-button-disabled float-left email-send-button" title="Send" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.do_email()">
                    <span class="send-text">Send</span>
                </span>
                </div>
            </div>
            <div class="ask-emailed-box" style="display: none;">
                <div class="no-results-text">
                    We have emailed support and will endeavor to get back to you with a response within 24 hours
                </div>
            </div>
            <div class="chat-spacer"></div>
        </div>

        <!-- ****************************** -->
        <!-- Detail view of a specific item -->
        <div class="search-details-view" style="display: none;">
            <div class="search-details" >
                <div class="search-details-header">
                    <span class="details-text" title="details">DETAILS</span>
                    <span tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.close_search_details()" title="Close" class="close-box">
                <span class="close-image">&times;</span>
                </span>
                    <span onclick="simsage.close_search_details()" title="Close" class="close-box">
                    <span class="close-text">Close</span>
                </span>
                </div>
                <div class="spacer"></div>
                <div class="detail-table">
                </div>
            </div>
        </div>

    </div>


    <!-- ********************* -->
    <!-- Search result display -->
    <div class="search-results" style="display: none;">
        <div class="search-results-inside">
            <div class="pagination-box search-display" style="display: none;">
            </div>

            <div class="search-results-two-columns search-display">
                <!-- this is where the search results are rendered into -->
                <div class="search-results-td search-results-text">
                </div>

                <div class="search-results-td search-results-images" style="display: none;">
                    <div class="search-result-images">
                    </div>
                </div>

            </div>
            <div style="clear:both;"></div>

        </div>
    </div>

    <br clear="both" />

    <!-- ************************************ -->
    <!-- chat dialog opened by the button below -->

    <div class="operator-chat-box-view" onclick="simsage.nop()" style="display: none;">
        <div class="operator-chat-box">
            <div class="operator-close-width">
                <div class="div-close" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.close_chat()" title="Close chat">
                    <span class="close-text">Close</span>
                    <span class="close-image-box"><span class="close-image">&times;</span></span>
                </div>
                <div class="chat-table">
                </div>
            </div>
            <div class="chat-bar-bottom">
                <span class="chat-text-box" onclick="simsage.focus_text('.chat-text')">
                    <label class="chat-box-text">
                        <input type="text" value="" autocomplete="false" placeholder="Type your message" class="chat-text" title="Type your message"
                               maxlength="200" onkeyup="simsage.chat_typing(event)">
                    </label>
                </span>
                <span class="chat-button-disabled chat-button-control" title="Send" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.do_chat()">
                <span class="chat-send-text">Send</span>
            </span>
            </div>
        </div>
    </div>

    <!-- ************************************ -->
    <!-- chat with us button at the right-hand-side bottom -->

    <div class="chat-button-at-bottom" style="display: none;">
        <!-- chat with us button online -->
        <div class="chat-container online" tabindex="0" onkeyup="if (activation(event)) this.onclick(null)" onclick="simsage.show_chat()">
            <span class="chat-with-us-online" title="Chat with us">
                <span class="chat-with-us-image-box">
                    <span class="chat-with-us-image chat-with-us-image-white">&#x1f5e9;</span>
                </span>
                <span class="chat-with-us-text chat-with-us-text-box-online">
                    Chat with us
                </span>
            </span>
        </div>
    </div>

</div>
