//
// SimSage Analytics class
//


class SimsageData {

    constructor(an_update_ui) {
        this.an_update_ui = an_update_ui;
        this.response = '';
        this.error = '';
        this.busy = false;

        // date selected by ui
        this.date = new Date();

        // active tab
        this.tab = 'keywords';
        this.tab_list = ['keywords', 'searches', 'logs', 'qna', 'synonyms', 'semantics'];

        // the stats
        this.search_frequencies = [];
        this.search_keyword_frequencies = [];

        // mind-items
        this.mind_item_filter = '';
        this.mind_item_prev_filter = '';
        this.mind_item_prev_id = '';
        this.mind_item_page_size = 10;
        this.mind_item_list = [];
        this.mind_item_page = 0;
        this.num_mind_items = 0;
        this.mind_item_nav = ['null'];

        // mind-item edit dialog
        this.mind_item_dlg_show = false;
        this.mind_item_dlg_action = "";
        this.mi_dlg_id = null;
        this.mi_dlg_q1 = "";
        this.mi_dlg_q2 = "";
        this.mi_dlg_answer = "";
        this.mi_dlg_links = "";

        // synonyms
        this.synonym_filter = '';
        this.synonym_prev_filter = '';
        this.synonym_prev_id = '';
        this.synonym_page_size = 10;
        this.synonym_list = [];
        this.synonym_page = 0;
        this.num_synonyms = 0;
        this.synonym_nav = ['null'];

        // synonym edit dialog
        this.synonym_dlg_show = false;
        this.synonym_dlg_action = "";
        this.syn_dlg_id = null;
        this.syn_dlg_words = "";

        // semantics
        this.semantic_filter = '';
        this.semantic_prev_filter = '';
        this.semantic_prev_id = '';
        this.semantic_page_size = 10;
        this.semantic_list = [];
        this.semantic_page = 0;
        this.num_semantics = 0;
        this.semantic_nav = ['null'];

        // semantic edit dialog
        this.semantic_dlg_show = false;
        this.semantic_dlg_action = "";
        this.sem_dlg_id = null;
        this.sem_dlg_word = "";
        this.sem_dlg_semantic = "";

        // file upload control
        this.filename = '';
        this.file_type = '';
        this.file_binary_data = null;
    }

    refresh() {
        if (this.an_update_ui) {
            this.an_update_ui(this);
        }
    }

    select_tab(tab) {
        this.tab = tab;
        if (tab === 'qna') {
            this.getMindItems();
        } else if (tab === 'synonyms') {
            this.getSynonyms();
        } else if (tab === 'semantics') {
            this.getSemantics();
        }
        this.refresh();
    }

    set_date(date) {
        this.date = date;
    }

    // close the error dialog - remove any error settings
    close_error() {
        this.error = '';
        this.busy = false;
    }

    // fetch the current set of analytics
    getAnalytics() {
        const self = this;
        this.error = '';
        this.busy = true;

        const year = this.date.getFullYear();
        const month = this.date.getMonth() + 1;
        const url = settings.base_url + '/stats/wp-stats/' + settings.organisationId + '/' +
                    settings.kbId + '/' + settings.sid + '/' + year + '/' + month + '/10';
        jQuery.ajax({
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            'type': 'GET',
            'url': url,
            'success': function (data) {
                self.search_frequencies = self.convert_months(data.accessFrequency);
                self.busy = false;
                const kw_list = self.convert_dictionary(data.queryWordFrequency);
                kw_list.sort(function(first, second) {
                    return second.value - first.value;
                });
                self.search_keyword_frequencies = kw_list;
                self.refresh();
            }

        }).fail(function (err) {
            self.busy = false;
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


    /**
     * convert a list of 31 integers to label with frequency data
     * @param data the data to process
     */
    convert_months(data) {
        if (data && data.length === 31) {
            const data_list = [];
            for (let i = 0; i < 31; i++) {
                const day = i + 1;
                data_list.push({label: "" + day, value: data[i]});
            }
            return data_list;
        } else {
            const data_list = [];
            for (let i = 0; i < 31; i++) {
                const day = i + 1;
                data_list.push({label: "" + day, value: 0});
            }
            return data_list;
        }
    }


    /**
     * convert a list of 31 integers to label with frequency data
     * @param data the data to process
     * @param include_zero include zero (0) values
     */
    convert_dictionary(data, include_zero = false) {
        if (data) {
            const data_list = [];
            for (let key in data) {
                // check if the property/key is defined in the object itself, not in parent
                if (data.hasOwnProperty(key)) {
                    const value = data[key];
                    if (include_zero || value !== 0) {
                        const label = key.replace("num ", "");
                        data_list.push({label: label, value: value});
                    }
                }
            }
            for (let i = 0; i < 31; i++) {
                const day = i + 1;
            }
            return data_list;
        }
        return [];
    }


    // download all conversations had between operators and clients for review
    dlOperatorConversations() {
        const self = this;
        this.error = '';
        this.busy = true;
        const url = settings.base_url + '/wp-operator-chats';
        const data = {organisationId: settings.organisationId, kbId: settings.kbId, sid: settings.sid,
                      year: this.date.getFullYear(), month: this.date.getMonth() + 1};
        const init = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            body: JSON.stringify(data),
            mode: 'cors',
            cache: 'default'
        };
        fetch(new Request(url), init)
            .then(function(response) {
                self.busy = false;
                self.refresh();
                if (!response.ok) {
                    self.error = `HTTP error! status: ${response.status}`;
                } else {
                    return response.blob().then((b) => {
                        let a = document.createElement("a");
                        a.href = URL.createObjectURL(b);
                        const filename = "operator-conversations-" + SimsageData.getFormattedTime() + ".xlsx";
                        a.setAttribute("download", filename);
                        a.click();
                    });
                }
            });
    }

    dlQueryLog() {
        const self = this;
        this.error = '';
        this.busy = true;
        const url = settings.base_url + '/stats/wp-query-logs';
        const data = {organisationId: settings.organisationId, kbId: settings.kbId, sid: settings.sid,
                      year: this.date.getFullYear(), month: this.date.getMonth() + 1};
        const init = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            body: JSON.stringify(data),
            mode: 'cors',
            cache: 'default'
        };
        fetch(new Request(url), init)
            .then(function(response) {
                self.busy = false;
                self.refresh();
                if (!response.ok) {
                    self.error = `HTTP error! status: ${response.status}`;
                } else {
                    return response.blob().then((b) => {
                        let a = document.createElement("a");
                        a.href = URL.createObjectURL(b);
                        const filename = "query-logs-" + SimsageData.getFormattedTime() + ".xlsx";
                        a.setAttribute("download", filename);
                        a.click();
                    });
                }
            });
    }

    // invoke the SimSage mind-dump endpoint, to create a spreadsheet with language customizations
    dlLanguageCustomizations() {
        const self = this;
        this.error = '';
        this.busy = true;
        const url = settings.base_url + '/backup/wp-mind-dump';
        const data = {organisationId: settings.organisationId, kbId: settings.kbId, sid: settings.sid};
        const init = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            body: JSON.stringify(data),
            mode: 'cors',
            cache: 'default'
        };
        this.refresh();
        fetch(new Request(url), init)
            .then(function(response) {
                self.busy = false;
                self.refresh();
                if (!response.ok) {
                    self.error = `HTTP error! status: ${response.status}`;
                } else {
                    return response.blob().then((b) => {
                        let a = document.createElement("a");
                        a.href = URL.createObjectURL(b);
                        const filename = "language-customizations-" + SimsageData.getFormattedTime() + ".xlsx";
                        a.setAttribute("download", filename);
                        a.click();
                    });
                }
            });
    }

    dlContentAnalysis() {
        const self = this;
        this.error = '';
        this.busy = true;
        const url = settings.base_url + '/document/wp-inventorize';
        const data = {organisationId: settings.organisationId, kbId: settings.kbId, sid: settings.sid};
        const init = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            body: JSON.stringify(data),
            mode: 'cors',
            cache: 'default'
        };
        fetch(new Request(url), init)
            .then(function(response) {
                self.busy = false;
                self.refresh();
                if (!response.ok) {
                    self.error = `HTTP error! status: ${response.status}`;
                } else {
                    return response.blob().then((b) => {
                        let a = document.createElement("a");
                        a.href = URL.createObjectURL(b);
                        const filename = "content-analysis-" + SimsageData.getFormattedTime() + ".xlsx";
                        a.setAttribute("download", filename);
                        a.click();
                    });
                }
            });
    }


    /**
     * Draw a graph using d3 and input_data
     *
     * @param control_id the absolute jquery '#id' of the control to draw the information into
     * @param title the tile of the graph
     * @param x_axis_label x-axis label
     * @param y_axis_label y-axis label
     * @param date_str the date of generation to display as part of the graph
     * @param input_data \{label: "str", value: float}
     */
    draw_graph(control_id, title, x_axis_label, y_axis_label, date_str, input_data) {
        const svg = d3.select(control_id);
        const svgContainer = d3.select('#container');

        let max_y = 0;
        for (const item of input_data) {
            if (item.value > max_y) {
                max_y = item.value;
            }
        }

        // round scale to nearest power of 10
        if (max_y < 10) max_y = 10;
        else if (max_y < 100) max_y = 100;
        else if (max_y < 1000) max_y = 1000;
        else if (max_y < 10000) max_y = 10000;
        else if (max_y < 100000) max_y = 100000;
        else if (max_y < 1000000) max_y = 1000000;

        const margin = 80;
        const width = 1000 - 2 * margin;
        const height = 600 - 2 * margin;

        const chart = svg.append('g')
            .attr('transform', `translate(${margin}, ${margin})`);

        const xScale = d3.scaleBand()
            .range([0, width])
            .domain(input_data.map((s) => s.label))
            .padding(0.4)

        const yScale = d3.scaleLinear()
            .range([height, 0])
            .domain([0, max_y]);

        // vertical grid lines
        const makeXLines = () => d3.axisBottom()
            .scale(xScale)

        const makeYLines = () => d3.axisLeft()
            .scale(yScale)

        chart.append('g')
            .attr('transform', `translate(0, ${height})`)
            .call(d3.axisBottom(xScale));

        chart.append('g')
            .call(d3.axisLeft(yScale));

        // vertical grid lines
        chart.append('g')
            .attr('class', 'grid')
            .attr('transform', `translate(0, ${height})`)
            .call(makeXLines()
                .tickSize(-height, 0, 0)
                .tickFormat('')
            )

        chart.append('g')
            .attr('class', 'grid')
            .call(makeYLines()
                .tickSize(-width, 0, 0)
                .tickFormat('')
            )

        const barGroups = chart.selectAll()
            .data(input_data)
            .enter()
            .append('g')

        barGroups
            .append('rect')
            .attr('class', 'bar')
            .attr('x', (g) => xScale(g.label))
            .attr('y', (g) => yScale(g.value))
            .attr('height', (g) => height - yScale(g.value))
            .attr('width', xScale.bandwidth())
            .on('mouseenter', function (actual, i) {
                d3.selectAll('.value')
                    .attr('opacity', 0)

                d3.select(this)
                    .transition()
                    .duration(300)
                    .attr('opacity', 0.6)
                    .attr('x', (a) => xScale(a.label) - 5)
                    .attr('width', xScale.bandwidth() + 10)

                const y = yScale(actual.value)

                chart.append('line')
                    .attr('id', 'limit')
                    .attr('x1', 0)
                    .attr('y1', y)
                    .attr('x2', width)
                    .attr('y2', y)

                barGroups.append('text')
                    .attr('class', 'divergence')
                    .attr('x', (a) => xScale(a.label) + xScale.bandwidth() / 2)
                    .attr('y', (a) => yScale(a.value) + 30)
                    .attr('fill', 'white')
                    .attr('text-anchor', 'middle')
                    .text((a, idx) => {
                        const divergence = (a.value - actual.value).toFixed(1)

                        let text = ''
                        if (divergence > 0) text += '+'
                        text += `${divergence}%`

                        return idx !== i ? text : '';
                    })

            })
            .on('mouseleave', function () {
                d3.selectAll('.value')
                    .attr('opacity', 1)

                d3.select(this)
                    .transition()
                    .duration(300)
                    .attr('opacity', 1)
                    .attr('x', (a) => xScale(a.label))
                    .attr('width', xScale.bandwidth())

                chart.selectAll('#limit').remove()
                chart.selectAll('.divergence').remove()
            })

        barGroups
            .append('text')
            .attr('class', 'value')
            .attr('x', (a) => xScale(a.label) + xScale.bandwidth() / 2)
            .attr('y', (a) => yScale(a.value) + 20)
            .attr('text-anchor', 'middle')
            .text((a) => ((max_y > 10 && a.value < 10.0) || a.value === 0) ? '' : a.value)

        svg
            .append('text')
            .attr('class', 'label')
            .attr('x', -(height / 2) - margin)
            .attr('y', margin / 2.4)
            .attr('transform', 'rotate(-90)')
            .attr('text-anchor', 'middle')
            .text(y_axis_label)

        svg.append('text')
            .attr('class', 'label')
            .attr('x', width / 2 + margin)
            .attr('y', height + margin * 1.7)
            .attr('text-anchor', 'middle')
            .text(x_axis_label)

        svg.append('text')
            .attr('class', 'title')
            .attr('x', width / 2 + margin)
            .attr('y', 40)
            .attr('text-anchor', 'middle')
            .text(title)

        if (date_str !== '') {
            svg.append('text')
                .attr('class', 'source')
                .attr('x', width - margin / 2)
                .attr('y', height + margin * 1.7)
                .attr('text-anchor', 'start')
                .text('Source: SimSage, ' + date_str)
        }
    }


    // helper - get pretty date for now
    static getFormattedTime() {
        const today = new Date();
        const y = today.getFullYear();
        // JavaScript months are 0-based.
        const m = today.getMonth() + 1;
        const d = today.getDate();
        const h = today.getHours();
        const mi = today.getMinutes();
        const s = today.getSeconds();
        return y + "-" + m + "-" + d + "-" + h + "-" + mi + "-" + s;
    }


    /////////////////////////////////////////////////////////////////////////////////
    // mind items

    // filter text-box enter press check
    handleMindItemKey(key) {
        if (key === 13) {
            this.getMindItems();
        }
    }

    // filter text-box change text
    setMindItemFilter(text) {
        this.mind_item_filter = text;
    }

    // do search with filter
    getMindItems() {
        if (this.mind_item_prev_filter !== this.mind_item_filter) {
            this.mind_item_prev_filter = this.mind_item_filter;
            this.mindItemResetPagination();
        }
        this.busy = true;
        const self = this;
        this.refresh();
        const data = {
            "organisationId": settings.organisationId, "kbId": settings.kbId, "sid": settings.sid,
            "prevId": this.mind_item_prev_id ? this.mind_item_prev_id : "null",
            "filter": this.mind_item_filter, "pageSize": this.mind_item_page_size
        };
        const url = settings.base_url + '/bot/wp-mind-items';
        jQuery.ajax({
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            'data': JSON.stringify(data),
            'type': 'PUT',
            'url': url,
            'success': function (data) {
                self.busy = false;
                if (data && data.mindItemList) {
                    self.mind_item_list = data.mindItemList;
                    self.num_mind_items = data.numMindItems;
                } else {
                    self.num_mind_items = 0;
                    self.mind_item_list = [];
                }
                self.refresh();
            }

        }).fail(function (err) {
            self.busy = false;
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

    // upload onchange event handling
    handleUploadChange(e) {
        e.preventDefault();

        const self = this;
        const reader = new FileReader();
        const file = e.target.files[0];
        const filename = file['name'];
        const file_type = file['type'];

        reader.onloadend = () => {
            self.filename = filename;
            self.file_type =  file_type;
            self.file_binary_data = reader.result;
            self.refresh();
        };
        reader.readAsDataURL(file)
    }

    uploadMindItems() {
        const self = this;
        if (this.file_binary_data) {
            this.busy = true;
            this.refresh();
            const payload = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                fileType: this.file_type,
                base64Text: this.file_binary_data,
            };
            const url = settings.base_url + '/knowledgebase/wp-upload';
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'data': JSON.stringify(payload),
                'type': 'PUT',
                'url': url,
                'success': function (data) {
                    self.busy = false;
                    self.refresh();
                    alert("upload successful");
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

    deleteAllMindItems() {
        if (confirm("are you sure you want to remove all mind-items?")) {
            const self = this;
            const payload = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
            };
            const url = settings.base_url + '/bot/wp-delete-all';
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'data': JSON.stringify(payload),
                'type': 'DELETE',
                'url': url,
                'success': function (data) {
                    self.busy = false;
                    self.num_mind_items = 0;
                    self.mind_item_list = [];
                    self.mindItemResetPagination();
                    self.refresh();
                    alert("delete all successful");
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

    mindItemDialogClose() {
        this.mind_item_dlg_show = false;
        this.refresh();
    }

    // convert text answers and links back into an actionList
    toActionList(answer, links) {
        const action_list = [];
        action_list.push({"action": "browser.write", "parameters": [answer]})
        for (const l of links.split("\n")) {
            if (l.trim().length > 0) {
                const l_lwr = l.toLowerCase();
                let is_image = false;
                for (const extn of settings.image_types) {
                    if (l_lwr.indexOf(extn) > 0) {
                        is_image = true;
                    }
                }
                if (is_image) {
                    action_list.push({"action": "browser.image", parameters: [l]});
                } else {
                    action_list.push({"action": "browser.link", parameters: [l]});
                }
            }
        }
        return action_list;
    }

    mindItemDialogSave() {
        // check the parameters are ok
        const self = this;
        const q1 = jQuery(".mi-q1").val().trim();
        const q2 = jQuery(".mi-q2").val().trim();
        const answer = jQuery(".mi-answer").val().trim();
        const links = jQuery(".mi-links").val().trim();
        const expression = q2.length > 0 ? q1 + " || " + q2 : q1;
        if (q1.length === 0 || answer.length === 0) {
            this.busy = false;
            this.error = "you must at least provide one question with one answer";
            this.refresh();
        } else {
            this.busy = true;
            this.refresh();
            const payload = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                mindItem: {
                    id: this.mi_dlg_id,
                    expression: expression,
                    preContext: '',
                    postContext: '',
                    metadata: '',
                    actionList: this.toActionList(answer, links)
                }
            }
            const url = settings.base_url + '/bot/wp-save';
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'data': JSON.stringify(payload),
                'type': 'PUT',
                'url': url,
                'success': function (data) {
                    self.busy = false;
                    self.mind_item_dlg_show = false;
                    self.refresh();
                    self.getMindItems();
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

    addMindItem() {
        this.mind_item_dlg_show = true;
        this.mind_item_dlg_action = "add mind-item"
        this.mi_dlg_id = null;
        this.mi_dlg_q1 = "";
        this.mi_dlg_q2 = "";
        this.mi_dlg_answer = "";
        this.mi_dlg_links = "";
        this.refresh();
    }

    // find a mind item in the list
    getMindItem(id) {
        for (const item of this.mind_item_list) {
            if (item.id == id) {
                return item;
            }
        }
        return null;
    }

    getAnswer(mi) {
        let answer = "";
        if (mi && mi.actionList) {
            for (const action of mi.actionList) {
                if (action.action === 'browser.write') {
                    for (const p of action.parameters) {
                        if (answer.length > 0) {
                            answer += "\n";
                        }
                        answer += p;
                    }
                }
            }
        }
        return answer;
    }

    getLinks(mi) {
        let urls = "";
        if (mi && mi.actionList) {
            for (const action of mi.actionList) {
                if (action.action === 'browser.url' || action.action === 'browser.image') {
                    for (const p of action.parameters) {
                        if (urls.length > 0) {
                            urls += "\n";
                        }
                        urls += p;
                    }
                }
            }
        }
        return urls;
    }

    editMindItem(id) {
        const mi = this.getMindItem(id);
        if (mi) {
            this.mind_item_dlg_show = true;
            this.mi_dlg_id = mi.id;
            this.mind_item_dlg_action = "edit mind-item"
            let q1 = mi.expression;
            let q2 = "";
            if (q1.indexOf("||") >= 0) {
                q2 = q1.split("||")[1];
                q1 = q1.split("||")[0];
            }
            this.mi_dlg_q1 = q1;
            this.mi_dlg_q2 = q2;
            this.mi_dlg_answer = this.getAnswer(mi);
            this.mi_dlg_links = this.getLinks(mi);
            this.refresh();
        }
    }

    deleteMindItem(id) {
        const mi = this.getMindItem(id);
        if (mi) {
            let q1 = mi.expression;
            if (q1.indexOf("||") >= 0) {
                q1 = q1.split("||")[0];
            }
            if (confirm("are you sure you want to delete mind-item id " + mi.id + ",\n\"" + q1 + "\"?")) {
            }
        }
    }

    mindItemResetPagination() {
        this.mind_item_prev_id = '';
        this.mind_item_page = 0;
        this.mind_item_nav = ['null'];
    }

    mindItemPrevPage() {
        if (this.mind_item_page > 0) {
            this.mind_item_page -= 1;
            this.mind_item_prev_id = 'null';
            if (this.mind_item_page < this.mind_item_nav.length) {
                this.mind_item_prev_id = this.mind_item_nav[this.mind_item_page];
            }
            this.getMindItems();
        }
    }

    mindItemNextPage() {
        const num_pages = Math.floor(this.num_mind_items / this.mind_item_page_size) + 1;
        if (this.mind_item_page < num_pages) {
            let id = 'null';
            if (this.mind_item_list.length > 0) {
                id = this.mind_item_list[this.mind_item_list.length - 1].id;
                this.mind_item_nav.push(id);
            }
            this.mind_item_page += 1;
            this.mind_item_prev_id = id;
            this.getMindItems();
        }
    }

    renderMindItemTable() {
        if (this.mind_item_list) {
            const str_list = [];
            for (const item of this.mind_item_list) {
                const id = item.id;
                const expr = item.expression;
                str_list.push("<tr>");
                str_list.push("<td>" + id + "</td>");
                str_list.push("<td>" + expr + "</td>");
                str_list.push("<td>");
                str_list.push("<span title='edit this mind-item' onclick='data.editMindItem(" + id + ")' class='ss-button'>");
                str_list.push("<img src='" + image_base + "/images/edit.svg' class='edit-button-image ss-button' alt='edit' /></span>");
                str_list.push("<span title='delete this mind-item' onclick='data.deleteMindItem(" + id + ")' class='ss-button'>");
                str_list.push("<img src='" + image_base + "/images/delete.svg' class='delete-button-image ss-button' alt='delete' /></span>");
                str_list.push("</td>");
                str_list.push("</tr>");
            }
            return str_list.join("\n");
        }
        return "";
    }

    renderMindItemPagination() {
        const num_pages = Math.floor(this.num_mind_items / this.mind_item_page_size) + 1;
        const page = this.mind_item_page + 1;
        const str_list = [];
        str_list.push("<button onclick='data.mindItemPrevPage()' title='go to the previous page'" + ((page>1 && !this.busy) ? "" : "disabled") + ">prev</button>");
        str_list.push("<span>page " + page + " of " + num_pages + "</span>");
        str_list.push("<button onclick='data.mindItemNextPage()' title='go to the next page'" + ((page < num_pages && !this.busy) ? "" : "disabled") + ">next</button>");
        return str_list.join("\n");
    }



    /////////////////////////////////////////////////////////////////////////////////
    // synonyms

    // filter text-box enter press check
    handleSynonymKey(key) {
        if (key === 13) {
            this.getSynonyms();
        }
    }

    // filter text-box change text
    setSynonymFilter(text) {
        this.synonym_filter = text;
    }

    // do search with filter
    getSynonyms() {
        if (this.synonym_prev_filter !== this.synonym_filter) {
            this.synonym_prev_filter = this.synonym_filter;
            this.synonymResetPagination();
        }
        this.busy = true;
        const self = this;
        this.refresh();
        const data = {
            "organisationId": settings.organisationId, "kbId": settings.kbId, "sid": settings.sid,
            "prevId": this.synonym_prev_id ? this.synonym_prev_id : "null",
            "filter": this.synonym_filter, "pageSize": this.synonym_page_size
        };
        const url = settings.base_url + '/language/wp-synonyms';
        jQuery.ajax({
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            'data': JSON.stringify(data),
            'type': 'PUT',
            'url': url,
            'success': function (data) {
                self.busy = false;
                if (data && data.synonymList) {
                    self.synonym_list = data.synonymList;
                    self.num_synonyms = data.numSynonyms;
                } else {
                    self.num_synonyms = 0;
                    self.synonym_list = [];
                }
                self.refresh();
            }

        }).fail(function (err) {
            self.busy = false;
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

    synonymDialogClose() {
        this.synonym_dlg_show = false;
        this.refresh();
    }

    synonymDialogSave() {
        // check the parameters are ok
        const self = this;
        const words = jQuery(".syn-words").val().trim();
        this.syn_dlg_words = words;
        if (words.length === 0) {
            this.busy = false;
            this.error = "you must at least provide two words to be synonymous";
            this.refresh();
        } else {
            this.busy = true;
            this.refresh();
            const payload = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                synonym: {
                    id: this.syn_dlg_id ? this.syn_dlg_id : "",
                    words: words,
                }
            }
            const url = settings.base_url + '/language/wp-save-synonym';
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'data': JSON.stringify(payload),
                'type': 'PUT',
                'url': url,
                'success': function (data) {
                    self.busy = false;
                    self.synonym_dlg_show = false;
                    self.refresh();
                    self.getSynonyms();
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

    addSynonym() {
        this.synonym_dlg_show = true;
        this.synonym_dlg_action = "add synonym"
        this.syn_dlg_id = null;
        this.syn_dlg_words = "";
        this.refresh();
    }

    // find a synonym in the list
    getSynonym(id) {
        for (const item of this.synonym_list) {
            if (item.id == id) {
                return item;
            }
        }
        return null;
    }

    editSynonym(id) {
        const synonym = this.getSynonym(id);
        if (synonym) {
            this.synonym_dlg_show = true;
            this.syn_dlg_id = synonym.id;
            this.synonym_dlg_action = "edit synonym"
            this.syn_dlg_words = synonym.words;
            this.refresh();
        }
    }

    deleteSynonym(id) {
        const synonym = this.getSynonym(id);
        if (synonym) {
            let q1 = synonym.words;
            if (confirm("are you sure you want to delete synonym id " + synonym.id + ",\n\"" + q1 + "\"?")) {
            }
        }
    }

    synonymResetPagination() {
        this.synonym_prev_id = '';
        this.synonym_page = 0;
        this.synonym_nav = ['null'];
    }

    synonymPrevPage() {
        if (this.synonym_page > 0) {
            this.synonym_page -= 1;
            this.synonym_prev_id = 'null';
            if (this.synonym_page < this.synonym_nav.length) {
                this.synonym_prev_id = this.synonym_nav[this.synonym_page];
            }
            this.getSynonyms();
        }
    }

    synonymNextPage() {
        const num_pages = Math.floor(this.num_synonyms / this.synonym_page_size) + 1;
        if (this.synonym_page < num_pages) {
            let id = 'null';
            if (this.synonym_list.length > 0) {
                id = this.synonym_list[this.synonym_list.length - 1].id;
                this.synonym_nav.push(id);
            }
            this.synonym_page += 1;
            this.synonym_prev_id = id;
            this.getSynonyms();
        }
    }

    renderSynonymTable() {
        if (this.synonym_list) {
            const str_list = [];
            for (const item of this.synonym_list) {
                const id = item.id;
                const expr = item.words;
                str_list.push("<tr>");
                str_list.push("<td>" + id + "</td>");
                str_list.push("<td>" + expr + "</td>");
                str_list.push("<td>");
                str_list.push("<span title='edit this synonym' onclick='data.editSynonym(" + id + ")' class='ss-button'>");
                str_list.push("<img src='" + image_base + "/images/edit.svg' class='edit-button-image ss-button' alt='edit' /></span>");
                str_list.push("<span title='delete this synonym' onclick='data.deleteSynonym(" + id + ")' class='ss-button'>");
                str_list.push("<img src='" + image_base + "/images/delete.svg' class='delete-button-image ss-button' alt='delete' /></span>");
                str_list.push("</td>");
                str_list.push("</tr>");
            }
            return str_list.join("\n");
        }
        return "";
    }

    renderSynonymPagination() {
        const num_pages = Math.floor(this.num_synonyms / this.synonym_page_size) + 1;
        const page = this.synonym_page + 1;
        const str_list = [];
        str_list.push("<button onclick='data.synonymPrevPage()' title='go to the previous page'" + ((page>1 && !this.busy) ? "" : "disabled") + ">prev</button>");
        str_list.push("<span>page " + page + " of " + num_pages + "</span>");
        str_list.push("<button onclick='data.synonymNextPage()' title='go to the next page'" + ((page < num_pages && !this.busy) ? "" : "disabled") + ">next</button>");
        return str_list.join("\n");
    }



    /////////////////////////////////////////////////////////////////////////////////
    // semantics

    // filter text-box enter press check
    handleSemanticKey(key) {
        if (key === 13) {
            this.getSemantics();
        }
    }

    // filter text-box change text
    setSemanticFilter(text) {
        this.semantic_filter = text;
    }

    // do search with filter
    getSemantics() {
        if (this.semantic_prev_filter !== this.semantic_filter) {
            this.semantic_prev_filter = this.semantic_filter;
            this.semanticResetPagination();
        }
        this.busy = true;
        const self = this;
        this.refresh();
        const data = {
            "organisationId": settings.organisationId, "kbId": settings.kbId, "sid": settings.sid,
            "prevWord": this.semantic_prev_id ? this.semantic_prev_id : "null",
            "filter": this.semantic_filter, "pageSize": this.semantic_page_size
        };
        const url = settings.base_url + '/language/wp-semantics';
        jQuery.ajax({
            headers: {
                'Content-Type': 'application/json',
                'API-Version': settings.api_version,
            },
            'data': JSON.stringify(data),
            'type': 'PUT',
            'url': url,
            'success': function (data) {
                self.busy = false;
                if (data && data.synonymList) {
                    self.semantic_list = data.semanticList;
                    self.num_semantics = data.numSemantics;
                } else {
                    self.num_semantics = 0;
                    self.semantic_list = [];
                }
                self.refresh();
            }

        }).fail(function (err) {
            self.busy = false;
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

    semanticDialogClose() {
        this.semantic_dlg_show = false;
        this.refresh();
    }

    semanticDialogSave() {
        // check the parameters are ok
        const self = this;
        const word = jQuery(".sem-word").val().trim();
        const semantic = jQuery(".sem-semantic").val().trim();
        this.sem_dlg_word = word;
        this.sem_dlg_semantic = semantic;
        if (word.length === 0 || semantic.length === 0) {
            this.busy = false;
            this.error = "you must at least provide a word with its semantic";
            this.refresh();
        } else {
            this.busy = true;
            this.refresh();
            const payload = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                semantic: {
                    word: word,
                    semantic: semantic,
                }
            }
            const url = settings.base_url + '/language/wp-save-semantic';
            jQuery.ajax({
                headers: {
                    'Content-Type': 'application/json',
                    'API-Version': settings.api_version,
                },
                'data': JSON.stringify(payload),
                'type': 'PUT',
                'url': url,
                'success': function (data) {
                    self.busy = false;
                    self.semantic_dlg_show = false;
                    self.refresh();
                    self.getSynonyms();
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

    addSemantic() {
        this.semantic_dlg_show = true;
        this.semantic_dlg_action = "add semantic"
        this.sem_dlg_word = "";
        this.syn_dlg_semantic = "";
        this.refresh();
    }

    // find a semantic in the list
    getSemantic(word) {
        for (const item of this.semantic_list) {
            if (item.word === word) {
                return item;
            }
        }
        return null;
    }

    editSemantic(word) {
        const semantic = this.getSemantic(word);
        if (semantic) {
            this.semantic_dlg_show = true;
            this.sem_dlg_word = semantic.word;
            this.semantic_dlg_action = "edit semantic"
            this.sem_dlg_semantic = semantic.semantic;
            this.refresh();
        }
    }

    deleteSemantic(word) {
        const semantic = this.getSemantic(word);
        if (semantic) {
            if (confirm("are you sure you want to delete semantic \"" + semantic.word + "\",\n\"" + semantic.semantic + "\"?")) {
            }
        }
    }

    semanticResetPagination() {
        this.semantic_prev_id = '';
        this.semantic_page = 0;
        this.semantic_nav = ['null'];
    }

    semanticPrevPage() {
        if (this.semantic_page > 0) {
            this.semantic_page -= 1;
            this.semantic_prev_id = 'null';
            if (this.semantic_page < this.semantic_nav.length) {
                this.semantic_prev_id = this.semantic_nav[this.semantic_page];
            }
            this.getSemantics();
        }
    }

    semanticNextPage() {
        const num_pages = Math.floor(this.num_semantics / this.semantic_page_size) + 1;
        if (this.semantic_page < num_pages) {
            let word = 'null';
            if (this.semantic_list.length > 0) {
                word = this.semantic_list[this.semantic_list.length - 1].word;
                this.synonym_nav.push(word);
            }
            this.semantic_page += 1;
            this.semantic_prev_id = word;
            this.getSemantics();
        }
    }

    renderSemanticTable() {
        if (this.semantic_list) {
            const str_list = [];
            for (const item of this.semantic_list) {
                const id = item.word;
                const expr = item.semantic;
                str_list.push("<tr>");
                str_list.push("<td>" + id + "</td>");
                str_list.push("<td>" + expr + "</td>");
                str_list.push("<td>");
                str_list.push("<span title='edit this semantic' onclick='data.editSemantic(" + id + ")' class='ss-button'>");
                str_list.push("<img src='" + image_base + "/images/edit.svg' class='edit-button-image ss-button' alt='edit' /></span>");
                str_list.push("<span title='delete this semantic' onclick='data.deleteSemantic(" + id + ")' class='ss-button'>");
                str_list.push("<img src='" + image_base + "/images/delete.svg' class='delete-button-image ss-button' alt='delete' /></span>");
                str_list.push("</td>");
                str_list.push("</tr>");
            }
            return str_list.join("\n");
        }
        return "";
    }

    renderSemanticPagination() {
        const num_pages = Math.floor(this.num_semantics / this.semantic_page_size) + 1;
        const page = this.semantic_page + 1;
        const str_list = [];
        str_list.push("<button onclick='data.semanticPrevPage()' title='go to the previous page'" + ((page>1 && !this.busy) ? "" : "disabled") + ">prev</button>");
        str_list.push("<span>page " + page + " of " + num_pages + "</span>");
        str_list.push("<button onclick='data.semanticNextPage()' title='go to the next page'" + ((page < num_pages && !this.busy) ? "" : "disabled") + ">next</button>");
        return str_list.join("\n");
    }



}

