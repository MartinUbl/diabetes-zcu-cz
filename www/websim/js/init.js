
var bcChannel;

function beforeUnloadHandler() {
    setTerminatingFlag(true);
    bcChannel.close();
    _terminate();
}

// to properly synchronize WASM worker initialization and document.ready event (which are, in their nature, asynchronous)
var initCD = 2;
function countDownInit() {
    initCD--;
    if (initCD === 0) {
        $('#disclaimer').html(UTF8ToString(_get_warning_disclaimer()));
        $('#disclaimer-parent').show();
        initPatientMap();

        $('#initial-loader').hide();
    }
}

addOnPostRun(function() {
    countDownInit();
});

$(document).ready(function() {

    $('.sc-button').prop('disabled', true);

    window.onbeforeunload = beforeUnloadHandler;

    bcChannel = new BroadcastChannel('SCGMS_Event_Channel');
    bcChannel.onmessage = (event) => {
        //console.log(JSON.parse(event.data));

        try {
            var parsed = JSON.parse(event.data);
            if (parsed.type === "event") {
                valueCallback(parsed.data_device_time, parsed.data_level, parsed.data_signal_id);
            }
            else if (parsed.type === "time_bounds") {
                timeBoundsCallback(parsed.data_start, parsed.data_end);
                receiveScenario(parsed.scenario);
            }
        } catch (e) {
            console.error(e);
        }

    };

    $('.meal-input').on('keyup', function(evt) {

        var val = evt.target.value;
        let content = parseFloat(val) || null;
        if (val.length > 0 && (content === null || content < 0)) {
            $(evt.target).addClass('input-error');
        }
        else {
            $(evt.target).removeClass('input-error');
        }

        var allValid = true;
        $('.meal-input').each(function() {
            if ($(this).hasClass('input-error')) {
                allValid = false;
                return false;
            }
        });

        $('#meal-save').prop('disabled', !allValid);
    });

    countDownInit();
});
