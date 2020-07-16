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

    console.log(settings);

</script>

<!-- filter button, search bar, and search button -->
<div class="centered-page">
	<div class="search-control">
		<div class="advanced-search-button advanced-search no-select input-properties" title="advanced search"
             onclick="search.toggle_advanced_search()" style="<?php echo $this->get_user_boolean_value_conditionally("simsage_adv_filter", 'display: none;') ?>">
            <div class="select-text">filter <span class="advanced-search-arrow">&#9660;</span></div>
		</div>
		<div class="search-text-box input-properties">
			<input type="text" class="input-text search-box input-properties" onclick="search.hide_advanced_search()"
                   onkeyup="search.search_key_press(event, this.value)">
		</div>
		<div class="search-button-box input-properties" title="search" onclick="search.search_click()">
			<img src="<?php echo $this->asset_folder . 'images/search.svg'?>" class="search-image" alt="search">
		</div>
		<div class="operator-selector operator-button-box input-properties" title="call operator" onclick="search.getOperatorHelp()"
             style="<?php echo $this->get_user_boolean_value_conditionally("use_operator", 'display: none;') ?>">
			<img src="<?php echo $this->asset_folder . 'images/operator.svg'?>" class="operator-image" alt="call operator">
		</div>
	</div>


    <!-- advanced search button and menu -->
	<div class="advanced-search-area" style="display: none;">
		<div class="floaty">
			<div class="adv-search-row">
				<span class="search-label">document type</span>
				<select name="document-type" class="adv-search-item-select input-properties" onchange="search.update_advanced_search({'type': this.options[this.selectedIndex].value.split(',')})">
					<option value="">not selected</option>
					<option value="html,htm">web pages</option>
					<option value="doc,docx">word documents</option>
					<option value="pdf">PDF documents</option>
					<option value="xls,xlsx">spreadsheets</option>
					<option value="jpg,jpeg,png,gif">images</option>
				</select>
			</div>
			<div class="adv-search-row">
				<span class="search-label">title</span>
				<input class="metadata-text input-properties" placeholder="title filter" type="text" onkeyup="search.update_advanced_search({'title': [this.value]})" />
			</div>
			<div class="adv-search-row">
				<span class="search-label">url</span>
				<input class="metadata-text input-properties" placeholder="url filter" type="text" onkeyup="search.update_advanced_search({'url': [this.value]})" />
			</div>
			<div class="adv-search-row">
				<span class="search-label">author</span>
				<input class="metadata-text input-properties" placeholder="author filter" type="text" onkeyup="search.update_advanced_search({'author': [this.value]})" />
			</div>
			<div class="button-row">
				<div class="metadata-buttons input-properties" title="clear all filters"
                     onclick="search.clear_advanced_search()">clear</div>
			</div>
		</div>
	</div>

    <!-- error message display bar -->
    <div class="error-dialog">
        <span class="close-button" onclick="this.parentElement.style.display='none'; search.close_error();">&times;</span>
        <div class="error-text"></div>
    </div>

    <!-- semantic search details page -->
    <div class="details-page">
    </div>

    <!-- speech bubble for assistant replies -->
	<div class="bubble-speech-container">
        <div class="bubble-left">
            <div class="bubble-speaker-icon"><img src="<?php echo $this->asset_folder . 'images/human.svg'?>" alt="SimSage image" class="bubble-speaker-icon-image" title="SimSage" /></div>
            <div class="bubble-close-icon" title="close this speech bubble" onclick="search.hide_speech_bubble()">
                <img src="<?php echo $this->asset_folder . 'images/dark-close.svg' ?>" alt="close" class="bubble-close-image"/>
            </div>
            <div class="bubble-text"></div>
        </div>
		<div class="bot-buttons"></div>
	</div>

    <!-- semantic search results container -->
    <div class="search-centered-page">
    </div>

    <div class="no-results-centered-page">
    </div>

</div>
