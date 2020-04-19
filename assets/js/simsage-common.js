
// timeout for office 365 sessions
const session_timeout_in_mins = 59;

//
// manage a SimSage connection
//
class SimSageCommon {

    constructor() {
        this.is_connected = false;    // connected to endpoint?
        this.stompClient = null;      // the connection
        this.ws_base = settings.ws_base;    // endpoint
        this.endpointId = SimSageCommon.getClientId();  // for search, this is the client's endpoint
        // are we busy doing something (communicating with the outside world)
        this.busy = false;
        // error message
        this.error = '';
        this.searching = false;     // was the engine performing a search or other duties?
        this.connection_retry_count = 1;
        this.is_typing = false; // are we receiving a "typing" message?
        this.typing_last_seen = 0; // last time
        // assigned operator
        this.assignedOperatorId = '';
        this.typingTimer = null;
    }

    // do nothing - overwritten
    refresh() {
        console.error('refresh() not overwritten');
    }

    // notify of an is-typing (true/false) event and refresh on state-change
    isTyping(isTyping) {
        const now = SimSageCommon.getSystemTime();
        if (isTyping) {
            this.typing_last_seen = now + 2000;
            if (!this.is_typing && isTyping) {
                this.is_typing = isTyping;
                this.refresh();
            }
        } else if (this.typing_last_seen < now  && this.is_typing) {
            this.is_typing = false;
            this.refresh();
        }
    }


    // the client (search UI) is typing, send a message to an operator is there is one
    clientIsTyping() {
        if (this.assignedOperatorId && this.assignedOperatorId.length > 0) {
            const data = {
                fromId: SimSageCommon.getClientId(),
                toId: this.assignedOperatorId,
                isTyping: true
            };
            this.send_message('/ws/ops/typing', data);
        }
    }


    // do nothing - overwritten
    receive_ws_data() {
        console.error('receive_ws_data() not overwritten');
    }

    // close the error dialog - remove any error settings
    close_error() {
        this.error = '';
        this.searching = false;
    }

    // connect to SimSage
    ws_connect() {
        const self = this;
        if (!this.is_connected && this.ws_base) {
            // this is the socket end-point
            const socket = new SockJS(this.ws_base);
            this.stompClient = Stomp.over(socket);
            this.stompClient.connect({},
                function (frame) {
                    self.stompClient.subscribe('/chat/' + self.endpointId, function (answer) {
                        self.receive_ws_data(JSON.parse(answer.body));
                    });
                    self.set_connected(true);
                },
                (err) => {
                    console.error(err);
                    this.set_connected(false);
                });
        }
    }

    set_connected(is_connected) {
        console.log('is_connected:' + is_connected);
        this.is_connected = is_connected;
        if (!is_connected) {
            if (this.stompClient !== null) {
                this.stompClient.disconnect();
                this.stompClient = null;
            }
            if (this.connection_retry_count > 1) {
                this.error = 'not connected, trying to re-connect, please wait (try ' + this.connection_retry_count + ')';
            }
            setTimeout(this.ws_connect.bind(this), 5000); // try and re-connect as a one-off in 5 seconds
            this.connection_retry_count += 1;

            // switch off typing timer until we re-connect
            this.is_typing = false;
            if (this.typingTimer !== null) {
                window.clearInterval(this.typingTimer);
                this.typingTimer = null;
            }

        } else {
            this.error = '';
            this.connection_retry_count = 1;
            this.stompClient.debug = null;

            // setup is-typing check
            if (this.typingTimer === null) {
                this.typingTimer = window.setInterval(() => this.isTyping(false), 1000);
            }
        }
        this.refresh();
    }

    send_message(endPoint, data) {
        if (this.is_connected) {
            this.error = '';
            this.stompClient.send(endPoint, {}, JSON.stringify(data));
        }
    }


    // ask the platform to provide access to an operator now
    getOperatorHelp() {
        this.searching = false;  // we're not performing a search
        const self = this;
        const data = {
            "organisationId": settings.organisationId,
            "kbList": [{"kbId": settings.kbId, "sid": ""}], // sids not used
            "clientId": SimSageCommon.getClientId(),
        };

        this.error = '';
        this.busy = true;
        this.refresh(); // notify ui

        if (this.assignedOperatorId.length === 0) { // call?
            const url = settings.base_url + '/ops/contact/operator';
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'type': 'POST',
                'data': JSON.stringify(data),
                'url': url,
                'dataType': 'json',
                'success': function (data) {
                    // no organisationId - meaning - no operator available
                    if (!data.organisationId || data.organisationId.length === 0) {
                        self.error = ui_settings.operator_message;
                    } else {
                        self.error = "";
                    }
                    self.busy = false;
                    // set the assigned operator
                    self.assignedOperatorId = data.assignedOperatorId;
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
        } else {
            // or disconnect?
            const url = settings.base_url + '/ops/disconnect/operator';
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'type': 'POST',
                'data': JSON.stringify(data),
                'url': url,
                'dataType': 'json',
                'success': function (data) {
                    self.error = "";
                    self.busy = false;
                    // set the assigned operator
                    self.assignedOperatorId = '';
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

    /////////////////////////////////////////////////////////////////////////////////////////
    // static helpers

    // create a random guid
    static guid() {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        }
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
    }

    // do we hav access to local-storage?
    static hasLocalStorage(){
        try {
            const test = 'test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch(e) {
            return false;
        }
    }

    // get or create a session based client id for SimSage usage
    static getClientId() {
        let clientId = "";
        const key = 'simsearch_client_id';
        const hasLs = SimSageCommon.hasLocalStorage();
        if (hasLs) {
            clientId = localStorage.getItem(key);
        }
        if (!clientId || clientId.length === 0) {
            clientId = SimSageCommon.guid(); // create a new client id
            if (hasLs) {
                localStorage.setItem(key, clientId);
            }
        }
        return clientId;
    }

    // get or create a session based client id for SimSage usage
    static getOperatorId() {
        let operatorId = "";
        const key = 'simsearch_operator_id';
        const hasLs = SimSageCommon.hasLocalStorage();
        if (hasLs) {
            operatorId = localStorage.getItem(key);
        }
        if (!operatorId || operatorId.length === 0) {
            operatorId = SimSageCommon.guid(); // create a new client id
            if (hasLs) {
                localStorage.setItem(key, operatorId);
            }
        }
        return operatorId;
    }

    // get a name from a url, either 'link' or 'image'
    static get_url_name(url) {
        if (url && url.length > 0) {
            // image or page?
            const name = url.toLowerCase().trim();
            for (const image_extn of settings.image_types) {
                if (name.endsWith(image_extn)) {
                    return "image";
                }
            }
            return "link";
        }
        return "";
    }

    // clear a session
    static clearClientId() {
        const key = 'simsearch_client_id';
        const hasLs = SimSageCommon.hasLocalStorage();
        if (hasLs) {
            localStorage.removeItem(key);
            this.sign_out();
            return true;
        }
        return false;
    }

    // replace highlight items from SimSage with style items for the UI display
    static highlight(str) {
        let str2 = str.replace(/{hl1:}/g, "<span class='hl1'>");
        str2 = str2.replace(/{hl2:}/g, "<span class='hl2'>");
        str2 = str2.replace(/{hl3:}/g, "<span class='hl3'>");
        str2 = str2.replace(/{:hl1}/g, "</span>");
        str2 = str2.replace(/{:hl2}/g, "</span>");
        str2 = str2.replace(/{:hl3}/g, "</span>");
        return str2;
    }

    // make sure a string doesn't exceed a certain size - otherwise cut it down
    static adjust_size(str) {
        if (str.length > 20) {
            return str.substr(0,10) + "..." + str.substr(str.length - 10);
        }
        return str;
    }

    // join string items in a list together with spaces
    static join(list) {
        let str = '';
        for (const item of list) {
            str += ' ' + item;
        }
        return str.trim();
    }

    // if this is a syn-set and its selections, return those
    static getSynSet(context_item) {
        if (context_item.synSetLemma && context_item.synSetLemma.length > 0 && context_item.synSetCloud) {
            const word = context_item.synSetLemma;
            return {"word": word.toLowerCase().trim(), "clouds": context_item.synSetCloud};
        }
        return null;
    }

    // return the unique list of words in text_str as a list
    static getUniqueWordsAsList(text_str) {
        const parts = text_str.split(" ");
        const newList = [];
        const duplicates = {};
        for (const _part of parts) {
            const part = _part.trim().toLowerCase();
            if (part.length > 0) {
                if (!duplicates.hasOwnProperty(part)) {
                    duplicates[part] = 1;
                    newList.push(_part.trim());
                }
            }
        }
        return newList;
    }

    // get current time in milli-seconds
    static getSystemTime() {
        return new Date().getTime();
    }

}

