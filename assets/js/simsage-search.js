
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// the search bar
let simsage = {

    // ws-response message types
    mt_Disconnect: "disconnect",
    mt_Error: "error",
    mt_Message: "message",
    mt_Email: "email",
    mt_IsTyping: "typing",
    mt_SignIn: "sign-in",
    mt_SignOut: "sign-out",
    mt_OperatorAvailable: "operator-available",
    mt_OperatorChat: "operator-chat",
    mt_OperatorMessage: "operator-message",

    // the service layer end-point, change "<server>" to ... (no / at end)
    base_url: "https://cloud.simsage.ai",
    // api version of ws_base
    api_version: 1,
    // the organisation's id to search, change "<organisation>" to...
    organisationId: "",

    // do we have an operator?
    operator_enabled: true,
    category_size: 5,   // size of category lists
    page_size: 5,           // number of pages per page in search
    page_size_custom: 10,   // number of pages per page in custom view
    currency_symbol: "$",

    // search settings
    fragment_count: 3,
    max_word_distance: 20,
    use_spelling_suggest: false,
    context_label: '',
    context_match_boost: 0.02,
    // bot sensitivity - controls the A.I's replies - we suggest you don't change it!
    bot_threshold: 0.8125,
    // image types for link name display
    image_types: [".jpg", ".jpeg", ".png", ".gif", ".svg"],
    // placeholder for search
    search_placeholder: "",

    kb_list: [],        // list of knowledge-bases
    kb: null,           // the selected knowledge-base
    source_list: [],    // list of sources
    source: null,       // the selected source

    is_busy: false,     // is the system busy?

    connection_retry_count: 1,  // retry-count for any errors and need to connect

    assigned_operator_id: "",   // the id of an assigned operator
    client_typing_last_seen: 0, // timer for typing
    typing_timeout: 1500,       // typing animation timeout in ms
    typing_repeat_timeout: 750, // don't send too many is-typing requests to the server
    operator_count: 0,          // how many operators available
    chat_list: [],              // list of chat messages

    is_custom_render: false,        // is this a custom render display?
    filters_visible: false,         // are the filters visible

    semantic_search_results: [],    // semantic search results
    semantic_search_result_map: {}, // url => semantic_search_result
    semantic_search_result_map_id: {}, // urlId => semantic_search_result

    // single instance
    instantiate() {
        // add other modules
        jQuery.extend(this, common_functions);
        jQuery.extend(this, search_options_control);
        jQuery.extend(this, no_results);
        jQuery.extend(this, search_details);
        jQuery.extend(this, sign_in_control);
        jQuery.extend(this, spelling_control);
        jQuery.extend(this, search_bot);
        jQuery.extend(this, pagination_control);
        jQuery.extend(this, chat_control);
        jQuery.extend(this, search_results_control);
        jQuery.extend(this, domain_control);
        simsage = this;
        return this;
    },

    // perform a search
    do_search: function(control_class) {
        let self = this;
        let text = "";
        // get the search text from the dom
        if (control_class) {
            text = jQuery("." + control_class).val();
        } else {
            text = jQuery(".search-text").val();
        }
        if (!text) {
            text = jQuery(".simsage-static-query").val();
        }
        if (this.kb && (this.is_custom_render || (text && text.trim() !== ''))) {
            // do we need to reset the pagination?
            if (this.reset_pagination(text)) {
                this.close_bot();
            }
            // create the query and clear the errors
            this.error('');
            text = cleanup_query_text(text);
            let search_query_str = super_search_query_str(text);
            if (search_query_str !== '()') {
                simsage.search_query = text;
                this.busy(true);
                let sourceId = "";
                if (this.source && this.source.id) {
                    sourceId = this.source.id;
                }
                let clientQuery = {
                    'organisationId': this.organisationId,
                    'kbList': [this.kb.id],
                    'clientId': this.get_client_id(),
                    'semanticSearch': true,     // always a search
                    'query': search_query_str,  // search query
                    'queryText': text,          // raw text
                    'numResults': 1,              // bot results
                    'scoreThreshold': this.bot_threshold,
                    'page': this.page,
                    'pageSize': this.page_size,
                    'shardSizeList': this.shard_size_list,
                    'fragmentCount': this.fragment_count,
                    'maxWordDistance': this.max_word_distance,
                    'spellingSuggest': this.use_spelling_suggest,
                    'contextLabel': this.context_label,
                    'contextMatchBoost': this.context_match_boost,
                    'sourceId': sourceId,
                };
                this.post_message('/api/ops/query', clientQuery, function(data) {
                    self.receive_search_results(data);
                });

            } else if (!this.is_custom_render) {
                this.error("Please enter a query to start searching.");
            } else {
                console.debug('is_custom_render: empty query');
            }

        } else if (!this.kb) {
            this.error("Server not responding, not connected.");

        } else if (!this.is_custom_render && (text && text.trim() === '')) {
            this.render_pagination();
        }
    },

    // process a SimSage search result
    receive_search_results: function(data) {
        let self = this;
        if (data.messageType === this.mt_Message) {
            this.is_typing = false;
            this.semantic_search_results = [];
            this.semantic_search_result_map = {};
            this.semantic_search_result_map_id = {};
            this.spelling_suggestion = data.spellingCorrection; // set spelling suggestion if we have one

            // set the assigned operator
            if (data.assignedOperatorId && data.assignedOperatorId.length > 0) {
                this.assigned_operator_id = data.assignedOperatorId;
                if (data.operatorName) {
                    this.operator_name = data.operatorName;
                } else {
                    this.operator_name = '';
                }
            }

            // did we get semantic search results?
            if (data.resultList) {
                this.shard_size_list = data.shardSizeList;
                data.resultList.map(function (sr) {
                    if (!sr.botResult) {
                        // enhance search result for display
                        if (sr.textIndex >= 0 && sr.textIndex < sr.textList.length) {
                            sr['index'] = sr.textIndex;  // inner offset index
                        } else {
                            sr['index'] = 0;  // inner offset index
                        }
                        sr['num_results'] = sr.textList.length;
                        self.semantic_search_results.push(sr);  // add item
                        self.semantic_search_result_map[sr.url] = sr;
                        self.semantic_search_result_map_id[sr.urlId] = sr;
                    }
                });
                this.num_results = data.totalDocumentCount;
                let divided = data.totalDocumentCount / this.page_size;
                this.num_pages = parseInt("" + divided);
                if (parseInt("" + divided) < divided) {
                    this.num_pages += 1;
                }
            } else {
                // cleanup
                this.num_results = 0;
                this.num_pages = 0;
                this.shard_size_list = [];
            }

            // did we get an bot reply?
            let dt = this.unix_time_convert(new Date().getTime());
            if (data.hasResult && data.text && data.text.length > 0) {
                // setup the bot
                this.show_bot(data.text, data.urlList, data.imageList);
            }

            // copy the know email flag from our results
            if (!this.know_email && data.knowEmail) {
                this.know_email = data.knowEmail;
            }

            // render whatever needs to be rendered, even if no results
            this.render_search_results();
            this.close_no_search_results();
            // only hide pagination if normal search
            if (!this.is_custom_render) {
                this.hide_pagination();
            }

            // no results?
            if (!data.hasResult) {
                // hide
                this.hide_search_results();
                // did we get assigned an operator?
                if (this.assigned_operator_id && this.assigned_operator_id.length > 0) {
                    let text = this.operator_name ? "you are chatting with " + this.operator_name :
                        "you are chatting with an Operator"
                    this.chat_list.push({
                        timeStamp: new Date().getTime(),
                        isSpecialMessage: true,
                        text: text
                    });
                    this.update_chat_window();
                    this.show_chat_button(true, this.chat_list.length > 0);
                    this.show_chat();

                } else if (!this.is_custom_render) {
                    // don't show no-search results for a custom render
                    this.show_no_search_results();
                }

            } else if (this.semantic_search_results && this.semantic_search_results.length > 0) {
                // hide if no actual results
                this.show_search_results();
            }
        } // if message-type is right
    },

    search_typing: function(event, control_class) {
        if (event.keyCode === 13) {
            event.stopPropagation();
            this.do_search(control_class);
            return false;
        }
        return true;
    },

    busy: function(is_busy) {
        this.is_busy = is_busy;
    },

    on_change_kb: function (kb_id) {
        this.source_list = [];
        this.is_custom_render = false;
        for (let i in this.kb_list) {
            if (this.kb_list.hasOwnProperty(i)) {
                let kb = this.kb_list[i];
                if (kb.id == kb_id) {
                    this.kb = kb;
                    for (let j in kb.sourceList) {
                        if (kb.sourceList.hasOwnProperty(j)) {
                            let source = kb.sourceList[j];
                            this.source_list.push({"name": source.name, "id": source.sourceId, "category_list": source.categoryList});
                        }
                    }
                    // select a default source if there is only one
                    if (this.source_list.length === 1) {
                        this.on_change_source(this.source_list[0].id);
                    }
                }
            }
        }
        // setup the drop down boxes in the UI
        // deal with domains
        this.setup_domains();
        this.setup_office_365_user();
        // setup any potential domains
        this.change_domain(null);
    },

    // change source by id
    on_change_source: function(source_id) {
        // make sure we have a valid source-id
        if (source_id === undefined) {
            source_id = jQuery(".dd-source").val();
        }
        this.source = null;
        this.is_custom_render = false;
        for (let i in this.source_list) {
            if (this.source_list.hasOwnProperty(i)) {
                let source = this.source_list[i];
                if (source.id == source_id) {
                    this.source = source;
                    // force display of filters if db system
                    if (source.category_list && source.category_list.length > 0) {
                        this.filters_visible = true;
                        this.is_custom_render = true;
                        this.page_size = this.page_size_custom;
                        this.show_pagination(); // needed for sorting etc.
                    }
                } // if is correct source
            }
        }
        // setup the drop down boxes in the UI
        // deal with domains
        this.setup_domains();
        this.setup_office_365_user();
        // setup any potential domains
        this.change_domain(null);
        if (!this.is_custom_render) {
            this.hide_search_results(); // hide otherwise
        } else {
            this.do_search();
        }
        this.render_pagination();
    },

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// is this a key CR or Space-bar event that should activate something?
function activation(event) {
    if (event && (event.keyCode === 13 || event.keyCode === 32)) {
        event.stopPropagation();
        return true;
    }
    return false;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// set of support functions used by each control - and copied into each control by setup
let common_functions = {
    // strings that are too big are clipped
    adjust_size: function(str, size) {
        if (size && str.length > size) {
            return str.substr(0,size) + "...";
        }
        return str;
    },
    // replace < and > to make a string html safe
    esc_html: function(str) {
        if (str) {
            if (typeof str === 'string' || str instanceof String) {
                str = str
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;");
                str = str
                    .replace(/&lt;br \/&gt;/g, "<br />");
                return str;
            } else {
                return str;
            }
        }
        return "";
    },
    ie11_polyfill: function() {
        if (!String.prototype.padStart) {
            String.prototype.padStart = function padStart(targetLength, padString) {
                targetLength = targetLength>>0; //truncate if number or convert non-number to 0;
                padString = String((typeof padString !== 'undefined' ? padString : ' '));
                if (this.length > targetLength) {
                    return String(this);
                }
                else {
                    targetLength = targetLength-this.length;
                    if (targetLength > padString.length) {
                        padString += padString.repeat(targetLength/padString.length); //append to original to ensure we are longer than needed
                    }
                    return padString.slice(0,targetLength) + String(this);
                }
            }
        }
    },
    s4: function() {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    },
    guid: function() {
        return this.s4() + this.s4() + '-' + this.s4() + '-' + this.s4() + '-' + this.s4() + '-' + this.s4() + this.s4() + this.s4();
    },
    has_local_storage: function() {
        try {
            let test = 'test';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            return true;
        } catch(e) {
            return false;
        }
    },
    get_client_id() {
        let clientId = "";
        let key = 'simsearch_client_id';
        let hasLs = this.has_local_storage();
        if (hasLs) {
            clientId = localStorage.getItem(key);
        }
        if (!clientId || clientId.length === 0) {
            clientId = this.guid(); // create a new client id
            if (hasLs) {
                localStorage.setItem(key, clientId);
            }
        }
        return clientId;
    },
    get_kb() {
        if (simsage && simsage.kb) {
            return simsage.kb;
        }
        return null;
    },
    get_kb_id() {
        let kb = this.get_kb();
        if (kb && kb.id) {
            return kb.id;
        }
        return null;
    },
    // valid an email address
    validate_email: function(email) {
        let i1 = email.indexOf('@');
        if (i1 > 0) {
            let i2 = email.lastIndexOf('.');
            return (i2 > i1) && i2 + 2 < email.length;
        }
        return false;
    },
    // set text focus on an item and make sure the text cursor is at the end of that field
    focus_text: function(ctrl) {
        const c = jQuery(ctrl);
        c.focus();
        const txt = c.val();
        c.val("");
        c.val(txt);
    },
    // for click events, stop propagating
    nop: function() {
        if (event) event.stopPropagation();
    },
    // show an error message
    error: function(err) {
        if (err) {
            jQuery(".error-dialog-box").show();

            let err_str = "";
            if (err && err["readyState"] === 0 && err["status"] === 0) {
                err_str = "Server not responding, not connected.";
            } else if (err && err["responseText"] && err["status"] > 299) {
                err_str = err["responseText"];
            } else {
                err_str = err;
            }
            jQuery(".error-text").html(this.esc_html(err_str));

        } else {
            this.close_error();
        }
    },
    // close the error display
    close_error: function() {
        jQuery(".error-text").val("");
        jQuery(".error-dialog-box").hide();
    },
    size_to_str: function(size) {
        if (size < 1024) {
            return size;
        } else if (size < 1024000) {
            return Math.floor(size / 1000) + "KB";
        } else if (size < 1024000000) {
            return Math.floor(size / 1000000) + "MB";
        } else {
            return Math.floor(size / 1000000000) + "GB";
        }
    },
    pad2: function(item) {
        return ("" + item).padStart(2, '0');
    },
    unix_time_convert: function(timestamp) {
        if (timestamp > 1000) {
            let a = new Date(timestamp);
            let year = a.getFullYear();
            let month = a.getMonth() + 1;
            let date = a.getDate();
            let hour = a.getHours();
            let min = a.getMinutes();
            let sec = a.getSeconds();
            return year + '/' + this.pad2(month) + '/' + this.pad2(date) + ' ' + this.pad2(hour) + ':' + this.pad2(min) + ':' + this.pad2(sec);
        }
        return "";
    },
    // post a message to the operator end-points
    post_message: function(endPoint, data, callback) {
        const self = this;
        let url = this.base_url + endPoint;
        url = url.replace("//api", "/api");
        jQuery.ajax({
            headers: {
                'Content-Type': 'application/json',
                'API-Version': this.api_version,
            },
            'data': JSON.stringify(data),
            'type': 'POST',
            'url': url,
            'dataType': 'json',
            'success': function (data) {
                if (callback) {
                    callback(data);
                }
            }
        }).fail(function (err) {
            console.error(JSON.stringify(err));
            self.error(err);
        });
    },
    // get a message
    get_message: function(endPoint, callback_success, callback_fail) {
        const self = this;
        let url = this.base_url + endPoint;
        url = url.replace("//api", "/api");
        jQuery.ajax({
            headers: {
                'Content-Type': 'application/json',
                'API-Version': this.api_version,
            },
            'type': 'GET',
            'url': url,
            'dataType': 'json',
            'success': function (data) {
                if (callback_success) {
                    callback_success(data);
                }
            }
        }).fail(function (err) {
            if (callback_fail) {
                callback_fail(err);
            } else {
                console.error(JSON.stringify(err));
                self.error(err);
            }
        });
    },
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// control taking care of the search options on screen (advanced search menu)
//
let search_options_control = {
    menu_visible: false, // is the attached menu showing?
    open: false,  // is the menu open or closed?

    // perform the initial startup setup
    init: function(control_id, settings, cb) {
        let self = this;
        if (!settings || !settings.organisation_id || !settings.base_url) {
            console.error("SimSage init() failed, settings must be set with an 'organisation_id' and a 'base_url'")
            return null;
        }
        // get the settings we do have
        this.base_url = settings.base_url;
        this.organisationId = settings.organisation_id;
        // get possible setting overrides
        // do we have an operator?
        if (typeof settings.operator_enabled === "boolean") this.operator_enabled = settings.operator_enabled;
        // size of category lists
        if (typeof settings.category_size === "number") this.category_size = settings.category_size;
        // number of pages per page in search
        if (typeof settings.page_size === "number") this.page_size = settings.page_size;
        // number of pages per page in custom view
        if (typeof settings.page_size_custom === "number") this.page_size_custom = settings.page_size_custom;
        if (typeof settings.currency_symbol === "string") this.currency_symbol = settings.currency_symbol;
        // search settings
        if (typeof settings.fragment_count === "number") this.fragment_count = settings.fragment_count;
        if (typeof settings.max_word_distance === "number") this.max_word_distance = settings.max_word_distance;
        if (typeof settings.use_spelling_suggest === "boolean") this.use_spelling_suggest = settings.use_spelling_suggest;
        if (typeof settings.context_label === "string") this.context_label = settings.context_label;
        if (typeof settings.context_match_boost === "number") this.context_match_boost = settings.context_match_boost;
        // bot sensitivity - controls the A.I's replies - we suggest you don't change it!
        if (typeof settings.bot_threshold === "number") this.bot_threshold = settings.bot_threshold;
        // placeholder for search text-control
        if (typeof settings.search_placeholder === "string") this.search_placeholder = settings.search_placeholder;

        console.log("SimSage init, url:" + this.base_url + ", organisation-id:" + this.organisationId,
                    ", operator-enabled:" + this.operator_enabled);

        this.render_pagination();
        // setup a polyfill for IE 11 functions we use
        this.ie11_polyfill();
        // set a placeholder?
        if (this.search_placeholder && this.search_placeholder.length > 0) {
            jQuery(".search-text").attr("placeholder", this.search_placeholder);
        }

        // monitor the ESC key to close dialog boxes
        jQuery(document).on('keydown', function(event) {
            if (event.key === "Escape" || event.key === "Esc") {
                const err_ctrl = jQuery(".error-dialog-box");
                if (err_ctrl.is(":visible")) {
                    err_ctrl.hide();
                } else {
                    simsage.close_chat();
                    simsage.close_search_details();
                    simsage.close_sign_in();
                    simsage.close_no_search_results();
                    simsage.close_bot();
                }
            }
        });

        let url = '/api/knowledgebase/search/info/' + encodeURIComponent(this.organisationId) + '/' + encodeURIComponent(this.get_client_id());
        this.get_message(url, function(data) {
            self.kb_list = data.kbList;
            if (self.kb_list && self.kb_list.length > 0) {
                self.kb = self.kb_list[0];
                self.kbId = self.kb_list[0].id;
            }
            self.operator_count = data.operatorCount;
            // get any previously assigned operator on refresh
            self.assigned_operator_id = '';
            if (data.assignedOperatorId) {
                self.assigned_operator_id = data.assignedOperatorId;
            }
            self.operator_name = '';
            if (data.operatorName) {
                self.operator_name = data.operatorName;
            }
            self.show_chat_button(this.operator_enabled && (self.operator_count > 0 || (self.assigned_operator_id && self.assigned_operator_id.length > 0)),
                self.chat_list.length > 0);
            // wordpress override
            if (this.kbId && this.kbId.length > 0) {
                self.kb = {"name": "wordpress knowledge-base", "id": this.kbId, "sourceList": []};
                self.on_change_kb(self.kb.id);
            }
            self.error('');
            self.connection_retry_count = 1;
            self.busy(false);
            if (self.operator_enabled) {
                // connect to the socket system
                self.connect_ws();
                // setup is-typing check
                window.setInterval(function () {
                    self.operator_was_typing(false)
                }, 1000);
            }

            if (cb) {
                cb(true);
            }
        }, function(err) {
            console.log(err);
            if (self.connection_retry_count > 1) {
                self.error('not connected, trying to re-connect, please wait (try ' + self.connection_retry_count + ')');
            } else {
                self.error(err);
                cb(false);
            }
            setTimeout(function() { self.init(null, null, cb) }, 5000); // try and re-connect as a one-off in 5 seconds
            self.connection_retry_count += 1;
        });
    },

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// the no-results handling
//
let no_results = {

    know_email: false,

    // single instance
    instantiate() {
        jQuery.extend(this, common_functions);
        return this;
    },
    email_typing: function(event) {
        if (event.keyCode === 13) {
            this.do_email();
        }
    },
    // user asks for help
    do_email: function() {
        let self = this;
        let email_address = jQuery(".email-text").val();
        if (email_address && this.validate_email(email_address.trim())) {
            this.error('');
            let emailMessage = {
                'messageType': this.mt_Email,
                'organisationId': this.organisationId,
                'kbList': [this.get_kb_id()],
                'clientId': this.get_client_id(),
                'emailAddress': email_address,
            };
            this.post_message('/api/ops/email', emailMessage, function(data) {
                self.receive_ws_data(data);
                self.show_no_search_results(); // update display
            });
        } else {
            this.error("invalid email address");
        }
    },

    show_no_search_results: function() {
        this.close_bot();
        jQuery(".no-search-results").show();
        jQuery(".not-found-words").html(this.adjust_size(simsage.search_query, 25));
        jQuery(".simsage-search-results-list").hide();
        if (this.know_email) {
            jQuery(".ask-email-box").hide();
            jQuery(".ask-emailed-box").show();
        } else {
            jQuery(".ask-email-box").show();
            jQuery(".ask-emailed-box").hide();
        }
    },

    close_no_search_results: function() {
        jQuery(".no-search-results").hide();
    },
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// search details display, single instance
//
let search_details = {
    // helper: render a single detail of the details view
    render_single_detail: function(label, text) {
        let str = "<div class=\"row-height align-top\">\n" +
            "<span class=\"label\" title=\"[label]\">[label]</span><span class=\"text\" title=\"[text]\">[text]</span>\n" +
            "</div>\n"
        str = str
            .replace(/\[label]/g, this.esc_html(label))
            .replace(/\[text]/g, this.esc_html(text));
        return str;
    },

    // helper: render a single detail, but with a clickable url
    render_single_detail_url: function(label, url) {
        let str = "<div class=\"row-height align-top\">\n" +
            "<span class=\"label\" title=\"[label]\">[label]</span><span class=\"url\" onclick=\"window.open('[url]', '_blank')\" title=\"[text]\">[text]</span>\n" +
            "</div>\n"
        str = str
            .replace(/\[label]/g, this.esc_html(label))
            .replace(/\[text]/g, this.esc_html(url));
        return str;
    },

    get_preview_url: function() {
        return this.base_url + '/api/document/preview/' + this.organisationId + '/' + this.kb.id + '/' +
            this.get_client_id();
    },

    // render the details of a single result
    render_details_view: function(result) {
        if (result) {
            let str = "<table class=\"details-table\">\n" +
                "<tbody><tr class=\"align-top whole-row\">\n" +
                "<td class=\"align-top row-1\">\n";
            str += this.render_single_detail_url("url", result.url);
            if (result.title) {
                str += this.render_single_detail("title", result.title);
            }
            if (result.urlId > 0) {
                str += this.render_single_detail("document id", result.urlId);
            }
            if (result.author) {
                str += this.render_single_detail("author", result.author);
            }
            if (result.documentType) {
                str += this.render_single_detail("document type", result.documentType);
            }
            if (result.binarySize > 0) {
                str += this.render_single_detail("document byte-size", this.size_to_str(result.binarySize));
            }
            if (result.textSize > 0) {
                str += this.render_single_detail("text byte-size", this.size_to_str(result.textSize));
            }
            if (result.source) {
                str += this.render_single_detail("source/origin", result.source);
            }
            if (result.created > 0) {
                str += this.render_single_detail("document created date/time", this.unix_time_convert(result.created));
            }
            if (result.lastModified > 0) {
                str += this.render_single_detail("document last-modified date/time", this.unix_time_convert(result.lastModified));
            }
            if (result.uploaded > 0) {
                str += this.render_single_detail("pipeline last-crawled date/time", this.unix_time_convert(result.uploaded));
            }
            if (result.converted > 0) {
                str += this.render_single_detail("pipeline last-converted date/time", this.unix_time_convert(result.converted));
            }
            if (result.parsed > 0) {
                str += this.render_single_detail("pipeline last-parsed date/time", this.unix_time_convert(result.parsed));
            }
            if (result.indexed > 0) {
                str += this.render_single_detail("pipeline last-indexed date/time", this.unix_time_convert(result.indexed));
            }
            if (result.previewed > 0) {
                str += this.render_single_detail("pipeline last-preview date/time", this.unix_time_convert(result.previewed));
            }
            if (result.numSentences > 0) {
                str += this.render_single_detail("number of sentences", result.numSentences);
            }
            if (result.numWords > 0) {
                str += this.render_single_detail("number of words", result.numWords);
            }
            if (result.numRelationships > 0) {
                str += this.render_single_detail("number of relationships", result.numRelationships);
            }
            for (let key in result.metadata) {
                // check if the property/key is defined in the object itself, not in parent
                if (key.indexOf('{') === -1 && result.metadata.hasOwnProperty(key)) {
                    let value = result.metadata[key];
                    if (value.indexOf("<") === -1) { // no tags or anything allowed - don't render
                        str += this.render_single_detail(key, value);
                    }
                }
            }
            str += "</td>\n" +
                "<td rowspan=\"20\" class=\"image-row align-top\">\n" +
                "<img src=\"[image]\" class=\"preview-image\" alt=\"page preview\" />\n" +
                "</td>\n" +
                "</tr>\n" +
                "</tbody>\n" +
                "</table>\n";
            str = str
                .replace(/\[url]/g,this. esc_html(result.url))
                .replace(/\[image]/g, this.get_preview_url() + '/' + this.esc_html(result.urlId) + '/0');
            jQuery(".detail-table").html(str);
        } else {
            jQuery(".detail-table").html("");
        }
    },

    show_search_details_by_id: function(result_id) {
        if (this.semantic_search_result_map_id && this.semantic_search_result_map_id.hasOwnProperty(result_id)) {
            this.show_search_details(this.semantic_search_result_map_id[result_id]);
        }
    },

    show_search_details: function(result) {
        this.render_details_view(result);
        jQuery(".search-details-view").show();
    },

    close_search_details: function() {
        jQuery(".search-details-view").hide();
    }

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// control for signing into SimSage
//
let sign_in_control = {

    show_sign_in: function() {
        jQuery(".search-sign-in").show();
    },

    setup_domains: function() {
    },

    setup_office_365_user: function() {
    },

    // setup any potential domains
    change_domain: function(domain) {
    },

    do_sign_in: function() {
        alert("todo: do_sign_in");
    },

    close_sign_in: function() {
        jQuery(".password").val("");
        jQuery(".search-sign-in").hide();
    },

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// control taking care of the search options on screen
//
let spelling_control = {

    text: "",

    show_spelling_suggestion: function(text) {
        if (text && text.length > 0) {
            this.text = this.esc_html(text);
            jQuery(".spelling-label-text").html("Did you mean, " + this.text + "?");
            jQuery(".spelling-box-view").show();
        }
    },

    use_spelling_suggestion: function() {
        if (this.text && this.text.length > 0 && jQuery(".search-text")) {
            jQuery(".search-text").val(this.text);
        }
    },

    close_spelling_suggestion: function() {
        jQuery(".spelling-box-view").hide();
    },

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// the bot display
//
let search_bot = {

    // single instance
    instantiate() {
        jQuery.extend(this, common_functions);
        return this;
    },

    show_bot: function(text, link_list, image_list) {
        jQuery(".bot-reply-text").html(this.esc_html(text));
        let time = this.unix_time_convert(new Date().getTime());
        jQuery(".bot-label-text").html("Bot, " + time);
        jQuery(".bot-box-view").show();
        // add the links
        let str = "";
        if (link_list && link_list.length > 0) {
            if (image_list && image_list.length > 0) {
                str += "<div title='images' class='bot-images-box'>";
                for (let i in image_list) {
                    let link = image_list[i];
                    if (link) {
                        str += "<img src=\"" + link + "\" class='bot-image-link' alt='image'" + link + "</img>\n";
                    }
                }
                str += "</div>\n"
            }
            for (let i in link_list) {
                let link = link_list[i];
                if (link) {
                    str += "<div class='bot-link' title='visit " + link + "'\"><a href=\"" + link + "\" target='_blank'>" + link + "</a></div>\n";
                }
            }
            jQuery(".bot-links").html(str);
        } else {
            jQuery(".bot-links").html("");
        }
    },
    close_bot: function() {
        jQuery(".bot-reply-text").html("");
        jQuery(".bot-box-view").hide();
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// the pagination control
//
let pagination_control = {

    // pagination
    num_pages: 0,       // search result information
    num_results: 0,
    page: 0,            // current page
    page_size: this.page_size,      // the size of pages

    selected_view: "text",          // the text or image view for non-custom render displays
    last_query: "",
    shard_size_list: [],            // sharding list

    do_change_page_size: function() {
        this.page_size = parseInt(jQuery(".dd-page-size").val());
        this.do_search();
    },

    select_text_view: function() {
        this.selected_view = "text";
        this.do_search();
    },

    select_image_view: function() {
        this.selected_view = "image";
        this.do_search();
    },

    prev_page: function() {
        if (this.page > 0) {
            this.page -= 1;
            this.do_search();
        }
    },

    next_page: function() {
        if ((this.page + 1) < this.num_pages) {
            this.page += 1;
            this.do_search();
        }
    },

    show_pagination: function() {
        jQuery(".pagination-box").show();
    },

    hide_pagination: function() {
        jQuery(".pagination-box").hide();
    },

    // reset the variables used in determining pagination if the query has changed, return true if reset
    reset_pagination: function(query_text) {
        if (this.last_query !== query_text) {
            this.last_query = query_text;
            this.page = 0;  // reset to page 0
            this.num_pages = 0;
            this.num_results = 0;
            this.shard_size_list = [];
            this.semantic_search_results = [];
            this.semantic_search_result_map = {};
            this.semantic_search_result_map_id = {};
            return true;
        }
        return false;
    },

    // render the pagination on-screen
    render_pagination: function() {
        let num_pages = this.num_pages > 0 ? this.num_pages : 1;
        let num_results = this.num_results + " results";
        if (this.num_results === 0) {
            num_results = "no results";
        } else if (this.num_results === 1) {
            num_results = "one result";
        }
        let page_str = "page " + (this.page + 1) + " of " + num_pages;
        let ps = this.page_size;
        let str = "<span class=\"pagination-text\" title=\"" + num_results + "\">" + num_results + "</span>\n" +
            "<span class=\"pagination-text page-size-sel\" title=\"\">\n" +
            "<label>\n" +
            "<select name=\"page-size\" class=\"page-size-select dd-page-size\" tabindex=\"0\" onchange=\"simsage.do_change_page_size()\" title=\"set the number of results per page\">\n" +
            "<option value=\"5\" " + (ps===5 ? "selected" : "") + ">5 results per page</option>\n" +
            "<option value=\"10\" " + (ps===10 ? "selected" : "") + ">10 results per page</option>\n" +
            "<option value=\"15\" " + (ps===15 ? "selected" : "") + ">15 results per page</option>\n" +
            "<option value=\"20\" " + (ps===20 ? "selected" : "") + ">20 results per page</option>\n" +
            "</select>\n" +
            "</label>\n" +
            "</span>\n";

        if (this.page > 0) {
            str += "<span class=\"prev-page-box pagination-left\" title=\"go to the previous page\" onclick=\"simsage.prev_page()\">\n" +
                "<span class=\"arrow-enabled\" alt=\"previous page\">&#x2190;</span>\n" +
                "</span>\n";
        } else {
            str += "<span class=\"prev-page-box-disabled pagination-left\" title=\"there is no previous page\">\n" +
                "<span class=\"arrow-disabled\" alt=\"previous page disabled\">&#x2190;</span>\n" +
                "</span>\n";
        }

        str += "<span class=\"pagination-text\" title=\"" + page_str + "\">" + page_str + "</span>\n";
        if (this.page + 1 < this.num_pages) {
            str += "<span class=\"next-page-box\" title=\"go to the next page\" onclick=\"simsage.next_page()\">" +
                "<span class=\"arrow-enabled\" alt=\"next page\">&#x2192;</span>\n" +
                "</span>\n";
        } else {
            str += "<span class=\"next-page-box-disabled\" title=\"there is no next page\">" +
                "<span class=\"arrow-disabled\" alt=\"next page disabled\">&#x2192;</span>\n" +
                "</span>\n";
        }

        str += "<span class=\"view-type-box\">\n";

        if (this.selected_view === "text") {
            str += "<span class=\"pagination-text view-results-as-box\" title=\"view results as\">View results as: </span>\n";
            str +=
                "<span class=\"view-text-outer-box text-view\" tabindex=\"0\" title=\"text view\" onkeyup=\"if (activation(event)) this.onclick()\" onclick=\"simsage.select_text_view()\">\n" +
                "<span class=\"view-text-box\">&nbsp;&#x1f5b9;</span>\n" +
                "<span class=\"view-results-as-text\">Text</span>\n" +
                "</span>\n";

            str +=
                "<span class=\"view-images-box-disabled image-view\" tabindex=\"0\" title=\"image view\" onkeyup=\"if (activation(event)) this.onclick()\" onclick=\"simsage.select_image_view()\">\n" +
                "<span class=\"view-image-box-disabled\">&nbsp;&#x1f5bb;</span>\n" +
                "<span class=\"view-results-as-text-disabled\">Image</span>\n" +
                "</span>\n";
        } else {
            str += "<span class=\"pagination-text view-results-as-box\" title=\"view results as\">View results as: </span>\n";
            str +=
                "<span class=\"view-text-outer-box-disabled text-view\" tabindex=\"0\" title=\"text view\" onkeyup=\"if (activation(event)) this.onclick()\" onclick=\"simsage.select_text_view()\">\n" +
                "<span class=\"view-text-box-disabled\">&nbsp;&#x1f5b9;</span>\n" +
                "<span class=\"view-results-as-text-disabled\">Text</span>\n" +
                "</span>\n";

            str +=
                "<span class=\"view-images-box image-view\" tabindex=\"0\" title=\"image view\" onkeyup=\"if (activation(event)) this.onclick()\" onclick=\"simsage.select_image_view()\">\n" +
                "<span class=\"view-image-box\">&nbsp;&#x1f5bb;</span>\n" +
                "<span class=\"view-results-as-text\">Image</span>\n" +
                "</span>\n";
        }

        str += "</span>\n";
        jQuery(".pagination-box").html(str);
    },

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// chat control button and text dialog
//

let chat_control = {

    // async chat sockets
    is_ws_connected: false,                        // connected to endpoint?
    stompClient: null,                          // the connection

    close_chat: function() {
        jQuery(".operator-chat-box-view").hide();
    },

    show_chat: function() {
        let ctrl = jQuery(".operator-chat-box-view");
        if (ctrl.is(":visible")) {
            ctrl.hide();
        } else {
            ctrl.show();
        }
    },

    do_chat: function() {
        let ctrl = jQuery(".chat-text");
        let text = ctrl.val();
        let self = this;
        if (this.kb && text.trim().length > 0) {
            let data = {
                organisationId: this.organisationId,
                kbList: [this.kb.id],
                clientId: this.get_client_id(),
                assignedOperatorId: this.assigned_operator_id,
                text: text,
            };
            // add an entry into the chat_list
            this.chat_list.push({
                timeStamp: new Date().getTime(),
                isSimSage: false,
                text: text,
                urlList: [],
            });
            ctrl.val("");
            this.update_chat_window();
            this.post_message('/api/ops/client/chat', data, function(data) {
                self.receive_ws_data(data);
            });
        }
    },

    user_is_typing: function() {
        let now = new Date().getTime();
        if (this.assigned_operator_id && this.assigned_operator_id.length > 0 && this.client_typing_last_seen < now) {
            this.client_typing_last_seen = now + this.typing_repeat_timeout;
            let data = {
                fromId: this.get_client_id(),
                toId: this.assigned_operator_id,
                isTyping: true
            };
            simsage.post_message('/api/ops/typing', data, function(data) {
            });
        }
    },

    chat_typing: function(event) {
        let ctrl = jQuery(".chat-text");
        let button = jQuery(".chat-button-control");
        if (ctrl.val().trim().length === 0) {
            button.removeClass("chat-button");
            button.addClass("chat-button-disabled");
        } else {
            if (event.keyCode === 13) {
                button.removeClass("chat-button");
                button.addClass("chat-button-disabled");
                this.do_chat();
            } else {
                button.removeClass("chat-button-disabled");
                button.addClass("chat-button");
                this.user_is_typing();
            }
        }
    },

    operator_was_typing: function(is_typing) {
        let now = new Date().getTime();
        if (is_typing) {
            this.operator_typing_last_seen = now + this.typing_timeout;
            if (!this.operator_typing && is_typing) {
                this.operator_typing = is_typing;
                this.update_chat_window();
            }
        } else if (this.operator_typing_last_seen < now  && this.operator_typing) {
            this.operator_typing = false;
            this.update_chat_window();
        }
    },

    // render an operator part of the conversation
    render_chat: function(chat) {
        let time_str = chat.timeStamp ? simsage.unix_time_convert(chat.timeStamp) : "";
        if (chat.isSpecialMessage) {
            return "<div class=\"disconnected-box\">\n" +
                "<div class=\"disconnected-line\">\n" +
                "<span class=\"disconnected-label\">" + this.esc_html(chat.text) + "</span>\n" +
                "<span class=\"hyphen\">-</span>\n" +
                "<span class=\"time\">" + time_str + "</span>\n" +
                "</div>\n" +
                "</div>\n";
        } else {
            let text_str = this.esc_html(chat.text ? chat.text : "");
            if (chat.isSimSage) {
                let operator_name = this.operator_name ? this.operator_name : "Operator";
                let url_str = "";
                if (chat.urlList) {
                    for (let j in chat.urlList) {
                        if (chat.urlList.hasOwnProperty(j)) {
                            let url = this.esc_html(chat.urlList[j]);
                            url_str += "<div class=\"url\" title=\"" + url + "\" onclick=\"window.open('" + url + "', '_blank');\">" + url + "</div>\n"
                        }
                    }
                }
                return "<div class=\"operator-box\">\n" +
                    "<div class=\"operator-line\">\n" +
                    "<span class=\"operator-label\">" + operator_name + "</span>\n" +
                    "<span class=\"hyphen\">-</span>\n" +
                    "<span class=\"time\">" + time_str + "</span>\n" +
                    "</div>\n" +
                    "<div class=\"operator-text-box\">\n" +
                    "<div class=\"operator-text\">" + text_str + "</div>\n" +
                    url_str +
                    "</div>\n" +
                    "</div>\n";
            } else {
                return "<div class=\"user-box\">\n" +
                    "<div class=\"user-line\">\n" +
                    "<span class=\"user-label\">You</span>\n" +
                    "<span class=\"hyphen\">-</span>\n" +
                    "<span class=\"time\">" + time_str + "</span>\n" +
                    "</div>\n" +
                    "<div class=\"user-text\">" + text_str + "</div>\n" +
                    "</div>\n";
            }
        }
    },

    render_typing_dots: function() {
        return "<div class=\"left-box-white\">\n" +
            "<span class=\"typing-dots-box\"><span class=\"typing-dots-image\">&#x20db;</span></span>\n" +
            "</div>\n";
    },

    render_chats: function() {
        let str = "";
        for (let i in this.chat_list) {
            if (this.chat_list.hasOwnProperty(i)) {
                str += this.render_chat(this.chat_list[i]);
            }
        }
        if (this.operator_typing) {
            str += this.render_typing_dots();
        }
        return str;
    },

    update_chat_window: function() {
        let ctrl = jQuery(".chat-table");
        ctrl.html(this.render_chats());
        // scroll to bottom of chat window to make the most recent message visible
        ctrl.animate({scrollTop: ctrl.prop("scrollHeight")}, 10);
    },

    // show or hide the chat button with the visible flag
    show_chat_button: function(has_operator, has_chat_messages) {
        this.nop();
        if (has_operator || has_chat_messages) {
            jQuery(".chat-button-at-bottom").show();
            // so we no longer have an operator, but have some history in the chat
            // just disable the button - change its colour
            let cc = jQuery(".chat-container");
            let cwu = jQuery(".chat-with-us-text");
            let ci = jQuery(".chat-with-us-image");
            let cwit = jQuery(".chat-with-us-online");
            if (!has_operator) {
                cc.removeClass("online");
                cc.addClass("offline");
                cwu.removeClass("chat-with-us-text-box-online");
                cwu.addClass("chat-with-us-text-box-online-disabled");
                ci.removeClass("chat-with-us-image-white");
                ci.addClass("chat-with-us-image-grey");
                cwit.attr("title", "all Operators offline");
            } else {
                cc.addClass("online");
                cc.removeClass("offline");
                cwu.addClass("chat-with-us-text-box-online");
                cwu.removeClass("chat-with-us-text-box-online-disabled");
                ci.addClass("chat-with-us-image-white");
                ci.removeClass("chat-with-us-image-grey");
                cwit.attr("title", "Chat with us");
            }
        } else {
            jQuery(".chat-button-at-bottom").hide();
        }
    },

    // connect to SimSage for async-socket chat
    connect_ws: function() {
        if (!this.is_ws_connected) {
            let self = this;
            // this is the socket end-point
            let ws_base = this.base_url + '/ws-api';
            let socket = new SockJS(ws_base);
            this.stompClient = Stomp.over(socket);
            // overwrite stomp debug logging
            this.stompClient.debug = function(msg) {};
            this.stompClient.connect({},
                function () {
                    self.stompClient.subscribe('/chat/' + self.get_client_id(), function (answer) {
                        self.receive_ws_data(JSON.parse(answer.body));
                    });
                    self.set_ws_connected(true);
                },
                function(err) {
                    console.error(err);
                    self.set_ws_connected(false);
                });
        }
    },

    // set the connection status of the chat system - and try and reconnect on fail
    set_ws_connected: function(is_ws_connected) {
        this.is_ws_connected = is_ws_connected;
        let self = this;
        if (!is_ws_connected) {
            if (this.stompClient !== null) {
                this.stompClient.disconnect();
                this.stompClient = null;
            }
            if (this.connection_retry_count > 1) {
                this.error('not connected, trying to re-connect, please wait (try ' + this.connection_retry_count + ')');
            }
            setTimeout(function() { this.connect_ws() }, 5000); // try and re-connect as a one-off in 5 seconds
            this.connection_retry_count += 1;

        } else {
            this.error('');
            this.connection_retry_count = 1;
            this.stompClient.debug = null;
        }
    },

    // overwrite: generic web socket receiver
    receive_ws_data: function(data) {
        this.busy(false);
        if (data) {
            if (data.messageType === this.mt_Error && data.error.length > 0) {
                this.error(data.error);  // set an error

            } else if (data.messageType === this.mt_Disconnect) {
                this.assigned_operator_id = ''; // disconnect any operator
                this.operator_name = '';
                this.operator_typing = false;
                // add disconnected message if we've been typing
                if (this.chat_list && this.chat_list.length > 0) {
                    this.chat_list.push({
                        timeStamp: new Date().getTime(),
                        isSpecialMessage: true,
                        text: "operator disconnected"
                    });
                }
                this.update_chat_window();

            } else if (data.messageType === this.mt_Email) {
                this.know_email = true;

            } else if (data.messageType === this.mt_SignIn) {
                if (data.errorMessage && data.errorMessage.length > 0) {
                    error(data.errorMessage);  // set an error
                    this.signed_in = false;
                } else {
                    // sign-in successful
                    this.signed_in = true;
                    this.sign_in_status(true);
                }
                // todo: show we have signed in

            } else if (data.messageType === this.mt_IsTyping) {
                this.operator_was_typing(true);

            } else if (data.messageType === this.mt_SignOut) {
                if (data.errorMessage && data.errorMessage.length > 0) {
                    this.error(data.errorMessage);  // set an error
                } else {
                    // sign-in successful
                    this.signed_in = false;
                    this.sign_in_status(false);
                }

            } else if (data.messageType === this.mt_OperatorAvailable) {
                // we're notified about a change in availability of the number of operators
                this.operator_count = data.operatorCount;
                if (data.assignedOperatorId && data.assignedOperatorId.length > 0) {
                    this.assigned_operator_id = data.assignedOperatorId;
                }
                this.show_chat_button(this.operator_count > 0 || (this.assigned_operator_id && this.assigned_operator_id.length > 0),
                    this.chat_list.length > 0);

            } else if (data.messageType === this.mt_OperatorChat) {
                // we're notified about a change in availability of the number of operators
                this.operator_count = data.operatorCount;
                if (data.assignedOperatorId && data.assignedOperatorId.length > 0) {
                    this.assigned_operator_id = data.assignedOperatorId;
                    if (data.operatorName) {
                        this.operator_name = data.operatorName;
                    } else {
                        this.operator_name = '';
                    }
                } else {
                    // no available operators
                    this.chat_list.push({
                        timeStamp: new Date().getTime(),
                        isSpecialMessage: true,
                        text: "no operators available"
                    });
                }
                this.update_chat_window();
                this.show_chat_button(this.operator_count > 0 || (this.assigned_operator_id && this.assigned_operator_id.length > 0),
                    this.chat_list.length > 0);

            } else if (data.messageType === this.mt_OperatorMessage) {

                // set the assigned operator
                if (data.assignedOperatorId && data.assignedOperatorId.length > 0) {
                    this.assigned_operator_id = data.assignedOperatorId;
                }
                // add an entry into the chat_list
                if (data.text && data.text.length > 0) {
                    this.chat_list.push({
                        timeStamp: new Date().getTime(),
                        isSimSage: true,
                        text: data.text,
                        urlList: [],
                    });
                    this.operator_typing = false;
                    this.update_chat_window();
                }

            } else if (data.messageType === this.mt_Message) {
                this.is_typing = false;
                // set the assigned operator
                if (data.assignedOperatorId && data.assignedOperatorId.length > 0) {
                    this.assigned_operator_id = data.assignedOperatorId;
                    if (data.operatorName) {
                        this.operator_name = data.operatorName;
                    } else {
                        this.operator_name = '';
                    }
                }
                // copy the know email flag from our results
                if (!this.know_email && data.knowEmail) {
                    this.know_email = data.knowEmail;
                }
            }
        }
    },

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// render search results
//

let search_results_control = {

    show_search_results: function() {
        jQuery(".simsage-search-results-list").show();
        jQuery(".search-display").show();
        this.close_no_search_results();
    },

    hide_search_results: function() {
        if (!this.is_custom_render) {
            jQuery(".search-display").hide();
        } else {
            // just empty the search results if this is a db display
            jQuery(".search-results-text").html("");
        }
    },

    prev_fragment: function(id) {
        if (this.semantic_search_result_map_id && this.semantic_search_result_map_id.hasOwnProperty(id)) {
            let result = this.semantic_search_result_map_id[id];
            if (result && result.textIndex > 0) {
                result.textIndex -= 1;
                this.render_search_results();
            }
        }
    },

    next_fragment: function(id) {
        if (this.semantic_search_result_map_id && this.semantic_search_result_map_id.hasOwnProperty(id)) {
            let result = this.semantic_search_result_map_id[id];
            if (result && result.textIndex + 1 < result.textList.length) {
                result.textIndex += 1;
                this.render_search_results();
            }
        }
    },

    open_url: function(url) {
        if (url) {
            window.open(url, '_blank');
        }
    },

    render_single_text_search_result: function(id, url, title, fragment, fragment_index, num_fragments) {
        let str = "<div class=\"search-result\">\n" +
            "<div class=\"search-text-width\">\n" +
            "<a href=\"[url]\" title=\"visit [url]\" target=\"_blank\"><span class=\"url-text\">[split-url]</span></a>\n" +
            "<div title=\"visit [url]\" onclick=\"simsage.open_url('[url]')\" class=\"more-details\"><span class=\"title-text\">[title]</span></div>\n" +
            "<div><span class=\"result-text\">[fragment]</span></div>\n" +
            "<div class=\"navigate-td\">\n";
        if (fragment_index > 0) {
            str += "<span class=\"navigate-left\" tabindex=\"0\" title=\"view the previous relevant fragment in this document\" onkeyup=\"if (activation(event)) this.onclick()\" onclick=\"simsage.prev_fragment([id]);\"><span class=\"navigate-image\">&#x2039;</span></span>\n";
        } else {
            str += "<span class=\"no-navigate-left\" tabindex=\"0\" title=\"there is no previous fragment in this document\"><span class=\"navigate-image-disabled\">&#x2039;</span></span>\n";
        }
        if ((fragment_index + 1) < num_fragments) {
            str += "<span class=\"navigate-right\" tabindex=\"0\" title=\"view the next relevant fragment in this document\" onkeyup=\"if (activation(event)) this.onclick()\" onclick=\"simsage.next_fragment([id]);\"><span class=\"navigate-image\">&#x203a;</span></span>\n";
        } else {
            str += "<span class=\"no-navigate-right\" tabindex=\"0\" title=\"there is no next fragment in this document\"><span class=\"navigate-image-disabled\">&#x203a;</span></span>\n";
        }
        str += "<span class=\"navigate-text\" title=\"Scroll through other relevant search results on this page\">Scroll through other relevant search results on this page</span>\n" +
            "</div>\n" +
            "</div>\n" +
            "<div title=\"view more details\" onkeyup=\"if (activation(event)) this.onclick()\" onclick=\"simsage.show_search_details_by_id([id]);\" tabindex=\"0\" class=\"search-image-width\">\n" +
            "<img src=\"[thumbnail_src]\" alt=\"[title]\" class=\"result-image\"/>\n" +
            "</div>\n" +
            "</div>\n";
        let fragment_str = this.esc_html(fragment)
            .replace(/{hl1:}/g, "<span class='hl1'>")
            .replace(/{:hl1}/g, "</span>")
            .replace(/{hl2:}/g, "<span class='hl2'>")
            .replace(/{:hl2}/g, "</span>");
        str = str
            .replace(/\[url]/g, this.esc_html(url))
            .replace(/\[split-url]/g, this.esc_html(url))
            .replace(/\[id]/g, this.esc_html(id))
            .replace(/\[thumbnail_src]/g, this.get_preview_url() + '/' + this.esc_html(id) + '/-1')
            .replace(/\[title]/g, title && title.length > 0 ? this.esc_html(title) : "(no title)")
            .replace(/\[fragment]/g, fragment_str);
        return str;
    },

    // Render a single search-database result
    render_single_search_db_result: function(id, url, title, html) {
        return html;
    },

    // render a single image result
    render_single_image_search_result: function(id, url, title) {
        let str = "<div class=\"image-result\">" +
            "<div class=\"image-image\" title=\"[title]\" onclick=\"simsage.show_search_details_by_id([id]);\">\n" +
            "<img src=\"[thumbnail_src]\" alt=\"image\" class=\"result-image-image\"/>\n" +
            "</div>\n" +
            "<div class=\"image-text\" title=\"[title]\" onclick=\"simsage.show_search_details_by_id([id]);\">\n" +
            "[title-short]\n" +
            "</div>\n" +
            "</div>\n";
        let title_short = simsage.adjust_size(title, 40);
        str = str
            .replace(/\[url]/g, url)
            .replace(/\[id]/g, id)
            .replace(/\[thumbnail_src]/g, this.get_preview_url() + '/' + id + '/0')
            .replace(/\[title-short]/g, title_short && title_short.length > 0 ? title_short : "(no title)")
            .replace(/\[title]/g, title && title.length > 0 ? title : "(no title)");
        return str;
    },

    get_url_id_from_url: function(url) {
        if (this.semantic_search_result_map.hasOwnProperty(url)) {
            let sr = this.semantic_search_result_map[url];
            if (sr && sr.url === url) {
                return sr.urlId;
            }
        }
        return 0;
    },

    get_image_url_by_doc_url: function(url) {
        // set the image for this item
        let urlId = this.get_url_id_from_url(url);
        return this.base_url + "/api/document/preview/" +
            this.organisationId + "/" + this.kb.id + "/" +
            this.get_client_id() + "/" + urlId + "/-1";
    },

    // update images from ids, ratings, and search result highlighting in records
    update_database_gui: function() {
        let self = this;
        jQuery("div.db-record-rating").each(function() {
            // Get the value
            let val = parseFloat(jQuery(this).html());
            // Make sure that the value is in 0 - 5 range, multiply to get width
            let size = Math.floor(Math.max(0, (Math.min(5, val))));
            let starStr = "";
            for (let i = size; i < 5; i++) {
                starStr += "&#x2606;"
            }
            for (let i = 0; i < size; i++) {
                starStr += "&#x2605;"
            }
            // Replace the numerical value with stars
            jQuery(this).html(starStr);
        });
        jQuery("div.db-record-price").each(function() {
            let val = parseFloat(jQuery(this).html().trim());
            if (val === Math.floor(val)) {
                val = val * 0.01;
            }
            // Replace the numerical value with stars
            $(this).html(simsage.currency_symbol + val.toFixed(2));
        });
        jQuery("div.db-image").each(function() {
            let val = jQuery(this).html().trim();
            let url = self.get_image_url_by_doc_url(val);
            $(this).html("<img src='" + url + "' class='db-image-size' alt='image'/>");
        });
        // fill in span hl1 and hl2
        jQuery(".search-results-text").each(function() {
            let val = jQuery(this).html().trim();
            val = val.replace(/{hl1:}/g, "<span class='hl1'>");
            val = val.replace(/{:hl1}/g, "</span>");
            val = val.replace(/{hl2:}/g, "<span class='hl2'>");
            val = val.replace(/{:hl2}/g, "</span>");
            jQuery(this).html(val);
        });
    },

    clear_search_results: function() {
        jQuery(".simsage-search-results-list").hide();
        jQuery(".search-results-td").html("");
    },

    // return some hard-wired search results - repeated
    render_search_results: function() {
        if (this.is_custom_render) {
            // render a set of DB records
            let reverse_list = this.semantic_search_results; // JSON.parse(JSON.stringify(simsage.semantic_search_results.reverse()));
            let str = "";
            for (let i in reverse_list) {
                if (reverse_list.hasOwnProperty(i)) {
                    let result = reverse_list[i];
                    if (result.url && result.textList && result.textList.length > 0) {
                        str += this.render_single_search_db_result(result.urlId, result.url, result.title, result.textList[0]);
                    }
                }
            }
            jQuery(".search-results-td").html(str);

        } else if (this.selected_view === "text") {

            let str = "";
            for (let i in this.semantic_search_results) {
                if (this.semantic_search_results.hasOwnProperty(i)) {
                    let result = this.semantic_search_results[i];
                    if (result.url) {
                        str += this.render_single_text_search_result(result.urlId, result.url, result.title,
                            result.textList[result.textIndex], result.textIndex, result.textList.length);
                    }
                }
            }
            jQuery(".search-results-td").html(str);

        } else {
            let str = "";
            for (let i in this.semantic_search_results) {
                if (this.semantic_search_results.hasOwnProperty(i)) {
                    let result = this.semantic_search_results[i];
                    if (result.url) {
                        str += this.render_single_image_search_result(result.urlId, result.url, result.title);
                    }
                }
            }
            str += "<div class=\"end-marker-images\"></div>\n";
            jQuery(".search-results-td").html(str);
        }
        this.update_database_gui();
        this.render_pagination();
    },

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// the no-results handling
//

let domain_control = {

    signed_in: false, // are we signed into a domain or something?

    do_change_domain: function() {
        // // what type of domain is this?
        // let domainType = "";
        // let domain_list = simsage.getDomainListForCurrentKB();
        // for (let domain of domain_list) {
        //     if (domain.sourceId == simsage.sourceId) {
        //         domainType = domain.domainType;
        //         break;
        //     }
        // }
        // let div_email = document.getElementById("divEmail");
        // let div_password = document.getElementById("divPassword");
        // let txt_email = document.getElementById("txtEmail");
        //
        // if (domainType === "aad") { // office365
        //     div_email.style.display = "none";
        //     div_password.style.display = "none";
        // } else {
        //     div_email.style.display = "";
        //     div_password.style.display = "";
        //     if (domainType === "simsage") {
        //         txt_email.placeholder = 'SimSage username';
        //     } else {
        //         txt_email.placeholder = 'Active-Directory username';
        //     }
        //     txt_email.focus();
        // }
    },

    show_sign_in: function() {
        if (this.source) {
            jQuery(".sign-in-title").html("sign-in to \"" +
                this.esc_html(this.source.name) + ", " +
                this.esc_html(this.source.domainType) + "\"");
            jQuery(".search-sign-in").show();
        }
    },

    // get or create a session based client id for SimSage usage
    set_office365_user: function(data) {
        let key = 'simsearch_office_365_user';
        let hasLs = simsage.has_local_storage();
        if (hasLs) {
            let to = simsage.session_timeout_in_mins * 60000;
            data.expiry = new Date().getTime() + to; // 1 hour timeout
            localStorage.setItem(key, JSON.stringify(data));
        }
    },

    // get or create a session based client id for SimSage usage
    remove_office365_user: function() {
        let key = 'simsearch_office_365_user';
        let hasLs = simsage.has_local_storage();
        if (hasLs) {
            localStorage.removeItem(key);
        }
    },

    // do the actual sign-in
    do_sign_in: function() {
        let self = this;
        if (this.source) {
            let user_name = jQuery(".sign-in-user-name").val();
            let password = jQuery(".sign-in-password").val();
            let domain = { sourceId: this.source.id, domain: this.source.name, domain_type: this.source.domainType };
            if (domain.domainType === 'aad') { // azure ad
                let user = this.get_office365_user();
                if (!user) {
                    // do we already have the code to sign-in?
                    let urlParams = new URLSearchParams(window.location.search);
                    let code = urlParams.get('code');
                    if (!code) {
                        window.location.href = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=' +
                            domain.clientId + '&response_type=code&redirect_uri=' +
                            encodeURIComponent(domain.redirectUrl) + '&scope=User.ReadBasic.All+offline_access+openid+profile' +
                            '&state=' + this.get_client_id();
                    } else {
                        // login this user, using the code
                        this.setup_office_365_user();
                    }
                } else {
                    // we have a user - assume the client wants to sign-out
                    this.remove_office365_user();
                    this.signed_in = false;
                    this.error('');
                }
            } else if (user_name && user_name.length > 0 && password && password.length > 0 && this.kb) {
                this.busy(true);
                this.error('');
                let adSignInData = {
                    'organisationId': this.organisationId,
                    'kbList': [this.kb.id],
                    'clientId': this.get_client_id(),
                    'sourceId': this.source.id,
                    'userName': user_name,
                    'password': password,
                };
                simsage.post_message('/api/ops/ad/sign-in', adSignInData, function (data) {
                    self.receive_ws_data(data, false);
                });
            }
        }
    },

    do_sign_out: function() {
        let self = this;
        this.error('');
        if (this.kb && this.source) {
            this.busy(true);
            this.remove_office365_user();
            let signOutData = {
                'organisationId': this.organisationId,
                'kbList': [this.kb.id],
                'clientId': this.get_client_id(),
                'sourceId': this.source.id,
            };
            this.post_message('/api/ops/ad/sign-out', signOutData, function (data) {
                self.receive_ws_data(data);
            });
        }
    },

}

// clean text - remove characters we use for special purposes
function cleanup_query_text(text) {
    // remove any : ( ) characters from text first (but not from http: and https:)
    text = text.replace(/\)/g, ' ');
    text = text.replace(/\(/g, ' ');
    text = text.replace(/:/g, ' ');
    text = text.replace(/http \/\//g, 'http://');
    text = text.replace(/https \/\//g, 'https://');
    return text;
}

// helper: turn search-options, text, and custom-rendering data into a super-query string
function super_search_query_str(text) {
    let query = "(";
    if (text.length > 0) {
        query += "body: " + text;
    }
    query += ")";
    return query;
}

// setup search
simsage.instantiate();
jQuery('.search-form').on('keydown keyup keypress', function(e) {
    const $ = jQuery;
    if (!$(this).hasClass("search-form-static")) {
        let keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    }
});

jQuery('.search-button-box').on('click', function(e) {
    e.preventDefault();
    return false;
});

// init when ready and do the search from the query parameters
jQuery(document).ready(function () {
    simsage.init("", settings, function(isSuccess) {
        if (isSuccess && document.querySelector('.simsage-static-query')) {
            const query_str = jQuery(".simsage-static-query").val();
            simsage.do_search('simsage-static-query');
            jQuery(".search-text").val(query_str);
            jQuery(".search-text").focus();
        }
    });
});
