// create an instance of the Operator Class
const ops = new Operator(ops_update_ui);

/**
 * callback from the operator for display logic
 * @param ops the ops class Instance
 */
function ops_update_ui(ops) {
    // should we display the advanced search menu?
    jQuery(function($){
        // buttons and status
        if (ops.ready_to_rcv || !ops.is_connected) {
            $("#btnReady").attr("disabled", "true");
        } else {
            $("#btnReady").removeAttr("disabled");
        }
        if (!ops.ready_to_rcv || !ops.is_connected) {
            $("#btnBreak").attr("disabled", "true");
            $("#btnNextUser").attr("disabled", "true");
            $("#btnBanUser").attr("disabled", "true");
            $("#botCount").html("");
        } else {
            $("#btnBreak").removeAttr("disabled");
            $("#btnNextUser").removeAttr("disabled");
            $("#btnBanUser").removeAttr("disabled");
            $("#botCount").html("users: " + ops.numActiveConnections);
        }

        if (ops.clientId === "") {
            $("#btnNextUser").attr("disabled", "true");
            $("#btnBanUser").attr("disabled", "true");
        }

        const txtResponse = $("#txtResponse");
        if (ops.is_connected && ops.ready_to_rcv && ops.clientId.length > 0) {
            txtResponse.removeAttr("disabled");
            txtResponse.focus();
            $("#btnChat").removeAttr("disabled");
        } else {
            txtResponse.attr("disabled", "true");
            $("#btnChat").attr("disabled", "true");
        }
        if (ops.response === '') {
            txtResponse.val(ops.response);
        }

        // learning section
        if (ops.question.length > 0 || ops.answer.length > 0) {
            $("#learningSection").show();
            $("#txtQuestion").text(ops.question);
            $("#txtAnswer").text(ops.answer);
            if (ops.question.length > 0 && ops.answer.length > 0) {
                $("#twoSection").show();
                $("#oneSection").hide();
            } else {
                $("#oneSection").show();
                $("#twoSection").hide();
            }
            if (ops.teachingSuccess) {
                $("#tick").show();
            } else {
                $("#tick").hide();
            }
        } else {
            $("#learningSection").hide();
        }
        // previous answer section
        if (ops.previousAnswer.length > 0) {
            $("#txtPreviousAnswer").text(ops.previousAnswer);
            $("#btnPreviousAnswer").val("use in " + ops.previousAnswerCountdown + " secs");
            $("#previousAnswerSection").show();
        } else {
            $("#previousAnswerSection").hide();
        }
        // the conversation list
        $("#conversationList").html(render_operator_conversations(ops));
    });
}


// startup - connect our plugin to a SimSage server
jQuery(function($) {
    $(document).ready(function () {
        // connect to our web-sockets for two way conversations
        ops.ws_connect();
    })
});


