
var patientBuilders = {};

var selectedPatient = null;

var storedStartDate;

function initPatientMap() {
    patientBuilders = {
        "prediabetes": {
            "name": "Prediabetes",
            "patients": [
                {
                    "name": "#001",
                    "patient_id": 0,
                    "builder": _build_filter_chain_prediabetes
                },
                {
                    "name": "#002",
                    "patient_id": 1,
                    "builder": _build_filter_chain_prediabetes
                }
            ],
        },
        "type1": {
            "name": "Type 1 Diabetes",
            "patients": [
                /*
                {
                    "name": "#001",
                    "patient_id": 0,
                    "builder": _build_filter_chain_type1
                }
                */
            ]
        }
    };

    var target = $('#patient_list');

    for (var diagnosis in patientBuilders) {

        var block = $('<div/>', {
            'class': 'diagnosis-block col-6 justify-items-center text-center'
        });

        block.append($('<h4/>', {
            'class': '',
            'text': patientBuilders[diagnosis].name
        }));

        if (patientBuilders[diagnosis].patients.length === 0) {
            block.append($('<p/>', {
                'class': 'text-muted',
                'text': 'Coming soon'
            }));
        }
        else {
            for (let i in patientBuilders[diagnosis].patients) {
                let patient = patientBuilders[diagnosis].patients[i];
                let li = $('<a/>', {
                    'class': 'btn btn-primary btn-patient',
                    'html': '<i class="fa-solid fa-person"></i>&nbsp;' + patient.name,
                    'data-diagnosis': diagnosis,
                    'data-index': i,
                    'data-patient-id': patient.patient_id
                });

                li.click(function() {
                    var patient = patientBuilders[$(li).data('diagnosis')].patients[$(li).data('index')];
                    switchToPhase2(patient);
                });

                block.append(li);
            }
        }

        target.append(block);
    }

    $('#patient-list-parent').show();
}

const PlotIndices = {
    IG: 1,
    Heartbeat: 2,
    CHO: 3,
    Proteins: 4,
    Fats: 5,
    Bolus: 6,
    Basal: 7
};

var plotData = [
    [],
    [],
    [],
    [],
    [],
    [],
    [],
    []
];
var uplot = null;

function switchToPhase2(patient) {
    selectedPatient = patient;

    $('#phase_1').hide();
    $('#phase_2').show();

    patient.builder(patient.patient_id, true);
}

var lastTime = 0;

var isTerminating = false;

function setTerminatingFlag(flag) {
    isTerminating = flag;
}

var scenario = [
    // will be filled with the scenario data once the callback fires
];

var currentScenarioIndex = 0;

// convert D HH:MM:SS to seconds
function parseTime(time) {
    let parts = time.split(" ");
    let days = (parts.length > 0) ? parseInt(parts[0]) : 0;
    let timeParts = parts[1].split(":");
    let hours = parseInt(timeParts[0]);
    let minutes = parseInt(timeParts[1]);
    let seconds = parseInt(timeParts[2]);

    return days * 24 * 60 * 60 + hours * 60 * 60 + minutes * 60 + seconds;
}

// convert seconds to D HH:MM:SS
function formatTimeToBackend(time) {
    let days = Math.floor(time / (24 * 60 * 60));
    time = time % (24 * 60 * 60);

    let hours = Math.floor(time / (60 * 60));
    time = time % (60 * 60);

    let minutes = Math.floor(time / 60);
    let seconds = time % 60;

    return `${days} ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

var lastStepTimestamp = 0;

function send_level_event_proxy(signal, timestamp, value) {
    //console.log("Send event", signal, timestamp, value);
    _send_level_event(stringToNewUTF8(signal), stringToNewUTF8(timestamp), value);
}

function makeStep() {
    let current = scenario[currentScenarioIndex];

    if (current.type === "meal") {
        if (current.carbs > 0) {
            send_level_event_proxy(Signals.Meal_CHO, formatTimeToBackend(current.time), current.carbs);
        }
        if (current.proteins > 0) {
            send_level_event_proxy(Signals.Meal_Proteins, formatTimeToBackend(current.time), current.proteins);
        }
        if (current.fats > 0) {
            send_level_event_proxy(Signals.Meal_Fats, formatTimeToBackend(current.time), current.fats);
        }
    }
    else if (current.type === "heartbeat") {
        send_level_event_proxy(Signals.Heartbeat, formatTimeToBackend(current.time), current.bpm);
    }
    else if (current.type === "bolus") {
        send_level_event_proxy(Signals.RequestedBolus, formatTimeToBackend(current.time), current.insulin);
    }
    else if (current.type === "basal") {
        send_level_event_proxy(Signals.RequestedBasal, formatTimeToBackend(current.time), current.insulin);
    }
    else if (current.type === "synchronization") {
        send_level_event_proxy(Signals.Synchronization, formatTimeToBackend(current.time), 0);
    }

    lastStepTimestamp = formatTimeToBackend(current.time);

    currentScenarioIndex++;
}

function makeSteps(cnt) {
    for (let i = 0; i < cnt; i++) {
        makeStep();
    }

    $('#meal-cho').val('');
    $('#meal-proteins').val('');
    $('#meal-fats').val('');
}

function stepUntil(type) {

    $('#simloader').removeClass('hidden');

    makeStep();

    while (currentScenarioIndex < scenario.length) {
        if (scenario[currentScenarioIndex].type === type) {
            break;
        }

        makeStep();
    }

    if (currentScenarioIndex < scenario.length && type === "meal") {
        $('#meal-cho').val(scenario[currentScenarioIndex].carbs);
        $('#meal-proteins').val(scenario[currentScenarioIndex].proteins);
        $('#meal-fats').val(scenario[currentScenarioIndex].fats);
    }
    else {
        $('#meal-cho').val('');
        $('#meal-proteins').val('');
        $('#meal-fats').val('');
    }
}

function saveMeal() {
    let carbs = parseFloat($('#meal-cho').val()) || 0;
    let proteins = parseFloat($('#meal-proteins').val()) || 0;
    let fats = parseFloat($('#meal-fats').val()) || 0;

    if (scenario[currentScenarioIndex].type !== "meal") {
        if (carbs > 0 || proteins > 0 || fats > 0) {
            scenario.splice(currentScenarioIndex, 0, {
                time: scenario[currentScenarioIndex].time,
                type: "meal",
                carbs: carbs,
                proteins: proteins,
                fats: fats
            });
        }
    }
    else {
        scenario[currentScenarioIndex].carbs = carbs;
        scenario[currentScenarioIndex].proteins = proteins;
        scenario[currentScenarioIndex].fats = fats;
    }

    $('#meal-save').prop('disabled', true);
}

function stepForTime(secs) {
    
    $('#simloader').removeClass('hidden');

    let targetTime = scenario[currentScenarioIndex].time + secs;

    while (currentScenarioIndex < scenario.length) {
        if (scenario[currentScenarioIndex].time >= targetTime) {
            break;
        }

        makeStep();
    }

    $('#meal-cho').val('');
    $('#meal-proteins').val('');
    $('#meal-fats').val('');
}

function receiveScenario(data) {

    var has = {
        meal_proteins: false,
        meal_fats: false,
        heartbeat: false,
        insulin: false
    };

    for (let i = 0; i < data.length; i++) {

        let values = data[i];

        // scheduled meal: [carbs, proteins, fats]
        if (values.signal == Signals.Meal_CHO || values.signal == Signals.Meal_Proteins || values.signal == Signals.Meal_Fats) {
            for (let j = 0; j < values.data.length; j++) {
                let meal = values.data[j];

                scenario.push({
                    time: parseTime(meal.timestamp),
                    type: "meal",
                    carbs: meal.level[0],
                    proteins: meal.level[1],
                    fats: meal.level[2]
                });

                has.meal_proteins = has.meal_proteins || meal.level[1] > 0;
                has.meal_fats = has.meal_fats || meal.level[2] > 0;
            }
        }
        // heartbeat
        else if (values.signal == Signals.Heartbeat) {
            for (let j = 0; j < values.data.length; j++) {
                let hb = values.data[j];

                scenario.push({
                    time: parseTime(hb.timestamp),
                    type: "heartbeat",
                    bpm: hb.level[0]
                });

                has.heartbeat = true;
            }
        }
        // bolus
        else if (values.signal == Signals.BolusInsulin) {
            for (let j = 0; j < values.data.length; j++) {
                let bolus = values.data[j];

                scenario.push({
                    time: parseTime(bolus.timestamp),
                    type: "bolus",
                    insulin: bolus.level[0]
                });

                has.insulin = true;
            }
        }
        // basal
        else if (values.signal == Signals.BasalInsulin) {
            for (let j = 0; j < values.data.length; j++) {
                let basal = values.data[j];

                scenario.push({
                    time: parseTime(basal.timestamp),
                    type: "basal",
                    insulin: basal.level[0]
                });

                has.insulin = true;
            }
        }
    }

    // sort scenario by time - ascending
    scenario.sort((a, b) => a.time - b.time);

    // fill in the gaps, where gap is defined as a time difference greater than 1 minute; the gaps will send a sync signal
    for (let i = 1; i < scenario.length; i++) {
        let diff = scenario[i].time - scenario[i - 1].time;

        if (diff > 60) {
            let gap = scenario[i - 1].time + 60;

            scenario.splice(i, 0, {
                time: gap,
                type: "synchronization"
            });
        }
    }

    if (!has.meal_proteins) {
        $('#row-meal-proteins').hide();
        $('#meal-proteins').val('0');
        $('#meal-proteins').prop('disabled', true);
    }
    else {
        $('#row-meal-proteins').show();
        $('#meal-proteins').prop('disabled', false);
    }

    if (!has.meal_fats) {
        $('#row-meal-fats').hide();
        $('#meal-fats').val('0');
        $('#meal-fats').prop('disabled', true);
    }
    else {
        $('#row-meal-fats').show();
        $('#meal-fats').prop('disabled', false);
    }
    
    if (!has.heartbeat) {
        $('#row-heartrate').hide();
    }
    else {
        $('#row-heartrate').show();
    }

    if (!has.insulin) {
        $('#row-insulin-bolus').hide();
        $('#row-insulin-basal').hide();
    }
    else {
        $('#row-insulin-bolus').show();
        $('#row-insulin-basal').show();
    }
}

function valueCallback(timestamp, value, signalId) {

    let recTimestamp = parseTime(timestamp);
    let recLastStepTimestamp = parseTime(lastStepTimestamp);

    if (recTimestamp >= recLastStepTimestamp - 5) {
        $('#simloader').addClass('hidden');
    }

    if (isTerminating) {
        return;
    }

    var days = 0;

    var srctimePart;
    var dparts = timestamp.split(" ");
    if (dparts.length < 2) {
        srctimePart = timestamp;
    }
    else {
        days = parseInt(dparts[0]);

        srctimePart = dparts[1];
    }

    // the timestamp is a string HH:MM:SS, parse it to three numbers
    var parts = srctimePart.split(":");

    var H = parseInt(parts[0]);
    var M = parseInt(parts[1]);
    var S = parseInt(parts[2]);

    $('#state_current_time').text(`${daysOfWeek[days % 7]} ${H}:${M.toString().padStart(2, '0')}`);

    if (lastTime === 0) {
        lastTime = new Date();
        lastTime.setHours(H);
        lastTime.setMinutes(M);
        lastTime.setSeconds(S);
    }

    // if the hour is less than the last hour, it means we are in the next day
    if (H < lastTime.getHours()) {
        lastTime.setDate(lastTime.getDate() + 1);
        lastTime.setHours(H);
        lastTime.setMinutes(M);
        lastTime.setSeconds(S);
    }
    else {
        lastTime.setHours(H);
        lastTime.setMinutes(M);
        lastTime.setSeconds(S);
    }

    var addSignal = function(time, index, value) {
        if (plotData[0].length > 0 && plotData[0][plotData[0].length - 1] === time.getTime() && plotData[index].length > 0 && plotData[index][plotData[index].length - 1] === null) {
            plotData[index][plotData[index].length - 1] = value;
        }
        else {
            plotData[0].push(time.getTime());

            for (let i = 1; i < plotData.length; i++) {
                if (i == index) {
                    plotData[i].push(value);
                }
                else {

                    if (i == 3 || i == 4 || i == 5 || plotData[i].length == 0) {
                        plotData[i].push(null);
                    }
                    else {
                        plotData[i].push(plotData[i][plotData[i].length - 1]);
                    }
                }
            }
        }
    };

    // IG
    if (signalId === Signals.IG) {

        $('#state_current_ig').text(value.toFixed(1));

        addSignal(lastTime, PlotIndices.IG, value);
    }
    // Bolus insulin
    else if (signalId === Signals.BolusInsulin) {

        $('#state_lastbolus').text(value.toFixed(1));

        addSignal(lastTime, PlotIndices.Bolus, value);

    }
    // Basal insulin
    else if (signalId === Signals.BasalInsulin) {

        $('#state_lastbasal').text(value.toFixed(1));

        addSignal(lastTime, PlotIndices.Basal, value);
    }
    // Carbohydrate content (meal)
    else if (signalId == Signals.Meal_CHO) {
        
        $('#state_lastmeal_cho').text(value.toFixed(1));

        addSignal(lastTime, PlotIndices.CHO, value);
    }
    // Protein content (meal)
    else if (signalId == Signals.Meal_Proteins) {

        $('#state_lastmeal_proteins').text(value.toFixed(1));

        addSignal(lastTime, PlotIndices.Proteins, value);
    }
    // Fat content (meal)
    else if (signalId == Signals.Meal_Fats) {

        $('#state_lastmeal_fats').text(value.toFixed(1));

        addSignal(lastTime, PlotIndices.Fats, value);
    }
    // BG (unused)
    else if (signalId == Signals.BG) {
        return;
    }
    // Heartbeat
    else if (signalId == Signals.Heartbeat) {

        $('#state_lastheartrate').text(value.toFixed(0));

        // if the last time is the same as current time, and there is not a value for index 2
        // it means we are updating the last value

        addSignal(lastTime, PlotIndices.Heartbeat, value);
    }
    // Synchronization
    else if (signalId == Signals.Synchronization) {
        // ignored
    }
    else {
        console.error("Unknown signalId", signalId, value);
        return;
    }

    var test = new Date().getTime();

    var xplotData = [
        [test, test+10000000, test+20000000, test+30000000, test+40000000, test+50000000, test+60000000, test+70000000, test+80000000, test+90000000],
        [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
        [100, 90, 80, 70, 60, 50, 40, 30, 20, 10],
        [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
        [100, 90, 80, 70, 60, 50, 40, 30, 20, 10],
        [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
        [10, 20, 30, 40, 50, 60, 70, 80, 90, 100],
        [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]
    ];

    uplot.setData(plotData);
}

const daysOfWeek = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];

function formatTime(epochSeconds) {
    let date = new Date(epochSeconds);

    let days = Math.floor((date - storedStartDate) / (1000 * 60 * 60 * 24));

    return `${daysOfWeek[days % 7]} ${date.toLocaleTimeString("en-GB", { hour: '2-digit', minute: '2-digit', hour12: false })}`; // D HH:MM:SS format
}

function timeBoundsCallback(start, end) {
    $('#simulation-init').hide();

    var w = $('#plot').innerWidth();
    var h = $('#plot').innerHeight();

    var startDate = new Date();
    var startTmParts = start.split(" ");

    var startParts = startTmParts[1].split(":");
    startDate.setHours(parseInt(startParts[0]));
    //startDate.setMinutes(0);
    startDate.setMinutes(parseInt(startParts[1]));
    //startDate.setSeconds(0);
    startDate.setSeconds(parseInt(startParts[2]));

    storedStartDate = startDate;

    var endDate = new Date();
    var endParts = end.split(" ");
    var endParts2 = endParts[1].split(":");
    endDate.setDate(startDate.getDate() + parseInt(endParts[0]));
    endDate.setHours(parseInt(endParts2[0]));
    //endDate.setMinutes(0);
    endDate.setMinutes(parseInt(endParts2[1]));
    //endDate.setSeconds(0);
    endDate.setSeconds(parseInt(endParts2[2]));

    const { linear, stepped, bars, spline, spline2, offpoints } = uPlot.paths;

    const _bars_a = offpoints({
        yoffsetval: 10,
        adjacents: [null, PlotIndices.Proteins, PlotIndices.Fats],
    });
    const _bars_b = offpoints({
        yoffsetval: 10,
        adjacents: [PlotIndices.CHO, null, PlotIndices.Fats],
    });
    const _bars_c = offpoints({
        yoffsetval: 10,
        adjacents: [PlotIndices.CHO, PlotIndices.Proteins, null],
    });

    let opts = {
        id: "plot",
        class: "patient-plot",
        width: w,
        height: h,
        legend: {
            show: false
        },
        cursor: {
            show: true,
            x: true,
            y: true,
            lock: false,
        },
        scales: {
            x: {
                range: () => [startDate, endDate],
                time: true
            },
            // IG
            y1: {
                range: () => [0, 30],
                auto: true
            },
            // Heartrate
            y2: {
                range: () => [0, 300],
                auto: true
            },
            // Meals (not used as a scale)
            y3: {
                range: () => [0, 100],
                auto: true
            },
            // boluses
            y4: {
                range: () => [0, 20],
                auto: true
            },
            // basal rate
            y5: {
                range: () => [0, 50],
                auto: true
            }
        },
        axes: [
            {
                scale: "x",
                values: (u, ticks) => {
                    return ticks.map(formatTime);
                },
                splits: () => {
                    let start = startDate;
                    start.setMinutes(0);
                    start.setSeconds(0);

                    let end = endDate;

                    let diff = end - start;

                    const hourSpacing = 8;

                    let hours = Math.floor(diff / (1000 * 60 * 60));
                    let interval = Math.floor(hours / hourSpacing);

                    let newTicks = [];
                    for (let i = 0; i <= interval; i++) {
                        let newTick = new Date(start);
                        newTick.setHours(newTick.getHours() + i * hourSpacing);
                        if (newTick < end) {
                            newTicks.push(newTick.getTime());
                        }
                    }

                    return newTicks;
                }
            },
            {
                scale: "y1",
                values: (u, vals) => vals.map(v => v.toFixed(1)),
                stroke: "blue",
            },
            {
                scale: "y2",
                values: (u, vals) => vals.map(v => v.toFixed(0)),
                stroke: "red",
                grid: {show: false},
            },
            {
                scale: "y3",
                show: false,
                grid: {show: false},
            },
            {
                scale: "y4",
                show: false,
                grid: {show: false},
            },
            {
                scale: "y5",
                show: false,
                grid: {show: false},
            }
        ],
        series: [
            {},
            {
                show: true,      
                spanGaps: false,
      
                label: "Interstitial glucose",
                value: (self, rawValue) => rawValue.toFixed(1),
      
                stroke: "blue",
                width: 2 / devicePixelRatio,
                scale: "y1",
                //paths: spline(),
            },
            {
                show: true,
                spanGaps: false,
      
                label: "Heartrate",
                //value: (self, rawValue) => rawValue.toFixed(1),
      
                stroke: "rgba(255,0,0,0.2)",
                width: 1 / devicePixelRatio,
                scale: "y2",
            },
            {
                label: "CHO",
                stroke: "rgba(0, 127, 0, 0.8)",
                width: 1,
                fill: "rgba(0, 127, 0, 0.8)",
                paths: _bars_a,
                points: {show: false},
                scale: "y3",
            },
            {
                label: "Proteins",
                stroke: "rgb(192, 192, 0, 0.8)",
                width: 1,
                fill: "rgb(192, 192, 0, 0.8)",
                paths: _bars_b,
                points: {show: false},
                scale: "y3",
            },
            {
                label: "Fats",
                stroke: "rgba(0, 0, 127, 0.8)",
                width: 1,
                fill: "rgba(0, 0, 127, 0.8)",
                paths: _bars_c,
                points: {show: false},
                scale: "y3",
            },
            {
                label: "Insulin bolus",
                stroke: "rgba(127,0,192,0.8)",
                width: 1 / devicePixelRatio,
                points: {
                    fill: "magenta",
                },
                scale: "y4",
            },
            {
                label: "Insulin basal rate",
                stroke: "rgba(0,127,127,0.8)",
                width: 1 / devicePixelRatio,
                paths: stepped({align: 1}),
                scale: "y5",
            },
        ],
    };
      
    uplot = new uPlot(opts, plotData, $('#plot')[0]);

    selectedPatient.builder(selectedPatient.patient_id, false);

    $('.sc-button').prop('disabled', false);
}
