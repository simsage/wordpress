//
// SimSage Operator helper class
//

// number of ms before the tick disappears
const teachingSuccessfulTickTimeout = 1000;
// refresh rate in ms
const operatorRefreshRate = 5000;
const countDownStart = 5;
const countDownRate = 1000;

// message types
const mt_ActiveConnections = "active connections";
const mt_Error = "error";
const mt_Message = "message";
const mt_TeachingSuccessful = "teaching success";
const mt_Typing = "typing";
const mt_Disconnect = "disconnect";


// operator class
class Operator extends SimSageCommon {

    constructor(ops_update_ui) {
        super();
        this.ops_update_ui = ops_update_ui;
        this.response = '';
        this.endpointId = SimSageCommon.getOperatorId();  // end-point for operators

        this.ready_to_rcv = false;           // operator ready to do some work?
        this.clientId = '';           // who we're connected to

        this.refresh_timer = null;              // operator refresh timer
        this.current_question = '';             // what the connected user wants to know currently

        this.conversation_list = [];
        this.previousAnswer = '';
        this.previousAnswerCountdown = 0;

        this.question = '';
        this.question_message = {};
        this.answer = '';
        this.answer_message = {};
        this.teachingSuccess = false;

        // number of bots connected to the server
        this.connectionCount = 0;
    }


    // override for super set_connected - intercept connection/disconnection events
    set_connected(is_connected) {
        super.set_connected(is_connected);
        if (is_connected) {
            if (this.refresh_timer === null) {
                this.refresh_timer = setInterval(() => this.refresh_tick(), operatorRefreshRate);
            }
            // this.count_down_timer = setInterval(() => this.countDownTick(), countDownRate);
        } else {
            if (this.refresh_timer !== null) {
                window.clearInterval(this.refresh_timer);
                this.refresh_timer = null;
            }
        }
    }


    // overwrite: call refresh ui
    refresh() {
        if (this.ops_update_ui) {
            this.ops_update_ui(this);
        }
    }

    // operator reply key was pressed
    operator_key_press(event, text) {
        this.response = text;
        if (event.keyCode === 13) {
            this.reply_click(null);
        } else {
            this.operatorIsTyping();
        }
    }


    // submit operator response
    reply_click(_text) {
        let text = this.response;
        if (_text !== null) { // override?
            text = _text;
        }
        if (this.clientId.length > 0 && text.length > 0) {
            const msg = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorId: SimSageCommon.getOperatorId(),
                clientId: this.clientId,
                text: text,
            };
            this.post_message('/ops/wp-chat', msg);

            // add this message to our list of items
            this.conversation_list.push({
                id: this.conversation_list.length + 1, primary: text, secondary: "You", used: false, is_simsage: true
            });

            if (_text === null) {
                this.response = '';
            }
            this.refresh();
        }
    }


    // better make sure we are sure before doing something accidentally
    confirmBanUser() {
        if (confirm("Are you SURE you want to BAN this user from the System?")) {
            this.operatorBanUser();
        }
    }


    // the operator is typing - send a message to the client if there is one
    operatorIsTyping() {
        if (this.ready_to_rcv && this.clientId.length > 0) {
            const msg = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                fromId: SimSageCommon.getOperatorId(),
                toId: this.clientId,
                isTyping: true,
            };
            this.post_message('/ops/wp-typing', msg);
        }
    }


    operatorReady() {
        if (this.is_connected) {
            this.ready_to_rcv = true;
            const data = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorId: SimSageCommon.getOperatorId(),
            };
            this.post_message('/ops/wp-ready', data);
            this.refresh();
        }
    }


    operatorTakeBreak() {
        if (this.is_connected) {
            this.ready_to_rcv = false;

            const msg = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorId: SimSageCommon.getOperatorId(),
                clientId: this.clientId,
            };
            this.post_message('/ops/wp-take-break', msg);

            this.clientId = '';
            this.conversation_list = [];
            this.refresh();
        }
    }


    operatorNextUser() {
        if (this.is_connected) {
            this.ready_to_rcv = true;

            const msg = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorId: SimSageCommon.getOperatorId(),
                clientId: this.clientId,
            };
            this.post_message('/ops/wp-next-user', msg);

            this.clientId = '';
            this.conversation_list = [];
            this.refresh();
        }
    }


    operatorBanUser() {
        if (this.is_connected) {
            this.ready_to_rcv = true;

            const msg = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorId: SimSageCommon.getOperatorId(),
                clientId: this.clientId,
            };
            this.post_message('/ops/wp-ban-user', msg);

            this.clientId = '';
            this.conversation_list = [];
            this.refresh();
        }
    }


    // timer callback - operator is still alive / here
    refresh_tick() {
        if (this.ready_to_rcv) {
            const msg = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorList: [{operatorId: SimSageCommon.getOperatorId(), isTyping: this.is_typing, clientId: this.clientId}],
            };
            this.post_message('/ops/wp-refresh', msg);
        }
    }


    //function called when button is clicked and shows notification
    notifyUser(text) {
        if (Notification.permission === "granted") {  // we have notification permission?
            const title = "the Operator needs your Attention"; // title
            const options = {
                body: "A new question just came in.  Click here to open the operator window.  \"" + text + "\""
            };
            const notification = new Notification(title, options);  // display notification
            notification.onclick = function() { window.focus(); }
        }
    }


    // callback from operator message list to select a message for teaching
    selectForLearn(message_key) {
        const message = this.conversation_list.find(x => x.key == message_key);
        if (message) {
            if (!message.used) {
                if (message && message.type === "simsage") {
                    this.answer = message.message;
                    this.answer_message = message;
                } else {
                    this.question = message.message;
                    this.question_message = message;
                }
                this.refresh();
            }
        }
    }


    // operator has chosen to each SimSage something new
    teach() {
        if (this.clientId.length > 0 && this.question.length > 0 && this.answer.length > 0) {
            const msg = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorId: SimSageCommon.getOperatorId(),
                clientId: this.clientId,
                text: this.question,
                answer: this.answer,
            };
            this.answer_message.used = true;
            this.question_message.used = true;
            this.post_message('/ops/wp-teach', msg);
            this.refresh();
        }
    }


    // clear qa section
    clearQA() {
        this.question = '';
        this.answer = '';
        this.refresh();
    }


    dontUse() {
        this.previousAnswer = '';
        this.previousAnswerCountdown = 0;
        this.refresh();
    }


    use() {
        this.reply_click(this.previousAnswer);
        this.previousAnswer = '';
        this.previousAnswerCountdown = 0;
        this.refresh();
    }


    // overwrite: generic web socket receiver
    receive_ws_data(data) {
        this.busy = false;
        if (data) {

            if (data.messageType === mt_Error && data.error.length > 0) {
                this.error = data.error;  // set an error
                this.refresh();
            }

            else if (data.messageType === mt_TeachingSuccessful) {

                // display a green tick - and one second later - remove the box and the tick
                this.teachingSuccess = true;
                setTimeout(() => {
                    this.question = '';
                    this.answer = '';
                    this.teachingSuccess = false;
                    this.refresh();
                }, teachingSuccessfulTickTimeout);
            }

            else if (data.messageType === mt_Disconnect) {
                if (data.disconnectedByClient) {
                    this.error = 'Client disconnected.';
                }
                this.clientId = '';
                this.conversation_list = [];
                this.refresh();
            }

            else if (data.messageType === mt_ActiveConnections) {
                this.connectionCount = data.connectionCount;
                this.refresh();
            }

            else if (data.messageType === mt_Typing) {
                this.isTyping(data.fromIsTyping);
            }

            else if (data.messageType === mt_Message) {

                if (!data.kbId || data.kbId.length === 0) {
                    this.error = "client text does not include a valid knowledge-base id.";
                    this.refresh();

                } else {

                    // stop the typing
                    this.is_typing = false;

                    // only get the conversation list if we haven't got one yet
                    if (this.conversation_list.length === 0 && data.conversationList && data.conversationList.length > 0) {
                        // does the message come with some of the conversation data of previous attempts
                        this.conversation_list = []; // reset the list - we have data
                        let count = this.conversation_list.length + 1; // unique ids assigned for REACT
                        for (const index in data.conversationList) {
                            if (data.conversationList.hasOwnProperty(index)) {
                                let ci = data.conversationList[index];
                                const is_simsage = ci.origin !== "user";
                                this.conversation_list.push({
                                    id: count, primary: ci.conversationText,
                                    secondary: is_simsage ? "You" : "user", used: false, is_simsage: is_simsage
                                });
                                count += 1;
                            }
                        }

                        const last_item = data.conversationList[data.conversationList.length - 1];
                        if (!last_item.is_simsage) {
                            this.current_question = last_item.primary;
                        }

                    } else if (data.text && data.text.length > 0) {
                        // otherwise - get the conversation from what just was said by the user
                        this.conversation_list = JSON.parse(JSON.stringify(this.conversation_list)); // copy existing list
                        // add new item
                        this.conversation_list.push({
                            id: this.conversation_list.length + 1, primary: data.text,
                            secondary: "user", used: false, is_simsage: false
                        });
                        this.current_question = data.text;
                    }

                    // html 5 notifications enabled?
                    if (data.text && data.text.length > 0 &&
                        window.Notification && window.Notification.permission === "granted") {  // we have notification permission?
                        const title = "the Operator needs your Attention"; // title
                        const options = {
                            body: "A new question just came in.  Click here to open the operator window.  \"" + data.text + "\""
                        };
                        const notification = new Notification(title, options);  // display notification
                        notification.onclick = function () {
                            window.focus();
                        }
                    }

                    if (data.previousAnswer && data.previousAnswer.length > 0) {
                        this.previousAnswer = data.previousAnswer;
                        this.previousAnswerCountdown = countDownStart;
                    }

                    if (data.clientId.length > 5) {
                        this.clientId = data.clientId;
                        this.clientKbId = data.kbId;
                    }

                    this.refresh();
                }

            } // if mt_message

        }
    }


}
