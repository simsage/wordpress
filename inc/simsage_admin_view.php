<?php
/**
 * the "view" of the admin page, the HTML controls and their control bindings and form POST
 * for updating the plugin's configuration
 *
 */
?>

<script lang="js">
    all_urls = [<?php echo '"'.implode('","', simsage_get_wp_contents()).'"' ?>];

    ignore_urls = [];           // set by our stored items
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

    function render_list(list, title, fn) {
        let str = "<div class=\"title\">" + title + "</div>";
        for (let i in list) {
            if (list.hasOwnProperty(i)) {
                let item = list[i];
                str += "<div class=\"url\" onclick=\"" + js + "('" + item + "')\">" + item + "</div>";
            }
        }
        return str;
    }

</script>


<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <style>
        fieldset {margin-bottom: 20px;}
        .location-label { font-weight: 600; font-size: 1.1em; margin-top: 10px; text-transform: uppercase; margin-bottom: 10px; }
        .label-success { font-weight: 600; font-size: 1.1em; margin-top: 10px; margin-bottom: 20px; line-height: 20px; }
        .wide-text { width: 500px; }
        .radio_label { margin-right: 4px; font-weight: bold;}
        .tabbed-display { margin-top: 20px; margin-left: 10px; }
        .tab-disabled { color: #ccc; cursor: default; pointer-events: none; }
        .two-lists-side-by-side {
            width: 640px; height: 400px;
            margin: 50px 0 0 50px !important;
            cursor: default;
        }
        .available-list { margin: 0 20px 0 0 !important; width: 280px; height: 390px; float: left; overflow: auto; }
        .ignore-list { margin: 0 0 0 0 !important; width: 280px; height: 390px; float: left;  overflow: auto; }
        .instructions { font-size: 14px; font-weight: 600; margin-bottom: 10px;}
        .title { font-size: 12px; font-style: italic; margin-bottom: 10px;}
        .url { font-size: 12px; cursor: pointer;}
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
                        <input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_fragment_size]" type="number" class="number-field" id="simsage_fragment_size"
                               value="<?php echo (isset($options['simsage_fragment_size']) && $options['simsage_fragment_size'] != '') ? sanitize_text_field($options['simsage_fragment_size']) : '3'; ?>"
                               placeholder="number of fragments per search-result" />
                        <span class="description">Fragment size, the number of matches shown inside each document, between <?php echo $this->get_default_field("simsage_fragment_size", "min") ?> and <?php echo $this->get_default_field("simsage_fragment_size", "max") ?> (default <?php echo $this->get_default_field("simsage_fragment_size", "value") ?>)</span>
                    </label>
                </fieldset>

                <fieldset>
                    <label>
                        <input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_word_distance]" type="number" class="number-field" id="simsage_word_distance"
                               value="<?php echo (isset($options['simsage_word_distance']) && $options['simsage_word_distance'] != '') ? sanitize_text_field($options['simsage_word_distance']) : '20'; ?>"
                               placeholder="maximum distance between keywords"/>
                        <span class="description">Maximum distance between keywords between <?php echo $this->get_default_field("simsage_word_distance", "min") ?> and <?php echo $this->get_default_field("simsage_word_distance", "max") ?> (use 0 for document level search, default <?php echo $this->get_default_field("simsage_word_distance", "value") ?>)</span>
                    </label>
                </fieldset>

                <fieldset>
                    <label>
                        <span class="radio_label">no</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_override_default_search]" type="radio" value="0" <?php echo (!isset($options['simsage_override_default_search']) || $options['simsage_override_default_search'] != '1') ? 'checked' : ''; ?> />
                        <span class="radio_label">yes</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_override_default_search]" type="radio" value="1" <?php echo (isset($options['simsage_override_default_search']) && $options['simsage_override_default_search'] == '1') ? 'checked' : ''; ?> />
                        <span class="description">Override the default WordPress Search.</span>
                    </label>
                </fieldset>

                <fieldset>
                    <label>
                        <span class="radio_label">no</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_adv_filter]" type="radio" value="0" <?php echo (isset($options['simsage_adv_filter']) && $options['simsage_adv_filter'] == '0') ? 'checked' : ''; ?> />
                        <span class="radio_label">yes</span><input name="<?php echo SIMSAGE_PLUGIN_NAME ?>[simsage_adv_filter]" type="radio" value="1" <?php echo (!isset($options['simsage_adv_filter']) || $options['simsage_adv_filter'] != '0') ? 'checked' : ''; ?> />
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

                    <div class="instructions">Click on URLs in either list to move them across</div>

                    <div class="available-list">
                    </div>

                    <div class="ignore-list">
                    </div>

                </div>

            </div>

            <script lang="js">
                jQuery(".available-list").html(render_list(available_urls, "Pages indexed by SimSage", "deselect_item"));
                jQuery(".ignore-list").html(render_list(ignore_urls, "Pages ignored by SimSage", "select_item"));
            </script>

        <?php } ?>


    </form>

</div>

