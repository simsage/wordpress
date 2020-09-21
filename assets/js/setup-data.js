
// create an instance of this class
const analytics = new SimsageAnalytics(an_update_ui);

// startup - connect our plugin to a SimSage server
jQuery(document).ready(function () {
    // get our analytics
    analytics.getAnalytics();
    // setup the jQuery date picker
    jQuery(".datepicker").datepicker({
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'MM yy',
        onClose: function(dateText, inst) {
            const date = new Date(inst.selectedYear, inst.selectedMonth, 1);
            jQuery(this).datepicker('setDate', date);
            analytics.set_date(date);
        }
    }).datepicker('setDate', new Date());
});


function an_update_ui(analytics) {
    // do we have an error message to display?
    if (analytics.error.length > 0) {
        jQuery(".error-text").html(analytics.error);
        jQuery(".error-dialog").show();
    } else {
        jQuery(".error-dialog").hide();
    }
    // remove any classes for non active items
    for (const tab of analytics.tab_list) {
        if (tab !== analytics.tab) {
            jQuery("#div_" + tab).hide();
            jQuery("#tab_" + tab).removeClass("nav-tab-active");
        }
    }
    // select active tab
    jQuery("#div_" + analytics.tab).show();
    jQuery("#tab_" + analytics.tab).addClass("nav-tab-active");

    // draw bar graphs
    const dateLabel = jQuery("#txtDatePicker").val();
    jQuery("#search-analytics").html("");
    analytics.draw_graph("#search-analytics",
        'Monthly Searches in ' + dateLabel,
        'Days', 'Number of Searches', dateLabel,
        analytics.search_frequencies);

    jQuery("#keyword-analytics").html("");
    analytics.draw_graph("#keyword-analytics",
        'Keyword Most often Searched for in ' + dateLabel,
        'Keyword', 'Number of Times used', dateLabel,
        analytics.search_keyword_frequencies);
}

