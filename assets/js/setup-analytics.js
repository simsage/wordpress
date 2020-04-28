
// create an instance of this class
const analytics = new SimsageAnalytics(update_ui);

// startup - connect our plugin to a SimSage server
jQuery(function($) {
    $(document).ready(function () {
        // get our analytics
        analytics.getAnalytics();
    })
});


function update_ui(analytics) {
    jQuery(function($) {

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

        analytics.draw_graph("#search-analytics",
            'Monthly Searches in April 2020',
            'Days', 'Number of Searches', 'April 2020',
            analytics.search_frequencies);

        analytics.draw_graph("#bot-analytics",
            'Monthly Bot Queries in April 2020',
            'Days', 'Number of Bot Queries', 'April 2020',
            analytics.bot_frequencies);

        analytics.draw_graph("#general-analytics",
            'General Statistics for April 2020',
            'Data', 'Count', 'April 2020',
            analytics.general_frequencies);

        analytics.draw_graph("#keyword-analytics",
            'Keyword Most often Searched for in April 2020',
            'Keyword', 'Number of Times used', 'April 2020',
            analytics.search_keyword_frequencies);
    })
}
