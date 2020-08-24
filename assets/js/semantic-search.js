//
// Semantic Search
//

// ws-response message types
const mt_Disconnect = "disconnect";
const mt_Error = "error";
const mt_Message = "message";
const mt_Email = "email";
const mt_IsTyping = "typing";
const mt_SignIn = "sign-in";
const mt_SignOut = "sign-out";
const mt_SpellingSuggest = "spelling-suggest";

// semantic search class
class SemanticSearch extends SimSageCommon {

    constructor() {
        super();
        this.session_id = '';
        this.selected_syn_sets = {};

        this.page = 0;
        this.num_results = 0;
        this.num_pages = 0;
        this.last_query = "";
        this.advanced_filter = {};
        this.semantic_search_results = [];
        this.semantic_set = {};
        this.syn_sets_seen = {};

        this.chat_window = [];

        // operator's id if we're chatting with someone
        this.assignedOperatorId = '';
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////

    // add a chat message and then do a normal search
    do_chat(page, text, advanced_filter) {
        if (text.trim() !== '') {
            // add the user's text to the chat stream
            const dt = unix_time_convert(SimSageCommon.get_system_time());
            const user_chat = {"who": "You", "what": text, "when": dt};
            this.chat_window.push(user_chat)
            // show the updated chat window
            update_ui(this.page, this.num_pages, this.num_results, this.semantic_search_results,
                this.semantic_set, this.chat_window, false, true);
            // and do the search
            this.do_semantic_search(page, text, advanced_filter);
        }
    }

    // perform the semantic search
    do_semantic_search(page, text, advanced_filter) {
        if (this.kb && text.trim() !== '') {
            // do we need to reset the pagination?
            this.reset_pagination(page, text);
            this.advanced_filter = advanced_filter; // copy
            // create the query and clear the errors
            const url = settings.base_url + '/ops/query';
            error('');

            text = this.cleanup_query_text(text);
            const search_query_str = this.semantic_search_query_str(text, advanced_filter);
            if (search_query_str !== '()') {
                this.searching = true;  // we're searching!
                this.search_query = text;
                this.show_advanced_search = false;
                this.show_details = false;
                busy(true);
                let source_id = '';
                if (advanced_filter.source_id.length > 0) {
                    source_id = advanced_filter.source_id;
                }

                const clientQuery = {
                    'organisationId': settings.organisationId,
                    'kbList': [this.kb.id],
                    'clientId': SemanticSearch.get_client_id(),
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
                    'spellingSuggest': settings.use_spelling_suggest,
                    'contextLabel': settings.context_label,
                    'contextMatchBoost': settings.context_match_boost,
                    'sourceId': source_id,
                };

                console.log(clientQuery);

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
                        // self.receive_ws_data(data);
                    }

                }).fail(function (err) {
                    error(err);
                });

            } else {
                error("Please enter a query to start searching.");
            }
        }
    }

    // get a preview url for the currently selected knowledge base
    get_preview_url() {
        return settings.base_url + '/document/preview/' + settings.organisationId + '/' + this.kb.id + '/' +
               SemanticSearch.get_client_id();
    }

    // reset the variables used in determining pagination if the query has changed
    reset_pagination(page, query_text) {
        if (this.last_query !== query_text) {
            this.last_query = query_text;
            this.page = 0;  // reset to page 0
            this.num_pages = 0;
            this.shard_size_list = [];
        } else {
            this.page = page;
        }
        this.semantic_search_results = [];
        this.semantic_set = {};
    }

    // pagination - previous page set
    prev_page() {
        if (this.page > 0) {
            this.page -= 1;
            this.do_semantic_search(this.page, this.search_query, this.advanced_filter);
        }
    }

    // pagination - next page set
    next_page() {
        if ((this.page + 1) < this.num_pages) {
            this.page += 1;
            this.do_semantic_search(this.page, this.search_query, this.advanced_filter);
        }
    }

    // overwrite: call refresh ui
    refresh() {
    }

    // correct the spelling
    correct_spelling(text) {
        document.getElementById("txtSearch").value = text;
        this.bot_text = '';
        this.bot_buttons = [];
        this.do_semantic_search(text);
    }

    // overwrite: generic web socket receiver
    receive_ws_data(data) {
        busy(false);
        if (data) {
            if (data.messageType === mt_Error && data.error.length > 0) {
                this.searching = false;
                error(data.error);  // set an error

            } else if (data.messageType === mt_Disconnect) {
                this.searching = false;
                this.assignedOperatorId = ''; // disconnect any operator

            } else if (data.messageType === mt_Email) {
                this.knowEmail = true;

            } else if (data.messageType === mt_SignIn) {
                this.searching = false;
                if (data.errorMessage && data.errorMessage.length > 0) {
                    error(data.errorMessage);  // set an error
                    this.signed_in = false;
                } else {
                    // sign-in successful
                    this.signed_in = true;
                    sign_in_status(true);
                }
                // todo: show we have signed in

            } else if (data.messageType === mt_IsTyping) {
                this.operator_is_typing(data.fromIsTyping);

            } else if (data.messageType === mt_SpellingSuggest) {
                // speech bubble popup with actions
                this.searching = false;
                this.bot_text = "Did you mean: " + data.text;
                this.bot_buttons = [];
                this.bot_buttons.push({text: "yes", action: 'search.correct_spelling("' + data.text + '");'});
                // todo: spelling suggest

            } else if (data.messageType === mt_SignOut) {

                this.searching = false;
                if (data.errorMessage && data.errorMessage.length > 0) {
                    error(data.errorMessage);  // set an error
                } else {
                    // sign-in successful
                    this.signed_in = false;
                    sign_in_status(false);
                }
                // todo: show user signed out

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

                    const self = this;
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
                    this.num_results = data.totalDocumentCount;
                    const divided = data.totalDocumentCount / settings.page_size;
                    this.num_pages = parseInt(divided);
                    if (parseInt(divided) < divided) {
                        this.num_pages += 1;
                    }
                }

                // did we get an NLP result?
                const nlp_reply = [];
                const dt = unix_time_convert(SimSageCommon.get_system_time());
                if (data.hasResult && data.text && data.text.length > 0) {
                    const nlp_data = {"who": "Bot", "what": data.text, "when": dt};
                    const url_list = [];
                    nlp_reply.push(nlp_data);
                    this.chat_window.push(nlp_data)
                    if (data.urlList && data.urlList.length > 0) {
                        for (const url of data.urlList) {
                            if (url.trim().length > 0) {
                                for (const sub_url of url.split(' ')) {
                                    url_list.push(sub_url);
                                }
                            }
                        }
                    }
                    if (url_list.length > 0) {
                        nlp_data.what += "<br/>";
                    }
                    for (const url of url_list) {
                        nlp_data.what += render_url(url);
                    }

                } else {
                    // todo: no bot results - can we ask about a syn-set not yet seen / selected?
                    if (Array.isArray(this.context_stack)) {
                        for (const context_item of this.context_stack) {
                            const syn_set = SimSageCommon.get_synset(context_item);
                            const search_words = {};
                            const search_word_list = SimSageCommon.get_unique_words_as_list(this.search_query);
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

                } // end of else if no bot results

                // copy the know email flag from our results
                if (!this.knowEmail && data.knowEmail) {
                    this.knowEmail = data.knowEmail;
                }

                if (data.hasResult) {
                    update_ui(this.page, this.num_pages, this.num_results, this.semantic_search_results,
                              this.semantic_set, this.chat_window, false, nlp_reply.length > 0);

                } else {
                    update_ui(0, 0, 0, [], {}, [],
                        true, false);
                }

            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////
    // send an email to the server for more contact / answering a query

    send_email(emailAddress) {
        if (emailAddress && emailAddress.trim().length > 0 && emailAddress.indexOf("@") > 0) {
            error('');
            const self = this;
            const url = settings.base_url + '/ops/email';
            this.searching = false;  // we're not performing a search
            const emailMessage = {
                'messageType': mt_Email,
                'organisationId': settings.organisationId,
                'kbList': [this.kb.id],
                'clientId': SemanticSearch.get_client_id(),
                'emailAddress': emailAddress,
            };
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'data': JSON.stringify(emailMessage),
                'type': 'POST',
                'url': url,
                'dataType': 'json',
                'success': function (data) {
                    self.receive_ws_data(data);
                }

            }).fail(function (err) {
                error(err);
            });
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////

    // remove duplicate strings from body search text and add synset items
    process_body_string(text) {
        const parts = SimSageCommon.get_unique_words_as_list(text);
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
    cleanup_query_text(text) {
        // remove any : ( ) characters from text first
        text = text.replace(/\)/g, ' ');
        text = text.replace(/\(/g, ' ');
        return text.replace(/:/g, ' ');
    }

    // get a semantic search query string for all the filters etc.
    semantic_search_query_str(text, af) {
        let query = "(";
        let needsAnd = false;
        if (text.length > 0) {
            query += "body: " + this.process_body_string(text);
            needsAnd = true;
        }
        if (af.url.length > 0 && af.url[0].length > 0) {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            for (const i in af.url) {
                if (i > 0) {
                    query += " and "
                }
                query += "url: " + this.url[i];
            }
            query += ") "
        }
        if (af.title.length > 0 && af.title[0].length > 0) {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            for (const i in af.title) {
                if (i > 0) {
                    query += " and "
                }
                query += "title: " + af.title[i];
            }
            query += ") "
        }
        if (af.author.length > 0 && af.author[0].length > 0) {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            for (const i in af.author) {
                if (i > 0) {
                    query += " and "
                }
                query += "author: " + af.author[i];
            }
            query += ") "
        }
        if (af.document_type.length > 0 && af.document_type[0].length > 0) {
            if (needsAnd)
                query += " and (";
            else
                query += " (";
            needsAnd = true;
            for (const i in af.document_type) {
                if (i > 0) {
                    query += " or "
                }
                query += "type: " + af.document_type[i];
            }
            query += ") "
        }
        query += ")";
        return query;
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

}

