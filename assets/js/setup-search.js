

// create an instance of the main SimSage search Class providing all our functionality
const search = new SemanticSearch(update_ui);

/**
 * callback from the search control for display logic
 * @param search
 */
function update_ui(search) {
    // should we display the advanced search menu?
    jQuery(function($){
        const busy = search.busy; // are we busy searching at the moment?

        // do we have an error message to display?
        if (search.error.length > 0) {
            $(".error-text").html(search.error);
            $(".error-dialog").show();
        } else {
            $(".error-dialog").hide();
            if (search.search_query === '') {
                $(".search-box").val(search.search_query);
            }
            if (!busy) {
                const html = search.get_semantic_search_html(); // the main search html to render
                $(".search-centered-page").html(html);
            }
        }

        // advanced menu show/hide
        if (search.show_advanced_search) {
            $(".advanced-search-area").show();
        } else {
            $(".advanced-search-area").hide();
        }
        // change the advanced button styling if it has items selected in it
        const advanced_search = $(".advanced-search-button");
        if (search.has_advanced_selection) {
            advanced_search.removeClass("advanced-search");
            advanced_search.addClass("advanced-search-selected");
        } else {
            advanced_search.addClass("advanced-search");
            advanced_search.removeClass("advanced-search-selected");
        }

        // do we need to clear the controls associated with the advanced search panel?
        if (search.clear_adv_search) {
            search.clear_adv_search = false;
            $(".metadata-text").val("");
            $(".adv-search-item-select").val("");
        }

        // operator button visible?
        if (search.is_connected && settings.can_contact_ops_direct) {
            $(".operator-button-box").show();
        } else {
            $(".operator-button-box").hide();
        }

        // disable any and all input controls when we're busy querying SimSage
        if (busy) {
            $(".input-properties").attr('disabled', 'disabled');
        } else {
            $(".input-properties").removeAttr('disabled');
        }

        // operator styling depending on connection
        const operatorSelector = $(".operator-selector");
        if (search.assignedOperatorId.length === 0) {
            operatorSelector.removeClass("operator-button-box-connected");
            operatorSelector.addClass("operator-button-box");
            operatorSelector.attr('title', 'call operator');
        } else {
            operatorSelector.removeClass("operator-button-box");
            operatorSelector.addClass("operator-button-box-connected");
            operatorSelector.attr('title', 'disconnect operator');
        }

        // show a details page?
        const detailsSelector = $(".details-page");
        if (!busy && search.show_details) {
            detailsSelector.html(search.details_html);
            detailsSelector.show();
        } else {
            detailsSelector.hide();
        }

        // no results at all and no operator? (and we've been 'searching')
        const no_results = $(".no-results-centered-page");
        if (!busy && search.searching && search.bot_text.length === 0 && search.semantic_search_results.length === 0 &&
            search.search_query.length > 0 && search.assignedOperatorId.length === 0) {
            no_results.html(search.no_results());
            no_results.show();
        } else {
            no_results.hide();
        }

        // render the bot's interests using template.js
        render_bot(search, $(".bubble-speech-container"), $(".bubble-text"), $(".bot-buttons"));

    });
}


// startup - connect our plugin to a SimSage server
jQuery(function($) {
    $(document).ready(function () {
        // connect to our web-sockets for two way conversations
        search.ws_connect();
    })
});
