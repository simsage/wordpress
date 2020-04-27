//
// SimSage Analytics class
//


class SimsageAnalytics {

    constructor(update_ui) {
        this.update_ui = update_ui;
        this.response = '';
        this.error = '';
        this.busy = true;
    }

    refresh() {
    }

    // fetch the current set of analytics
    getAnalytics() {
        const self = this;
        this.error = '';
        this.busy = true;

        const url = settings.base_url + '/stats/wp-stats/' + settings.organisationId + '/' +
                    settings.kbId + '/' + settings.sid + '/2020/04/10';
        jQuery.ajax({
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            'type': 'GET',
            'url': url,
            'success': function (data) {
                alert(JSON.stringify(data));
                self.refresh();
            }

        }).fail(function (err) {
            console.error(JSON.stringify(err));
            if (err && err["readyState"] === 0 && err["status"] === 0) {
                self.error = "Server not responding, not connected.";
            } else {
                self.error = err;
            }
            self.busy = false;
            self.refresh();
        });
    }

}
