function autoCheckForOtherAnswer(element) {
    if (typeof element === 'string' && !element.startsWith('#')) {
		element = `#${element}`;
	}

    var siblings = $j(element).siblings();
    var checkbox = siblings[0];
    if (element.val().length > 0) checkbox.setAttribute('checked', true);
    else checkbox.removeAttribute('checked');
}

function Timer(startTime, stopTime, displayFunction, ringFunction) {
    this.second = 1000;

    this.setStartTime = function (startTime) {
        this.startTime = startTime;
        this.currentTime = startTime;
    };

    this.setStopTime = function (stopTime) {
        this.stopTime = stopTime;
    };

    this.pause = function () {
        if (this.intervalPointer != null) {
            clearInterval(this.intervalPointer);
            this.intervalPointer = null;
        }
    };

    this.isExpired = function () {
        return this.expired;
    };

    this.toString = function () {
        var diff = this.stopTime - this.currentTime;
        var min_to_sec = 60;
        var hour_to_sec = 60 * 60;

        var ore = Math.floor(diff / hour_to_sec);
        diff -= ore * hour_to_sec;
        var minuti = Math.floor(diff / min_to_sec);
        diff -= minuti * min_to_sec;
        var secondi = diff;

        return zeroFill(ore, 2) + ":" + zeroFill(minuti, 2) + ":" + zeroFill(secondi, 2);
    };

    this.ring = ringFunction;

    this.display = displayFunction;

    this.clock = function () {
        this.display(this.toString());
        if (this.stopTime - this.currentTime <= 0) {
            this.expired = true;
            this.stop();
            this.ring();
        }
        else this.expired = false;
        this.currentTime++;
    };

    this.start = function () {
        if (this.startTime >= this.stopTime) {
            this.stopTime = this.startTime;
        }
        this.currentTime = this.startTime;
        this.display(this.toString());
        this.resume();
    };

    this.resume = function () {
        this.pause();
        this.intervalPointer = setInterval("clockTimer();", this.second);
    };

    this.stop = function () {
        this.pause();
    };

    this.intervalPointer = null;
    this.expired = false;
    this.setStartTime(parseInt(startTime));
    this.setStopTime(parseInt(stopTime));
}

var timer = null;

function testTimer(startTime, stopTime, message) {
    timer = new Timer(startTime, stopTime,
        function (value) {
            $j('.absoluteTimer').css({ display: 'block' });
            $j('.absoluteTimer').html(value);
        },
        function () {
            alert(message);
            $j('testForm').trigger('submit');
        }
    );
}

function clockTimer() {
    if (timer != null) {
        timer.clock();
    }
}

const startTimer = () => {
    if (timer != null) {
        timer.start();
    }
}

const closeTest = async () => {
    const appendChar = document.location.search.length ? '&' : '?';
    return await fetch(`${document.URL}${appendChar}unload`,{
        keepalive: true,
    });
}

if (window.attachEvent) {
	window.attachEvent('onload', startTimer);
	window.attachEvent('beforeunload', closeTest);
} else if (window.addEventListener) {
	window.addEventListener('load', startTimer);
	window.addEventListener('beforeunload', closeTest, { 'once' : true, 'passive' : true });
} else {
	document.addEventListener('load',startTimer);
	document.addEventListener('beforeunload', closeTest, { 'once' : true, 'passive' : true });
}

function move(e, id_nodo, direction) {
    var loc = window.location.pathname;
    var dir = loc.substring(0, loc.lastIndexOf('/'));
    $j.ajax({
        url: dir + '/move.php?id_nodo=' + id_nodo + '&direction=' + direction,
        dataType: 'text',
        async: false,
        success: function (data) {
            if (data == '1') {
                var li = $j(e).closest('li');
                var span_order = li.find('.span_order');
                if (direction == 'up') {
                    var alt = li.prev();
                    alt.before(li.detach());
                    span_order.text(parseInt(span_order.text()) - 1);
                    alt.find('.span_order').text(parseInt(alt.find('.span_order').text()) + 1);
                }
                else if (direction == 'down') {
                    var alt = li.next();
                    alt.after(li.detach());
                    span_order.text(parseInt(span_order.text()) + 1);
                    alt.find('.span_order').text(parseInt(alt.find('.span_order').text()) - 1);
                }

                var offset = li.offset().top;
                var height = $j(window).height();
                var scrollTop = $j(window).scrollTop();
                if ((direction == 'down' && offset > scrollTop + height) || (direction == 'up' && offset < scrollTop + height)) {
                    $j(window).scrollTop(offset);
                }
            }
        }
    });
}

load_js('js/commons.js');

function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

var domReady = false;
async function confirmSubmit(formObj) {
    if (!domReady) {
        return false;
    }

    var answers = {};
    var res = 0;
    $j('[name^="question["]').each(function (index, e) {
        var isCommonAnswer = true;
        if ($j(e).closest('.answer_cloze_slot_test, .answer_cloze_erase_test, .answer_cloze_highlight_test').length === 1) {
            isCommonAnswer = false;
        }

        if (isCommonAnswer) {
            var tag = $j(e).prop('tagName').toLowerCase();
            var type = $j(e).attr('type');
            var name = $j(e).attr('name');
            var matches = name.match(/\[([^\]]+)\]\[([^\]]+)\]\[(answer|attachment|extra|other)\]/);
            var multiple = (name.search(/\[answer\]\[[0-9]*\]$/) != -1) ? true : false;
            var topic = parseInt(matches[1]);
            var question = parseInt(matches[2]);

            var extra = false;
            var other = false;
            if (tag == 'textarea' || tag == 'select') {
                type = tag;
            }
            else if (name.indexOf("attachment") != -1) {
                type = 'file';
            }
            else if (name.indexOf("extra") != -1) {
                extra = true;
            }
            else if (name.indexOf("other") != -1) {
                other = true;
            }

            var v = false;
            switch (type) {
                case 'radio':
                case 'checkbox':
                    if ($j(e).prop('checked')) {
                        v = true;
                    }
                    break;
                default:
                case 'textarea':
                case 'select':
                case 'hidden':
                case 'text':
                case 'file':
                    if ($j(e).val().length > 0) {
                        v = true;
                    }
                    break;
            }

            if (answers[topic] == undefined) {
                answers[topic] = {};
            }
            if (answers[topic][question] == undefined) {
                answers[topic][question] = {};
            }

            if (!extra) {
                if (other) {
                    var sibling = $j(e).siblings('input');
                    if (sibling
                        && sibling.attr('name').indexOf("answer") != -1
                        && sibling.prop('checked')
                        && (sibling.attr('type') == 'checkbox' || sibling.attr('type') == 'radio')) {
                        answers[topic][question][index - 1] = ($j(e).val().length > 0) ? true : false;
                    }
                }
                else if (multiple) {
                    answers[topic][question][index] = v;
                }
                else {
                    answers[topic][question][0] = answers[topic][question][0] || v;
                }
            }
        }
    });

    //check for cloze erase and highlight test
    $j('.answer_cloze_erase_test, .answer_cloze_highlight_test').each(function (index, e) {
        var topic = $j(e).closest('[id^="liTopic"]').attr('id').replace('liTopic', '');
        topic = parseInt(topic);
        if (answers[topic] == undefined) {
            answers[topic] = {};
        }

        var question = $j(e).closest('[id^="liQuestion"]').attr('id').replace('liQuestion', '');
        question = parseInt(question);
        if (answers[topic][question] == undefined) {
            answers[topic][question] = {};
        }

        var array = $j(e).find('[name^="question["]');
        var v = true;
        if (array.length === 0) {
            v = false;
        }
        answers[topic][question][0] = v;
    });

    //check for cloze slot test
    $j('.answer_cloze_slot_test').each(function (index, e) {
        var topic = $j(e).closest('[id^="liTopic"]').attr('id').replace('liTopic', '');
        topic = parseInt(topic);
        if (answers[topic] == undefined) {
            answers[topic] = {};
        }

        var question = $j(e).closest('[id^="liQuestion"]').attr('id').replace('liQuestion', '');
        question = parseInt(question);
        if (answers[topic][question] == undefined) {
            answers[topic][question] = {};
        }

        var array = $j(e).find('.dragdropBox .draggable');
        var v = false;
        if (array.length === 0) {
            v = true;
        }
        answers[topic][question][0] = v;
    });

    res = true;
    for (var t in answers) {
        for (var q in answers[t]) {
            var r = false;
            for (var a in answers[t][q]) {
                r = r || answers[t][q][a];
            }
            res = res && r;
        }
    }

    $j('#confirm, #redo').addClass('disabled').prop("disabled",true);

    if (!res) {
        res = confirm(confirmEmptyAnswers);
    }

    if (res) {
        if (window.detachEvent) {
            window.detachEvent('beforeunload', closeTest);
        } else if (window.removeEventListener) {
            window.removeEventListener('beforeunload', closeTest);
        } else {
            document.removeEventListener('beforeunload', closeTest);
        }
        await closeTest();
        formObj.submit();
    } else {
        $j('#confirm, #redo').removeClass('disabled').prop("disabled",false);
    }
}

onDOMLoaded(() => {
    domReady = true;
});
