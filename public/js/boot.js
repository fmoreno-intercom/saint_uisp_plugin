function loadingTemplate(message) {
    return '<i class="fa fa-spinner fa-spin fa-fw fa-2x"></i>'
}
function ReloadTable(button) {
    button = $(event.currentTarget);
    var myfunction = button.data('offset');
    var template = "<tr>"+loadingTemplate()+"</tr>";
    const params = new Object();
    params.functions = myfunction;
    params.templateLoading = template;
    params.tbody = $('tbody').html();
    params.title = button.html()
    params.tableid = $('table').attr('id');
    //$('#nav_table_title span').html();
    ajaxRequest(params);
}

$(document).ready(function () {
    $('a[data-offset]').on('click', function(event) {
        ReloadTable(event);
    });
});