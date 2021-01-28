<?php
/**
 * the "view" of the admin page, the HTML controls and their control bindings and form POST
 * for updating the plugin's configuration
 *
 */
?>

<script lang="js">
    all_urls = [<?php echo '"'.implode('","', simsage_get_wp_contents()).'"' ?>];
    ignore_urls = [<?php echo '"'.implode('","', $this->get_ignore_urls()).'"' ?>];
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

</script>


<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <style>
        fieldset {margin-bottom: 20px;}
        .location-label { font-weight: 600; font-size: 1.1em; margin-top: 10px; text-transform: uppercase; margin-bottom: 10px; }
        .label-success { font-weight: 600; font-size: 1.1em; margin-top: 10px; margin-bottom: 20px; line-height: 20px; }
        .radio_label { margin-right: 4px; font-weight: bold;}
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
    <h2>SimSage Plugin Options</h2>

	<?php
	$active_tab = '';
	if( isset( $_GET[ 'tab' ] ) ) {
		$active_tab = sanitize_text_field($_GET[ 'tab' ]);
	} // end if
	$options = get_option( SIMSAGE_PLUGIN_NAME );
    $plan = simsage_get_plan();
	// add the nonce, option_page, action and referer.
	settings_fields( SIMSAGE_PLUGIN_NAME );
	do_settings_sections( SIMSAGE_PLUGIN_NAME );

	// flags for controlling the forms and tabs
    // after signing-in we get an account set and saved locally
    $has_account = isset($options['simsage_account'] ) && isset($options['simsage_account']['id']);
    // when we have selected a site, this variable will be set
    $has_kb = $has_account && isset($options['simsage_account']['kbId']);
    // list of bot items (or initial empty array)
    $qa_list = isset($options['simsage_qa']) ? $options['simsage_qa'] : array();
    // list of synonyms
    $synonym_list = isset($options['simsage_synonyms']) ? $options['simsage_synonyms'] : array();
	?>

    <?php if ( !$has_account && ($active_tab == 'account' || $active_tab == '') ) { ?>
        <div class="label-success">
            Please enter your SimSage registration key below.<br />
            <a href="<?php echo $this->get_portal_server(); ?>/#/create?origin=plugin" target="_blank">Register here</a> if you don't have an account.
        </div>
	<?php } ?>

    <div class="nav-tab-wrapper">
        <a href="?page=simsage-search&tab=account" class="nav-tab <?php echo ($active_tab == 'account' || $active_tab == '') ? 'nav-tab-active' : ''; ?>">Account</a>
        <a href="?page=simsage-search&tab=search" class="nav-tab <?php echo $active_tab == 'search' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Search</a>
        <a href="?page=simsage-search&tab=filters" class="nav-tab <?php echo $active_tab == 'filters' ? 'nav-tab-active' : ''; ?> <?php if ( ! $has_kb ) echo 'tab-disabled' ?>">Content Filters</a>
    </div>

    <form method="post" id="adminForm" name="<?php echo SIMSAGE_PLUGIN_NAME; ?>_search_options">

        <?php if ( $active_tab == 'account' || $active_tab == '' ) { ?>
            <div class="tabbed-display">

            <!-- the user sets their location globally for SimSage to operate against -->
            <fieldset>
                <div class="location-label">your location</div>
                <label>
                    <div><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_server_location]" type="radio" value="0" <?php echo (!isset($options['simsage_server_location']) || $options['simsage_server_location'] != '1') ? 'checked' : ''; ?> /><span class="radio_label">Europe</span></div>
                    <div><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_server_location]" type="radio" value="1" <?php echo (isset($options['simsage_server_location']) && $options['simsage_server_location'] == '1') ? 'checked' : ''; ?> /><span class="radio_label">Australia / New Zealand</span></div>
                    <?php if ( SIMSAGE_USE_TEST ) { ?>
                        <div><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_server_location]" type="radio" value="2" <?php echo (isset($options['simsage_server_location']) && $options['simsage_server_location'] == '2') ? 'checked' : ''; ?> /><span class="radio_label">NZ Test</span></div>
                        <div><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_server_location]" type="radio" value="3" <?php echo (isset($options['simsage_server_location']) && $options['simsage_server_location'] == '3') ? 'checked' : ''; ?> /><span class="radio_label">Dev</span></div>
                    <?php } ?>
                    <br />
                    <div><span class="radio_label">other locations currently not supported</span></div>
                </label>
            </fieldset>

            <div style="margin-top: -10px; margin-bottom: 20px;">
                <?php submit_button( 'update location', 'primary','submit', true ); ?>
            </div>


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

                <fieldset>
                    <label>
                        <span class="radio_label">no</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_adv_filter]" type="radio" value="0" <?php echo (!isset($options['simsage_adv_filter']) || $options['simsage_adv_filter'] != '1') ? 'checked' : ''; ?> />
                        <span class="radio_label">yes</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_adv_filter]" type="radio" value="1" <?php echo (isset($options['simsage_adv_filter']) && $options['simsage_adv_filter'] == '1') ? 'checked' : ''; ?> />
                        <span class="description">Show the advanced search filter button.</span>
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

            <script lang="js">

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
                        jQuery(".ignore-list-value").val(ignore_urls.join("|"));
                        render_lists();
                    }
                }

                function select_item(url) {
                    let index = ignore_urls.indexOf(url);
                    if (index >= 0) {
                        ignore_urls.splice(index, 1);
                        available_urls.push(url);
                        jQuery(".ignore-list-value").val(ignore_urls.join("|"));
                        render_lists();
                    }
                }

                render_lists();

            </script>

            <input type="hidden" name="action" value="update-filter">
            <input type="hidden" name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_ignore_url_list]" class="ignore-list-value" value="">
            <div class="spacing-bottom">&nbsp;</div>

            <?php submit_button( 'update Filter Settings', 'primary','submit', true ); ?>

        <?php } ?>


    </form>

</div>

