
// create an instance of this class
const data = new SimsageData(an_update_ui);

// startup - connect our plugin to a SimSage server
jQuery(document).ready(function () {
    // get our analytics
    data.getAnalytics();
    // setup the jQuery date picker
    jQuery(".datepicker").datepicker({
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'MM yy',
        onClose: function(dateText, inst) {
            const date = new Date(inst.selectedYear, inst.selectedMonth, 1);
            jQuery(this).datepicker('setDate', date);
            data.set_date(date);
        }
    }).datepicker('setDate', new Date());
});


function an_update_ui(data) {
    // do we have an error message to display?
    if (data.error.length > 0) {
        jQuery(".error-text").html(data.error);
        jQuery(".error-dialog").show();
    } else {
        jQuery(".error-dialog").hide();
    }
    // remove any classes for non active items
    for (const tab of data.tab_list) {
        if (tab !== data.tab) {
            jQuery("#div_" + tab).hide();
            jQuery("#tab_" + tab).removeClass("nav-tab-active");
        }
    }
    // select active tab
    jQuery("#div_" + data.tab).show();
    jQuery("#tab_" + data.tab).addClass("nav-tab-active");

    // draw bar graphs
    const dateLabel = jQuery("#txtDatePicker").val();
    jQuery("#search-analytics").html("");
    data.draw_graph("#search-analytics",
        'Monthly Searches in ' + dateLabel,
        'Days', 'Number of Searches', dateLabel,
        data.search_frequencies);

    jQuery("#keyword-analytics").html("");
    data.draw_graph("#keyword-analytics",
        'Keyword Most often Searched for in ' + dateLabel,
        'Keyword', 'Number of Times used', dateLabel,
        data.search_keyword_frequencies);

    // enable mind-item upload button?
    jQuery(".upload-button").prop('disabled', data.file_binary_data === null || data.busy);
    jQuery(".ss-button").prop('disabled', data.busy);

    // render the inside of the mind-item table
    jQuery("#mindItemList").html(data.renderMindItemTable());

    // set mind-item pagination
    jQuery("#mindItemPagination").html(data.renderMindItemPagination());

    // mind item dialog
    if (data.mind_item_dlg_show) {
        jQuery(".qna-title").html(data.mind_item_dlg_action);
        jQuery("#qna-edit").show();
        jQuery(".mi-q1").val(data.mi_dlg_q1);
        jQuery(".mi-q2").val(data.mi_dlg_q2);
        jQuery(".mi-answer").val(data.mi_dlg_answer);
        jQuery(".mi-links").val(data.mi_dlg_links);
    } else {
        jQuery("#qna-edit").hide();
    }


    // render the inside of the synonym table
    jQuery("#synonymList").html(data.renderSynonymTable());

    // set synonym pagination
    jQuery("#synonymPagination").html(data.renderSynonymPagination());

    // mind item dialog
    if (data.synonym_dlg_show) {
        jQuery("#synonym-edit").show();
        jQuery(".synonym-title").html(data.synonym_dlg_action);
        jQuery(".syn-words").val(data.syn_dlg_words);
    } else {
        jQuery("#synonym-edit").hide();
    }


    // render the inside of the semantic table
    jQuery("#semanticList").html(data.renderSemanticTable());

    // set semantic pagination
    jQuery("#semanticPagination").html(data.renderSemanticPagination());

    // mind item dialog
    if (data.semantic_dlg_show) {
        jQuery("#semantic-edit").show();
        jQuery(".semantic-title").html(data.semantic_dlg_action);
        jQuery(".sem-word").val(data.sem_dlg_word);
        jQuery(".sem-semantic").val(data.sem_dlg_semantic);
    } else {
        jQuery("#semantic-edit").hide();
    }

}

