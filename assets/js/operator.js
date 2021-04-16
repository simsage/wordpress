//
// SimSage Operator helper class
//

// message types
const mt_ActiveConnections = "active connections";
const mt_Error = "error";
const mt_Message = "message";
const mt_TeachingSuccessful = "teaching success";
const mt_Typing = "typing";
const mt_Disconnect = "disconnect";


// operator class
class Operator extends SimSageCommon {

    constructor() {
        super();
    }


    // send a chat message to SimSage
    chat(client_id, text) {
        const msg = {
            organisationId: settings.organisationId,
            kbId: settings.kbId,
            sid: settings.sid,
            operatorId: SimSageCommon.get_client_id(),
            clientId: client_id,
            text: text,
        };
        this.post_message('/api/ops/wp-chat', msg);
    }

    // the operator is typing - send a message to the client if there is one
    signal_operator_is_typing(clientId) {
        const msg = {
            organisationId: settings.organisationId,
            kbId: settings.kbId,
            sid: settings.sid,
            fromId: SimSageCommon.get_client_id(),
            toId: clientId,
            isTyping: true,
        };
        this.post_message('/api/ops/wp-typing', msg);
    }


    operator_ready() {
        if (this.is_connected) {
            const data = {
                organisationId: settings.organisationId,
                kbId: settings.kbId,
                sid: settings.sid,
                operatorId: SimSageCommon.get_client_id(),
            };
            this.post_message('/api/ops/wp-ready', data);
        }
    }


    operator_take_break(clientId) {
        const msg = {
            organisationId: settings.organisationId,
            kbId: settings.kbId,
            sid: settings.sid,
            operatorId: SimSageCommon.get_client_id(),
            clientId: clientId,
        };
        this.post_message('/api/ops/wp-take-break', msg);
    }


    operator_next_user() {
        const msg = {
            organisationId: settings.organisationId,
            kbId: settings.kbId,
            sid: settings.sid,
            operatorId: SimSageCommon.get_client_id(),
            clientId: this.clientId,
        };
        this.post_message('/api/ops/wp-next-user', msg);
    }


    operator_ban_user() {
        const msg = {
            organisationId: settings.organisationId,
            kbId: settings.kbId,
            sid: settings.sid,
            operatorId: SimSageCommon.get_client_id(),
            clientId: this.clientId,
        };
        this.post_message('/api/ops/wp-ban-user', msg);
    }


    // timer callback - operator is still alive / here
    operator_refresh_tick() {
        const msg = {
            organisationId: settings.organisationId,
            kbId: settings.kbId,
            sid: settings.sid,
            operatorList: [{operatorId: SimSageCommon.get_client_id(), isTyping: this.is_typing, clientId: this.clientId}],
        };
        this.post_message('/api/ops/wp-refresh', msg);
    }


    // function called when button is clicked and shows notification
    notify_user(text) {
        if (Notification.permission === "granted") {  // we have notification permission?
            const title = "the Operator needs your Attention"; // title
            const options = {
                body: "A new question just came in.  Click here to open the operator window.  \"" + text + "\""
            };
            const notification = new Notification(title, options);  // display notification
            notification.onclick = function() { window.focus(); }
        }
    }


    // operator has chosen to each SimSage something new
    teach(client_id, question, answer) {
        const msg = {
            organisationId: settings.organisationId,
            kbId: settings.kbId,
            sid: settings.sid,
            operatorId: SimSageCommon.get_client_id(),
            clientId: client_id,
            text: question,
            answer: answer,
        };
        this.post_message('/api/ops/wp-teach', msg);
    }


    // overwrite: generic web socket receiver
    receive_ws_data(data) {
        this.busy = false;
        if (data) {

            if (data.messageType === mt_Error && data.error.length > 0) {
                error(data.error);
            }

            else if (data.messageType === mt_TeachingSuccessful) {
                // used to be a timer for displaying the successful tick after
                // a succesful teaching event
            }

            else if (data.messageType === mt_Disconnect) {
                if (data.disconnectedByClient) {
                    error('Client disconnected.');
                }
                // client_disconnected(false);
            }

            else if (data.messageType === mt_ActiveConnections) {
                set_active_connections(data.connectionCount);
            }

            else if (data.messageType === mt_Typing) {
                if (data.toId === SimSageCommon.get_client_id()) {
                    if (data.fromIsTyping) {
                        ops.typing_last_seen = SimSageCommon.get_system_time() + 2000;
                        ops.is_typing = true;
                    }
                    client_is_typing();
                }
            }

            else if (data.messageType === mt_Message) {

                if (!data.kbId || data.kbId.length === 0) {
                    error("client text does not include a valid knowledge-base id.");

                } else {

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
                        set_previous_answer(data.previousAnswer);
                    }

                    // have we been assigned a client?
                    if (data.clientId && data.clientId.length > 5) {
                        connect_to_client(data.clientId, data.kbId, data.conversationList ? data.conversationList : [], false);
                    }

                }

            } // if mt_message

        }
    }


}
