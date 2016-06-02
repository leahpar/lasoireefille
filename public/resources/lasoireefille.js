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
    var a=document.getElementsByTagName("a");
    for(var i=0;i<a.length;i++)
    {
        a[i].onclick=function()
        {
            window.location=this.getAttribute("href");
            return false
        }
    }

});