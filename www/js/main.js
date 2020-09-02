
$(function () {
    //$.nette.init();
});

$(document).ready(function() {
    $("body").tooltip({ selector: '[data-toggle=tooltip]' });
    
    setTimeout(function() {
        $('.flash').addClass('fadeout');
        
        setTimeout(function() {
            $('.flash').remove();
        }, 3000);
    }, 1000);
});
