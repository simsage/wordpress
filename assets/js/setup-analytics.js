
// create an instance of this class
const analytics = new SimsageAnalytics();

// startup - connect our plugin to a SimSage server
jQuery(function($) {
    $(document).ready(function () {
        // get our analytics
        analytics.getAnalytics();
    })
});
