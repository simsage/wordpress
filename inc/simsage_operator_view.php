<?php
/**
 * the "view" of the operator page, the HTML controls and their control bindings and form POST
 * for conversing with clients
 */
?>



<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <style>
        fieldset {margin-bottom: 20px;}
    </style>

    <div id="icon-themes" class="icon32"></div>

	<?php
	$options = get_option( SIMSAGE_PLUGIN_NAME );
	$plan = simsage_get_plan();
	debug_log( print_r($plan, true) );
    // does this plan have operator access?
    $has_access = ($plan != null && isset( $plan['operatorEnabled'] ) && $plan['operatorEnabled']);
    // when we have selected a site, this variable will be set
    $has_sites = (simsage_get_kb() != null);
    // and is the operator enabled?
    $using_bot = !isset($options["simsage_use_bot"]) || $options["simsage_use_bot"] != '0';
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
            // image types for link name display
            image_types: [".jpg", ".jpeg", ".png", ".gif", ".svg"],
        };

    </script>

    <?php if ( $has_access && $has_sites && $using_bot ) { ?>

        <div class="operator-area operator-display">

            <div id="chatButtons" class="operator-buttons-top">
                <span class="menu-button">
                    <button id="btnReady" type="button" class="operator-button" disabled
                           title="Signal that you are ready to go and converse with customers."
                           onClick="operator_ready()">
                        <span class="operator-button-icon"><svg class="" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15c1.66 0 2.99-1.34 2.99-3L15 6c0-1.66-1.34-3-3-3S9 4.34 9 6v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 15 6.7 12H5c0 3.42 2.72 6.23 6 6.72V22h2v-3.28c3.28-.48 6-3.3 6-6.72h-1.7z"></path></svg></span>
                        <span class="operator-button-text">Ready</span>
                    </button>
                </span>
                <span class="menu-button">
                    <button id="btnBreak" type="button" class="operator-button" disabled
                           title="take a break, stop participating in conversations while you have a break."
                           onClick="operator_take_break()">
                        <span class="operator-button-icon"><svg class="MuiSvgIcon-root operator-button-icon" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z"></path></svg></span>
                        <span class="operator-button-text">Break</span>
                    </button>
                </span>
                <span class="menu-button">
                    <button id="btnBanUser" type="button" class="operator-button operator-button-margin-left" disabled
                           title="The current conversation is abusive or bad spirited, ban this user from the system."
                           onClick="confirm_ban_user()">
                        <span class="operator-button-icon"><svg class="MuiSvgIcon-root operator-button-icon" focusable="false" viewBox="0 0 24 24" aria-hidden="true"><circle cx="15" cy="8" r="4"></circle><path d="M23 20v-2c0-2.3-4.1-3.7-6.9-3.9l6 5.9h.9zm-11.6-5.5C9.2 15.1 7 16.3 7 18v2h9.9l4 4 1.3-1.3-21-20.9L0 3.1l4 4V10H1v2h3v3h2v-3h2.9l2.5 2.5zM6 10v-.9l.9.9H6z"></path></svg></span>
                        <span class="operator-button-text">Ban User</span>
                    </button>
                </span>
                <span class="menu-button">
                    <button id="btnNextUser" type="button" class="operator-button operator-button-margin-left" disabled
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
                        <button type="button" id="btnChat" class="operator-button" value="chat" disabled
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

    <?php } else if ( $has_access && $has_sites && !$using_bot ) { ?>
        <div class="label-success">You have elected not to use the bots, so the operator interface has been disabled.<br/>
            You can change this setting <a href="/wp-admin/options-general.php?page=simsage-search&tab=bot">here</a> (look for <i>Use the SimSage bot along with search</i>).
        </div>

    <?php } else if ( !$has_sites ) { ?>
        <div class="operator-area">
            <div class="label-success">Please <a href="/wp-admin/options-general.php?page=simsage-search">configure</a> your SimSage plugin first.</div>
        </div>
    <?php } else if ( !$has_access ) { ?>
        <div class="operator-area">
            <div class="label-success">Your plan does not provide you with Operator Access.  Please <a href="<?php echo $this->get_portal_server(); ?>/#/sign-in?origin=plugin" target="_blank">upgrade your plan</a> if you wish to have Operator Support.</div>
        </div>
    <?php } ?>

</div>

