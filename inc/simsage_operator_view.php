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

    <div class="operator-area">
        <h2>SimSage Operator</h2>
    </div>

	<?php
	$options = get_option( SIMSAGE_PLUGIN_NAME );
	$plan = simsage_get_plan();
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

            <div id="chatButtons" class="button-list">
                <span class="menu-button">
                    <input id="btnReady" type="button" class="button" disabled
                           title="Signal that you are ready to go and converse with customers."
                           value="ready for a chat" onClick="operator_ready()" />
                </span>
                <span class="menu-button">
                    <input id="btnBreak" type="button" class="button" disabled
                           title="take a break, stop participating in conversations while you have a break."
                           value="take a break" onClick="operator_take_break()" />
                </span>
                <span class="menu-button">
                    <input id="btnNextUser" type="button" class="button" disabled
                           title="We have finished the current conversation and are ready for a next one."
                           value="next user" onClick="operator_next_user()" />
                </span>
                <span class="menu-button">
                    <input id="btnBanUser" type="button" class="button" disabled
                           title="The current conversation is abusive or bad spirited, ban this user from the system."
                           value="ban user" onClick="confirm_ban_user()" />
                </span>
                <span id="botCount"></span>
            </div>

            <div id="chat1">
                <div class="conversation-parent">
                    <div id="conversationList" class="conversation-container"></div>
                </div>
                <div>
                    <span><input id="txtResponse" class="text-response" type="text" disabled maxlength="200"
                           onkeyup="operator_key_press(event, this.value)" placeholder="your response" aria-label="response" />
                    </span>
                    <span>
                        <input type="button" id="btnChat" class="button" value="chat" disabled
                               title="your response (available when connected to a user)" onClick="reply_click(null)" />
                    </span>
                </div>
            </div>


            <div id="learningSection" class="learnings">
                <div>
                    <span class="learn-label">question</span>
                    <span id="txtQuestion" class="question-text"></span>
                </div>
                <br clear="both" />
                <div>
                    <span class="learn-label">answer</span>
                    <span id="txtAnswer" class="answer-text"></span>
                </div>
                <br clear="both" />
                <div id="twoSection">
                    <input type="button" class="button learn-button" value="clear" onClick="clearQA()" />
                    <input type="button" class="button learn-button" value="teach SimSage" onClick="teach()" />
                </div>
                <div id="oneSection" class="learn-buttons">
                    <input type="button" class="button learn-button" value="clear" onClick="clearQA()" />
                </div>
            </div>

            <div id="previousAnswerSection" class="previous-answer">
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

