$(document).ready(function () {

    // Search lists
    var options = {
        valueNames: [ 'mousse_name', 'mousse_description' ]
    };
    var generalList = new List('general', options);
    var moiList = new List('moi', options);


    // Toogle attendance
    $('.switch-people-attendance').change(function() {
        $.get("/post-appel?id="+$(this).attr('people')+"&attendance="+$(this).is(":checked"));
    });

    // Fucking iOS hack
    // http://stackoverflow.com/questions/2898740/iphone-safari-web-app-opens-links-in-new-window
    $(document).on('click', 'a', function(event) {
        var href = $(this).attr("href");
        // Pas pour les "faux" liens, ni pour les liens externes
        if (href.substr(0, 1) != '#' && href.substr(0, 4) != 'http') {
            event.preventDefault();
            window.location = href;
        }
    });
});