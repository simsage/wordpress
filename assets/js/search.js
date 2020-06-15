//
// Semantic Search helper class
//

// ws-response message types
const mt_Disconnect = "disconnect";
const mt_Error = "error";
const mt_Message = "message";
const mt_Email = "email";
const mt_IsTyping = "typing";
const mt_SpellingSuggest = "spelling-suggest";

// an empty filter
function emptyAdvancedSearchFilter() {
    return {"type": "", "title": "", "url": "", "author": ""};
}

// semantic search class
class SemanticSearch extends SimSageCommon {

    constructor(update_ui) {
        super();
        this.update_ui = update_ui;

        // the current semantic search set
        this.semantic_search_results = [];
        this.semantic_search_result_map = {};

        // pagination for semantic search
        this.page = 0;
        this.num_results = -1;
        this.num_pages = 0;

        this.prev_query = ''; // shard logic: what was asked previously
        this.shard_size_list = [];

        // bot details
        this.bot_text = '';
        this.bot_buttons = [];
        this.bubble_visible = true; // is the bot visible?

        // semantic set (categories on the side from semantic search results)
        this.semantic_set = {};
        this.context_stack = [];            // syn-set management
        this.selected_syn_sets = {};
        this.syn_sets_seen = {};

        // details page
        this.show_details = false;
        this.details_html = '';

        // selected view for semantic search
        this.view = 'lines';

        // advanced filter visibility and actions
        // needed since we decouple logic from display / controls
        this.has_advanced_selection = false;
        this.show_advanced_search = false;
        // toggle for clearing advanced search results in the UX
        this.clear_adv_search = false;
        this.advanced_search_filter = emptyAdvancedSearchFilter();

        // the user's current query
        this.search_query = '';

        // do we know this person's email address already?
        this.knowEmail = false;
        // the email address the user has been typing
        this.email = '';
    }


    // semantic search key was pressed
    search_key_press(event, text) {
        this.search_query = text;
        if (event.keyCode === 13) {
            this.search_click();
        } else {
            this.clientIsTyping();
        }
    }


    // start semantic search
    search_click() {
        this.reset_pagination();
        this.do_semantic_search(this.search_query);
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////

    // perform the semantic search
    do_semantic_search(text) {
        this.show_advanced_search = false;
        this.show_details = false;
        const url = settings.base_url + '/ops/query';
        const self = this;
        this.error = '';

        text = this.clean_query_text(text);
        const search_query_str = this.semantic_search_query_str(text);
        if (search_query_str !== '()') {
            this.searching = true;  // we're searching!
            this.show_advanced_search = false;
            this.busy = true;
            let source_id = 1;
            if (this.source && this.source.sourceId) {
                source_id = this.source.sourceId;
            }

            const clientQuery = {
                'organisationId': settings.organisationId,
                'kbList': [{'kbId': settings.kbId, 'sid': ''}], // sids not used
                'clientId': SemanticSearch.getClientId(),
                'semanticSearch': true,     // always a search
                'query': search_query_str,  // search query
                'queryText': text,          // raw text
                'numResults': 1,              // bot results
                'scoreThreshold': settings.bot_threshold,
                'page': this.page,
                'pageSize': settings.page_size,
                'shardSizeList': this.shard_size_list,
                'fragmentCount': settings.fragment_count,
                'maxWordDistance': settings.max_word_distance,
                'searchThreshold': settings.score_threshold,
                'spellingSuggest': settings.use_spelling_suggest,
                'sourceId': source_id,
            };

            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'data': JSON.stringify(clientQuery),
                'type': 'POST',
                'url': url,
                'dataType': 'json',
                'success': function (data) {
                    self.receive_ws_data(data);
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

            // clear the query box?
            if (text === this.search_query && this.assignedOperatorId.length > 0) {
                this.search_query = '';
            }

            this.refresh();

        } else {
            this.error = "Please enter a query to start searching.";
            this.refresh();
        }
    }

    // overwrite: call refresh ui
    refresh() {
        if (this.update_ui) {
            this.update_ui(this);
        }
    }

    // correct the spelling
    correct_spelling(text) {
        this.search_query = text;
        this.bot_text = '';
        this.bot_buttons = [];
        this.do_semantic_search(text);
    }

    close_bot_window() {
        this.bot_text = '';
        this.bot_buttons = [];
        this.refresh();
    }

    visit(url) {
        if (url && url.length > 0) {
            window.open(url, '_blank');
        }
    }

    // overwrite: generic web socket receiver
    receive_ws_data(data) {
        this.busy = false;
        if (data) {
            if (data.messageType === mt_Error && data.error.length > 0) {
                this.searching = false;
                this.error = data.error;  // set an error
                this.refresh();

            } else if (data.messageType === mt_Disconnect) {
                this.searching = false;
                this.assignedOperatorId = ''; // disconnect any operator
                this.refresh();

            } else if (data.messageType === mt_IsTyping) {
                this.isTyping(data.fromIsTyping);

            } else if (data.messageType === mt_SpellingSuggest) {
                // speech bubble popup with actions
                this.searching = false;
                this.bot_text = "Did you mean: " + data.text;
                this.bot_buttons = [];
                this.bot_buttons.push({text: "yes", action: 'search.correct_spelling("' + data.text + '");'});
                this.bot_buttons.push({text: "no", action: 'search.close_bot_window();'});
                this.refresh();


            } else if (data.messageType === mt_Message) {

                const self = this;
                this.bot_text = '';
                this.is_typing = false;
                this.semantic_search_results = [];
                this.semantic_search_result_map = {};
                this.semantic_set = {};
                this.context_stack = []; // includes syn_sets of selected_syn_sets
                this.bot_buttons = [];
                // set the assigned operator
                this.assignedOperatorId = data.assignedOperatorId;
                if (this.assignedOperatorId == null) { // compatibility with older versions of SimSage
                    this.assignedOperatorId = '';
                }

                // did we get semantic search results?
                if (data.resultList) {

                    this.shard_size_list = data.shardSizeList;
                    this.semantic_set = data.semanticSet;
                    this.context_stack = data.contextStack;
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
                        }

                    });
                    this.busy = false;
                    this.num_results = data.totalDocumentCount;
                    const divided = data.totalDocumentCount / settings.page_size;
                    self.num_pages = parseInt(divided);
                    if (parseInt(divided) < divided) {
                        self.num_pages += 1;
                    }
                }

                // bot result?
                if (data.hasResult && data.text && data.text.length > 0) {
                    this.bot_text = data.text;
                    if (data.urlList && data.urlList.length > 0) {
                        for (const url of data.urlList) {
                            if (url.trim().length > 0) {
                                for (const sub_url of url.split(' ')) {
                                    const url_name = SimSageCommon.get_url_name(sub_url);
                                    this.bot_buttons.push({
                                        text: url_name,
                                        action: 'search.visit("' + sub_url + '");'
                                    });
                                }
                            }
                        }
                    }
                } else {
                    // no bot results - can we ask about a syn-set not yet seen / selected?
                    if (Array.isArray(this.context_stack)) {
                        for (const context_item of this.context_stack) {
                            const syn_set = SimSageCommon.getSynSet(context_item);
                            const search_words = {};
                            const search_word_list = SimSageCommon.getUniqueWordsAsList(this.search_query);
                            for (const search_word of search_word_list) {
                                search_words[search_word.toLowerCase()] = 1;
                            }
                            if (syn_set) {
                                const word = syn_set["word"];
                                const clouds = syn_set["clouds"];
                                if (!this.syn_sets_seen[word] && clouds.length > 1 && search_words[word]) {
                                    // add a question for the bot
                                    this.bot_text = "What type of <b>" + word + "</b> are you looking for?";
                                    this.bot_buttons = [];
                                    for (const i in clouds) {
                                        if (clouds.hasOwnProperty(i)) {
                                            this.bot_buttons.push({
                                                text: clouds[i],
                                                action: 'search.select_syn_set("' + word + '", ' + i + ');'
                                            });
                                        }
                                    }
                                    this.bot_buttons.push({
                                        text: "all",
                                        action: 'search.select_syn_set("' + word + '", -1);'
                                    });
                                    break;
                                }
                            }
                        }
                    }
                }

                // copy the know email flag from our results
                if (!this.knowEmail && data.knowEmail) {
                    this.knowEmail = data.knowEmail;
                }

                this.refresh();
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////

    // switch to text view in search ui
    text_view() {
        this.view = 'lines';
        this.refresh();
    }

    // switch to image view in search ui
    image_view() {
        this.view = 'images';
        this.refresh();
    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////
    // no results render system and its email handler

    no_results() {
        return render_no_results(settings.ask_email, this.knowEmail);
    }

    send_email() {
        if (this.email && this.email.length > 0 && this.email.indexOf("@") > 0) {
            this.searching = false;  // we're not performing a search
            this.stompClient.send("/ws/ops/email", {},
                JSON.stringify({
                    'messageType': mt_Email,
                    'organisationId': settings.organisationId,
                    'kbList': [{'kbId': settings.kbId, 'sid': ''}], // sids not used
                    'clientId': SemanticSearch.getClientId(),
                    'emailAddress': this.email,
                }));
            this.error = '';
            this.knowEmail = true;
            this.refresh();
        }
    }

    // key handling for the email popup control inside the bot window
    email_keypress(event, text) {
        this.email = text;
        if (event && event.keyCode === 13) {
            this.send_email();
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////
    // bot display

    // hide the bot response
    hide_speech_bubble() {
        this.searching = false;  // we're not performing a search
        this.bubble_visible = false;
        this.refresh();
    }


    // visit the previous text snippet of a single search result
    doInnerPrev(url) {
        const sr = this.semantic_search_result_map[url];
        if (sr && sr.index > 0) {
            sr.index -= 1;
            if (sr.index === 0)
                sr.message = '\n' + sr.response;
            else
                sr.message = '\n' + sr.textList[sr.index - 1];
            this.refresh();
        }
    }

    // visit the next text snippet of a single search result
    doInnerNext(url) {
        const sr = this.semantic_search_result_map[url];
        if (sr && sr.index + 1 < sr.num_results) {
            sr.index += 1;
            sr.message = '\n' + sr.textList[sr.index - 1];
            this.refresh();
        }
    }

    // pagination - previous page set
    prevPage() {
        if (this.page > 0) {
            this.page -= 1;
            this.do_semantic_search(this.search_query);
        }
    }

    // pagination - next page set
    nextPage() {
        if ((this.page + 1) < this.num_pages) {
            this.page += 1;
            this.do_semantic_search(this.search_query);
        }
    }

    // reset the variables used in determining pagination if the query has changed
    reset_pagination() {
        if (this.search_query !== this.prev_query) {
            this.prev_query = this.search_query;
            this.page = 0;  // reset to page 0
            this.shard_size_list = [];
        }
        this.bot_text = '';
        this.bubble_visible = true;
        this.semantic_search_results = [];
    }

    // remove duplicate strings from body search text and add synset items
    process_body_string(text) {
        const parts = SimSageCommon.getUniqueWordsAsList(text);
        const newList = [];
        for (const _part of parts) {
            const part = _part.trim().toLowerCase();
            const synSet = this.selected_syn_sets[part];
            if (typeof synSet !== 'undefined' && parseInt(synSet) >= 0) {
                newList.push(_part.trim() + '/' + synSet);
            } else {
                newList.push(_part.trim());
            }
        }
        return newList.join(" ");
    }

    // clean text - remove characters we use for special purposes
    clean_query_text(text) {
        // remove any : ( ) characters from text first
        text = text.replace(/\)/g, ' ');
        text = text.replace(/\(/g, ' ');
        return text.replace(/:/g, ' ');
    }

    // get a semantic search query string for all the filters etc.
    semantic_search_query_str(text) {
        let query = "(";
        let needsAnd = false;
        if (text.length > 0) {
            query += "body: " + this.process_body_string(text);
            needsAnd = true;
        }
        if (this.advanced_search_filter['url'].length > 0) {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            query += "url: " + this.advanced_search_filter['url'];
            query += ") "
        }
        if (this.advanced_search_filter['title'].length > 0) {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            query += "title: " + this.advanced_search_filter['title'];
            query += ") "
        }
        if (this.advanced_search_filter['author'].length > 0) {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            query += "author: " + this.advanced_search_filter['author'];
            query += ") "
        }
        if (this.advanced_search_filter['type'].length > 0 && this.advanced_search_filter['type'][0] !== '') {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            const fileType = this.advanced_search_filter['type'];
            for (const i in fileType) {
                if (i > 0) {
                    query += " or "
                }
                query += "type: " + fileType[i];
            }
            query += ") "
        }
        query += ")";
        return query;
    }

    /////////////////////////////////////////////////////////////////////////////////////////

    // select a semantic category item and modify the query string accordingly
    select_semantic(semantic_item) {
        let text_list = this.search_query.split(' ');
        let found = false;
        for (const item of text_list) {
            if (item.toLowerCase() === semantic_item.toLowerCase()) {
                found = true;
            }
        }
        // reconstruct the input
        const new_text_list = [];
        if (found) {
            // remove it
            for (const item of text_list) {
                if (item.toLowerCase() !== semantic_item.toLowerCase()) {
                    new_text_list.push(item);
                }
            }
        } else {
            // copy old list and add new item
            for (const item of text_list) {
                new_text_list.push(item);
            }
            new_text_list.push(semantic_item);
        }
        this.search_query = SemanticSearch.join(new_text_list);
        this.refresh();
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    // syn-set context management

    // select a syn-set
    select_syn_set(word, value) {
        this.selected_syn_sets[word.toLowerCase().trim()] = value;
        this.syn_sets_seen[word.toLowerCase().trim()] = 1; // mark it as 'seen' and done
        this.bot_text = "";
        this.bot_buttons = [];
        this.do_semantic_search(this.search_query);
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    // advanced search filter get and controllers

    // get the html for the search results neatly drawn out for the UI
    get_semantic_search_html() {
        const num_results = this.semantic_search_results.length;
        if (num_results > 0) {
            const organisation_id = settings.organisationId;
            const kb_id = settings.kbId;
            let list = this.semantic_search_results.slice(); // copy
            let html_list = [];
            if (this.view === 'lines') {
                html_list.push(render_rhs_containers(this.context_stack, this.selected_syn_sets, this.semantic_set));
                const base_url = settings.base_url;
                list.map(function (h) {
                    const text = SemanticSearch.highlight(h.textList[h.index]);
                    html_list.push(render_result(organisation_id, kb_id, h.index, h.title, h.author, h.url, h.urlId,
                                              h.num_results, text, base_url));
                });

            } else if (this.view === 'images') {
                html_list.push(render_rhs_containers(this.context_stack, this.selected_syn_sets, this.semantic_set));
                const base_url = settings.base_url;
                list.map(function (h) {
                    const text = SemanticSearch.highlight(h.textList[h.index]);
                    html_list.push(render_result_images(organisation_id, kb_id, h.index, h.title, h.author, h.url, h.urlId,
                                                     h.num_results, text, base_url));
                });
            }
            html_list.push(render_pagination(this.page, this.num_pages, this.busy, this.num_results));
            html_list.push('</div>');
            return html_list.join('\n');
        }
        return "";
    }

    // do we have any results to display?
    get_has_results() {
        const num_results = this.semantic_search_results.length;
        return (num_results > 0 || this.bot_text.length > 0);
    }

    // show advanced filter menu
    toggle_advanced_search() {
        this.show_advanced_search = !this.show_advanced_search;
        this.show_details = false;
        this.refresh();
    }

    // hide advanced filter menu
    hide_advanced_search() {
        this.show_advanced_search = false;
        this.show_details = false;
        this.refresh();
    }

    // simple clear toggle
    clear_advanced_search() {
        this.advanced_search_filter = emptyAdvancedSearchFilter();
        this.has_advanced_selection = false;
        this.clear_adv_search = true;
        this.refresh();
    }

    // update the advanced filter
    update_advanced_search(data) {
        if (data["title"]) data["title"] = data["title"].join('');
        if (data["url"]) data["url"] = data["url"].join('');
        if (data["author"]) data["author"] = data["author"].join('');
        this.advanced_search_filter = {...this.advanced_search_filter, ...data};
        // check settings
        this.has_advanced_selection = false;
        const avd = this.advanced_search_filter;
        if ((avd['type'].length > 0 && avd['type'][0] !== '') || avd['url'].length > 0 || avd['author'].length > 0 || avd['title'].length > 0) {
            this.has_advanced_selection = true;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    // details dialog box

    // show the details page on screen
    showDetails(url_id, url) {
        const num_results = this.semantic_search_results.length;
        const organisation_id = settings.organisationId;
        const kb_id = settings.kbId;
        const base_url = settings.base_url;
        const document = this.semantic_search_result_map[url];
        const text = SemanticSearch.highlight(document.textList[document.index]);
        this.details_html = render_details(base_url, organisation_id, kb_id, url_id, document, text, this.search_query);
        this.show_details = true;
        this.show_advanced_search = false;
        this.refresh();
    }

    // close the details page on screen
    closeDetails() {
        this.details_html = '';
        this.show_details = false;
        this.show_advanced_search = false;
        this.refresh();
    }

}
