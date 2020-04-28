
// create an instance of this class
const analytics = new SimsageAnalytics(update_ui);

// startup - connect our plugin to a SimSage server
jQuery(function($) {
    $(document).ready(function () {
        // get our analytics
        analytics.getAnalytics();
        // setup the jQuery date picker
        $(".datepicker").datepicker({
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            dateFormat: 'MM yy',
            onClose: function(dateText, inst) {
                const date = new Date(inst.selectedYear, inst.selectedMonth, 1);
                $(this).datepicker('setDate', date);
                analytics.set_date(date);
            }
        }).datepicker('setDate', new Date());
    })
});


function update_ui(analytics) {
    jQuery(function($) {
        // do we have an error message to display?
        if (analytics.error.length > 0) {
            $(".error-text").html(analytics.error);
            $(".error-dialog").show();
        } else {
            $(".error-dialog").hide();
        }
        // remove any classes for non active items
        for (const tab of analytics.tab_list) {
            if (tab !== analytics.tab) {
                $("#div_" + tab).hide();
                $("#tab_" + tab).removeClass("nav-tab-active");
            }
        }
        // select active tab
        $("#div_" + analytics.tab).show();
        $("#tab_" + analytics.tab).addClass("nav-tab-active");

        // draw bar graphs
        const dateLabel = $("#txtDatePicker").val();
        $("#search-analytics").html("");
        analytics.draw_graph("#search-analytics",
            'Monthly Searches in ' + dateLabel,
            'Days', 'Number of Searches', dateLabel,
            analytics.search_frequencies);

        $("#keyword-analytics").html("");
        analytics.draw_graph("#keyword-analytics",
            'Keyword Most often Searched for in ' + dateLabel,
            'Keyword', 'Number of Times used', dateLabel,
            analytics.search_keyword_frequencies);
    })
}
