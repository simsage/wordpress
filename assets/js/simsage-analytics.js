//
// SimSage Analytics class
//


class SimsageAnalytics {

    constructor(update_ui) {
        this.update_ui = update_ui;
        this.response = '';
        this.error = '';
        this.busy = true;

        // date selected by ui
        this.date = new Date();

        // active tab
        this.tab = 'logs';
        this.tab_list = ['keywords', 'searches', 'logs'];

        // the stats
        this.search_frequencies = [];
        this.search_keyword_frequencies = [];
    }

    refresh() {
        if (this.update_ui) {
            this.update_ui(this);
        }
    }

    select_tab(tab) {
        this.tab = tab;
        this.refresh();
    }

    set_date(date) {
        this.date = date;
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
                self.search_frequencies = self.convert_months(data.searchAccessFrequency);
                const kw_list = self.convert_dictionary(data.queryWordFrequency);
                kw_list.sort(function(first, second) {
                    return second.value - first.value;
                });
                self.search_keyword_frequencies = kw_list;
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

    dlOperatorConversations() {
    }

    dlQueryLog() {
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
        fetch(new Request(url), init)
            .then(function(response) {
                if (!response.ok) {
                    self.error = `HTTP error! status: ${response.status}`;
                    self.refresh();
                } else {
                    return response.blob().then((b) => {
                        let a = document.createElement("a");
                        a.href = URL.createObjectURL(b);
                        const filename = "language-customizations-" + SimsageAnalytics.getFormattedTime() + ".xlsx";
                        a.setAttribute("download", filename);
                        a.click();
                    });
                }
            });
    }

    dlContentAnalysis() {
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

}
