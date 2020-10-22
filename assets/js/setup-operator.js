
// number of ms before the tick disappears
const teachingSuccessfulTickTimeout = 1000;
// refresh rate in ms
const operator_wait_timeout_in_ms = 10000;


// create an instance of the Operator Class
ops = new Operator();

response = '';

ready_to_rcv = false;           // operator ready to do some work?
clientId = '';                  // who we're connected to
clientKbId = '';                // and re- what knowledge-base
assignedOperatorId = '';
is_typing = false;

conversation_list = [];
previousAnswer = '';
previousAnswerCountdown = 0;

question = '';
question_message = {};
answer = '';
answer_message = {};
teachingSuccess = false;

// number of bots connected to the server
connectionCount = 0;


callback = {
    operator_ready: () => ops.operator_ready(),
    operator_take_break: () => ops.operator_take_break(),
    signal_operator_is_typing: (clientId) => ops.signal_operator_is_typing(clientId),
    operator_ban_user: () => ops.operator_ban_user(),
    operator_next_user: () => ops.operator_next_user(),
    operator_refresh_tick: () => ops.operator_refresh_tick(),
    teach: (client_id, question, answer) => ops.teach(client_id, question, answer),
    chat: (client_id, text) => ops.chat(client_id, text),
    notify_user: (text) => ops.notify_user(text),
}


function update_buttons() {
}


// tell the system we are busy (or not)
function busy() {
    update_buttons();
}

// user clicks operator ready button
function operator_ready() {
    if (callback.operator_ready) {
        callback.operator_ready();
        ready_to_rcv = true;
        question = '';
        answer = '';
        jQuery("#btnReady").attr("disabled", "true");
        jQuery("#btnBreak").removeAttr("disabled");
    }
}

function operator_take_break() {
    if (ops.is_connected && callback.operator_take_break) {
        client_disconnected();
        callback.operator_take_break();

        ready_to_rcv = false; // but not ready to receive
        jQuery("#btnReady").removeAttr("disabled");
        jQuery("#btnBreak").attr("disabled", "true");
    }
}

function operator_next_user() {
    if (ops.is_connected) {
        if (callback.operator_next_user) {
            callback.operator_next_user();
        }
        client_disconnected();
    }
}

function confirm_ban_user() {
    if (confirm("Are you SURE you want to BAN this user from the System?")) {
        operator_ban_user();
    }
}

function operator_ban_user() {
    if (this.is_connected && callback.operator_ban_user) {
        callback.operator_ban_user();
    }
    client_disconnected();
}

function operator_key_press(event, text) {
    if (event.keyCode === 13) {
        reply_click(null);
    } else {
        response = text;
        if (ready_to_rcv && clientId && clientId.length > 0 && callback.signal_operator_is_typing) {
            callback.signal_operator_is_typing(clientId);
        }
    }
}

// reply button is clicked (or enter is pressed inside the operator text box)
function reply_click(_text) {
    let text = response; // local value typed by user
    if (_text !== null) { // override?
        text = _text;
    }
    if (clientId.length > 0 && text.length > 0) {
        if (callback.chat) {
            callback.chat(clientId, text);
        }
        // // add this message to our list of items
        conversation_list.push({
            id: conversation_list.length + 1, primary: text, secondary: "You", used: false, is_simsage: true
        });
        response = '';
        jQuery("#txtResponse").val("");
        // re-render the conversation list
        jQuery("#conversationList").html(render_operator_conversations());
    }
}

function setup_sign_in() {
}

function dont_use() {
    previousAnswer = '';
    previousAnswerCountdown = 0;
}

function use() {
    reply_click(previousAnswer);
    previousAnswer = '';
    previousAnswerCountdown = 0;
}

// a message has come from the operator that she has disconnected us
function client_disconnected() {
    // disconnect any client
    connect_to_client('', '', []);
}

function set_active_connections(count) {
    connectionCount = count;
    jQuery("#botCount").html("users: " + connectionCount);
}

function client_is_typing(typing) {
    ops.is_typing = typing;
    is_typing = typing;
    jQuery("#conversationList").html(render_operator_conversations());
}

// we just got a message that the operator's typing status has changed (timer based)
function update_chat_window(chat_list, is_typing) {
    client_is_typing(is_typing);
}

// callback from operator message list to select a message for teaching
function select_for_learn(message_id) {
    const message = conversation_list.find(x => x.id == message_id);
    if (message) {
        if (!message.used) {
            if (message && message.is_simsage) {
                // de-select all other user messages
                for (const m of conversation_list) {
                    if (m.is_simsage && m.id != message_id) {
                        m.selected = false;
                    }
                }
                message.selected = !message.selected;
                if (message.selected) {
                    answer = message.primary;
                    answer_message = message;
                } else {
                    answer_message = {};
                    answer = '';
                }
            } else {
                // de-select all other non-user messages
                for (const m of conversation_list) {
                    if (!m.is_simsage && m.id != message_id) {
                        m.selected = false;
                    }
                }
                message.selected = !message.selected;
                if (message.selected) {
                    question = message.primary;
                    question_message = message;
                } else {
                    question_message = {};
                    question = '';
                }
            }

            // re-render the conversation list
            jQuery("#conversationList").html(render_operator_conversations());

            ops_show_teaching_dialog();
        }
    }
}

function clearQA() {
    question = '';
    question_message = {};
    answer = '';
    answer_message = {};
    for (const m of conversation_list) {
        m.selected = false;
    }
    ops_show_teaching_dialog();
    // re-render the conversation list
    jQuery("#conversationList").html(render_operator_conversations());
}

function teach() {
    if (clientId.length > 0 && question.length > 0 && answer.length > 0) {
        answer_message.used = true;
        question_message.used = true;
        if (callback.teach) {
            callback.teach(clientId, question, answer);
        }
        question = '';
        answer = '';
        ops_show_teaching_dialog();
        // re-render the conversation list
        jQuery("#conversationList").html(render_operator_conversations());
    }
}

// show a teaching dialog after the user has made their selection
function ops_show_teaching_dialog() {
    // learning section
    if (question.length > 0 || answer.length > 0) {
        jQuery("#learningSection").show();
        jQuery("#txtQuestion").text(question);
        jQuery("#txtAnswer").text(answer);
        if (question.length > 0 && answer.length > 0) {
            jQuery("#twoSection").show();
            jQuery("#oneSection").hide();
        } else {
            jQuery("#oneSection").show();
            jQuery("#twoSection").hide();
        }
    } else {
        jQuery("#learningSection").hide();
    }
}

// render a busy message (animating dots)
function render_simsage_busy() {
    return  "<div class=\"busy-image-container\"><img class=\"busy-image\" src=\"" + image_base + "images/dots.gif\" alt=\"there is some typing going on\"></div>";
}

/**
 * replace < and > in a string to make it html safe
 * @param str the string to act on
 * @return the escaped string
 */
function esc_html(str) {
    if (typeof str === 'string' || str instanceof String) {
        return str
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
    }
    return str;
}


// render the operator's conversation list
function render_operator_conversations() {
    const result = [];
    for (const message of conversation_list) {
        const html_text = esc_html(message.primary);
        const id = message.id;
        if (!message.is_simsage) {
            const style = (message.used || message.selected) ? 'chat-user-box-dark' : 'chat-user-box';
            result.push('<div class="conversation"><div class="' + style + '" onClick="select_for_learn(' + id + ')">\n' +
                '    <img src="' + image_base + 'images/human.svg" class="chat-user-image" alt="user" />\n' +
                '    <div class="user-message-text">' + html_text + '</div>\n' +
                '    </div></div>\n');
        } else {
            const style = (message.used || message.selected) ? 'chat-simsage-box-dark' : 'chat-simsage-box';
            result.push('<div class="conversation"><div class="' + style + '" onClick="select_for_learn(' + id + ')">\n' +
                '    <img src="' + image_base + 'images/tinman.svg" class="chat-simsage-image" alt="SimSage" />\n' +
                '    <div class="simsage-message-text">' + html_text + '</div>\n' +
                '    </div></div>\n');
        }
    }
    if (is_typing) {
        result.push(render_simsage_busy());
    }
    return result.reverse().join('\n');
}

// notification of client_id has been set
function connect_to_client(client_id, client_kb_id, prev_conversation_list) {
    if (client_id === '') {
        // disconnected
        clientId = '';
        clientKbId = '';
        conversation_list = [];
        ops.is_typing = false;
        is_typing = false;
        question = '';
        answer = '';
        question_message = {};
        answer_message = {};
        // disable the text field and chat button and others
        jQuery("#txtResponse").attr("disabled", "true");
        jQuery("#btnChat").attr("disabled", "true");
        jQuery("#btnNextUser").attr("disabled", "true");
        jQuery("#btnBanUser").attr("disabled", "true");
        jQuery("#botCount").html("");
        jQuery("#conversationList").html("");

    } else {
        clientId = client_id;
        clientKbId = client_kb_id;
        ops.is_typing = false;
        is_typing = false;
        // render any previous conversations if not done so already
        add_previous_conversation_context(prev_conversation_list);
        // render the conversation list
        jQuery("#conversationList").html(render_operator_conversations());
        // enable chat field and button
        const txtResponse = jQuery("#txtResponse");
        txtResponse.removeAttr("disabled");
        txtResponse.focus();
        jQuery("#btnChat").removeAttr("disabled");
        jQuery("#btnBreak").removeAttr("disabled");
        jQuery("#btnNextUser").removeAttr("disabled");
        jQuery("#btnBanUser").removeAttr("disabled");
    }
}

// notification of a previous answer being available
function set_previous_answer(previous_answer) {
    previousAnswer = previous_answer;
    previousAnswerCountdown = 1;
    update_buttons();
}

// notification of the bot adding some text
function add_bot_response(text) {
    // otherwise - get the conversation from what just was said by the user
    conversation_list = JSON.parse(JSON.stringify(conversation_list)); // copy existing list
    // add new item
    conversation_list.push({
        id: conversation_list.length + 1, primary: text,
        secondary: "user", used: false, is_simsage: false
    });
}

// if connected, let the system know we're still here at some interval
function operator_present_tick() {
    if (ready_to_rcv && ops.is_connected && callback.operator_refresh_tick) {
        callback.operator_refresh_tick();
    }
}

// notification of a previous conversation list being available
function add_previous_conversation_context(prev_conversation_list) {
    if (prev_conversation_list && prev_conversation_list.length > 0) {
        // does the message come with some of the conversation data of previous attempts
        conversation_list = []; // reset the list - we have data
        let count = 1;
        for (const index in prev_conversation_list) {
            if (prev_conversation_list.hasOwnProperty(index)) {
                let ci = prev_conversation_list[index];
                const is_simsage = (ci.origin !== "user");
                conversation_list.push({
                    id: count, primary: ci.conversationText, selected: false,
                    secondary: is_simsage ? "You" : "user", used: false, is_simsage: is_simsage
                });
                count += 1;
            }
        }
    }
}

function setup_dropdowns(dd1, dd2) {
    update_buttons();
}

function error(err_message) {
    if (err_message !== '') {
        console.log("err_message:" + err_message);
    }
}

function simsage_connected() {
    jQuery("#btnReady").removeAttr("disabled");
}

// startup - connect our plugin to a SimSage server
jQuery(function($) {
    jQuery(document).ready(function () {
        // connect to our web-sockets for two way conversations
        ops.init_simsage();
        // setup operator timer ticks
        window.setInterval(() => operator_present_tick(), operator_wait_timeout_in_ms);
    })
});


