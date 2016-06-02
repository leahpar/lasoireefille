$(document).ready(function () {

    // Search lists
    var options = {
        valueNames: [ 'mousse_name', 'mousse_description' ]
    };
    var generalList = new List('general', options);
    var moiList = new List('moi', options);


    // Toogle attendance
    $('.switch-people-attendance').change(function() {
        $.get("/attendance?id="+$(this).attr('people')+"&attendance="+$(this).is(":checked"));
    });

});