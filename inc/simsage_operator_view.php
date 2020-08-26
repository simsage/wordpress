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
        .label-success { font-weight: 600; font-size: 1.1em; margin-top: 10px; margin-bottom: 20px; }
        .wide-text { width: 500px; }
        .radio_label { margin-right: 4px; font-weight: bold;}
        .tabbed-display { margin-top: 20px; margin-left: 10px; }
    </style>

    <div id="icon-themes" class="icon32"></div>
    <h2>SimSage Operator</h2>

	<?php
	$options = get_option( PLUGIN_NAME );
	$plan = get_plan();
    // does this plan have operator access?
    $has_access = ($plan != null && isset( $plan['operatorEnabled'] ) && $plan['operatorEnabled']);
    // when we have selected a site, this variable will be set
    $has_sites = (get_kb() != null);
    // and is the operator enabled?
    $using_bot = isset($options["simsage_use_bot"]) && $options["simsage_use_bot"];
    ?>

    <?php if ( $has_access && $has_sites && $using_bot ) { ?>

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
                organisationId: "<?php echo $this->get_account_setting("id") ?>",
                // the knowledge base's Id (selected site) and security id (sid)
                kbId: "<?php echo $this->get_site_setting("kbId") ?>",
                // the operator uses the SecurityID to verify themselves, do not expose it to your web users!
                sid: "<?php echo $this->get_site_setting("sid") ?>",
                // bot settings
                bot_enabled: <?php echo $using_bot ? "true" : "false" ?>,
                // image types for link name display
                image_types: [".jpg", ".jpeg", ".png", ".gif", ".svg"],
            };

        </script>

        <div class="operator-area">

            <div id="chatButtons" class="button-list">
                <span class="menu-button">
                    <input id="btnReady" type="button" class="button" disabled
                           title="Signal that you are ready to go and converse with customers."
                           value="ready for a chat" onClick="ops.operatorReady()" />
                </span>
                <span class="menu-button">
                    <input id="btnBreak" type="button" class="button" disabled
                           title="take a break, stop participating in conversations while you have a break."
                           value="take a break" onClick="ops.operatorTakeBreak()" />
                </span>
                <span class="menu-button">
                    <input id="btnNextUser" type="button" class="button" disabled
                           title="We have finished the current conversation and are ready for a next one."
                           value="next user" onClick="ops.operatorNextUser()" />
                </span>
                <span class="menu-button">
                    <input id="btnBanUser" type="button" class="button" disabled
                           title="The current conversation is abusive or bad spirited, ban this user from the system."
                           value="ban user" onClick="ops.confirmBanUser()" />
                </span>
                <span id="botCount"></span>
            </div>

            <div id="chat1">
                <div class="conversation-parent">
                    <div id="conversationList" class="conversation-container"></div>
                </div>
                <div>
                    <span><input id="txtResponse" class="text-response" type="text" disabled maxlength="200"
                           onkeyup="ops.operator_key_press(event, this.value)" placeholder="your response" aria-label="response" />
                    </span>
                    <span>
                        <input type="button" id="btnChat" class="button" value="chat" disabled
                               title="your response (available when connected to a user)" onClick="ops.reply_click(null)" />
                    </span>
                </div>
            </div>


            <div id="learningSection" class="learnings">
                <img id="tick" alt="success" class="learn-tick" src="<?php echo $this->asset_folder . 'images/tick.svg'?>"/>
                <div>
                    <span class="learn-label">question</span>
                    <span id="txtQuestion" class="question-text"></span>
                </div>
                <div>
                    <span class="learn-label">answer</span>
                    <span id="txtAnswer" class="answer-text"></span>
                </div>

                <div id="twoSection">
                    <input type="button" class="button learn-button" value="clear" onClick="ops.clearQA()" />
                    <input type="button" class="button learn-button" value="teach SimSage" onClick="ops.teach()" />
                </div>
                <div id="oneSection" class="learn-buttons">
                    <input type="button" class="button learn-button" value="clear" onClick="ops.clearQA()" />
                </div>
            </div>

            <div id="previousAnswerSection" class="previous-answer">
                <div class="previous-answer-title">a previous answer to this question exists</div>
                <div id="txtPreviousAnswer" class="previous-answer-answer"></div>
                <div class="previous-answer-buttons">
                    <input type="button" class="button learn-button" value="don't use" onClick="ops.dontUse()" />
                    <input id="btnPreviousAnswer" type="button" class="button learn-button" value="use in 5 secs" onClick="ops.use()" />
                </div>
            </div>

        </div>

    <?php } else if ( $has_access && $has_sites && !$using_bot ) { ?>
        <div class="label-success">You have elected not to use the bots, so the operator interface has been disabled.<br/>
            You can change this setting <a href="/wp-admin/options-general.php?page=simsage-search&tab=bot">here</a> (look for <i>Use the SimSage bot along with search</i>).
        </div>

    <?php } else if ( $has_access ) { ?>
        <div class="label-success">Please <a href="/wp-admin/options-general.php?page=simsage-search">configure</a> your SimSage plugin first.</div>
    <?php } else if ( !$has_access ) { ?>
        <div class="label-success">Your plan does not provide you with Operator Access.  Please <a href="<?php echo SIMSAGE_REGO_SERVER; ?>/#/sign-in?origin=plugin" target="_blank">upgrade your plan</a> if you wish to have Operator Support.</div>
    <?php } ?>

</div>

