var DATE = null;

function pad(n) {
    return (n < 10) ? '0' + n : n;
}

function updateClock(timestamp) {

    if($j('#js_clock').length > 0) {
        var milliseconds = timestamp * 1000;
        DATE = new Date(milliseconds);

        new PeriodicalExecuter(function(pe) {
            DATE.setSeconds(DATE.getSeconds() + 1);
            $j('#js_clock').html(
                `${pad(DATE.getHours())}:${pad(DATE.getMinutes())}:${pad(DATE.getSeconds())}`
            );
        }, 1);
    }

}