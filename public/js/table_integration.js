function CalculateBottom(object_measure = 'main') {
    var measure = $(object_measure);
    windowHeight = $(window).height();
    scrollDistance = $(window).scrollTop();
    divOffsetTop = measure.offset().top;
    delta = Math.abs(divOffsetTop - (scrollDistance + windowHeight));
    return delta;
}
function ajaxRequest(params) {
    if (!$('#mytable').hasClass('d-none')) {
        $('#mytable').toggleClass('d-none');
        $('#nav_table').toggleClass('d-none');
    }
    if ($('#myLoading').hasClass('d-none')) {
        $('#myLoading').toggleClass('d-none');
    }
    //$('#myLoading').html(params.templateLoading);
    
    const table = $('#table_base');
    table.bootstrapTable("destroy");
    // data you may need
    $.ajax({
        type: "POST",
        url: "public.php",
        data: "page="+params.functions+'&title='+params.title+'&table_id='+params.table_id,
        //data: "page=Test&title="+params.title,
        // You are expected to receive the generated JSON (json_encode($data))
        success: function (data) {
            data = JSON.parse(data);
            data['height'] = CalculateBottom();
            //data['height'] = 'auto';
            $('#myLoading').toggleClass('d-none');
            $('#nav_table').toggleClass('d-none');
            $('#mytable').toggleClass('d-none');
            MyData(table, data, params);
        },
        error: function (er) {
            alert("error");
            $('tbody').html(params.tbody);
            //params.error(er);
            console.log(er);
        }
    });
}
function MyData(table, data, params) {
    let myrows = [];
    let mycolumns = [];
    if ('data' in data) {
        myrows = data['data'];
        delete data['data']
    }
    if ('multiplerows' in data) {
        myrows = data['multiplerows'];
        delete data['multiplerows']
    }
    if ('columns' in data) {
        mycolumns = data['columns'];
        delete data['columns']
    }
    switch (params.title) {
        case 'Clientes por Tipo Conexión':    
            buildSubTable(table, mycolumns, myrows, params.title);
            break;
        default:
            CreateTable(table, mycolumns, myrows, params.title, data);
            break;
    }   
}
function PrepareDataTable(json_data, mytitle = '', id_table='#table_base', nav_table_id = '#nav_table_title') {
    const $mytable = $(id_table);
    $(nav_table_id).html('<span>'+mytitle+'</span>');
    var dataset = document.querySelector(id_table).dataset;
    const mydataset = Object.keys(dataset).map((key) => [key, dataset[key]]);
    const cant_dataset = Object.keys(dataset).length;
    //console.log(json_data);
    // Cambiar Valores de Dataset
    if (cant_dataset > 0) {
        // Buscar los valores del Dataset en el Json y los agrega si no estan
        mydataset.forEach(function(name, index, arr) {
            let key = arr[index][0];
            if (!(key in json_data)) {
                json_data[key] = arr[index][1];
            }
        });
    }
    const mydata = Object.keys(json_data).map((key) => [key, json_data[key]]);
    const cant_mydata = Object.keys(json_data).length;
    if (cant_mydata > 0) {
        mydata.forEach(function(name, index, arr) {
            let key = arr[index][0];
            let removethis = false;
            switch(key) {
                case "toolbar":
                case "toggle":
                    //console.log("Valores para table:" + key + ":" + arr[index][1]);
                    delete json_data[key];
                    break;
                case "search":
                    //console.log("Valores para ambos:" + key + ":" + arr[index][1]);
                    dataset[key] = arr[index][1];
                    break;
                case "height":
                    //console.log("Valores para json:" + key + ":" + arr[index][1]);
                    break;
                default:
                    dataset[key] = arr[index][1];
                    delete json_data[key];
            } 
        });
    }
    //console.log(json_data);
    //console.log(dataset);
    return json_data;
}
function CreateTable(id_table, columns, data, title, options = null, table_name = null) {
    var finaldata;
    if (options != null && Object.keys(options).length > 0) {
        finaldata = (table_name === null) ? PrepareDataTable(options, title) : PrepareDataTable(options, title, table_name);
    } else {
        console.log("No options");
    }
    $table = (typeof(id_table) != 'object') ? $('#' + id_table) : id_table;
    myid_table = $table.attr('id');
    
    // DEBUG PAR DATA EN TABLE
    //var dataset = document.querySelector('#' + myid_table).dataset;
    //console.log(dataset);
    // CARGA INFO EN TABLE
    finaldata['columns'] = columns;
    finaldata['data'] = data;
    console.log(finaldata);
    $table.bootstrapTable('destroy').bootstrapTable(finaldata);
}
function buildSubTable(id_table, columns, data, title) {
    var $table = $(id_table);
    var columnsSubtable = [
        {
            field: 'mysearch',
            title: 'Detalle',
            sortable: true
        }
    ];
    var dataSubtable = [];

    subrows = Object.keys(data).map((key) => [key, data[key]]);  
    subrows.forEach(function(name, index, arr) {
        row = {};
        row['mysearch'] = arr[index][0];
        dataSubtable.push(row);
    });
    
    const myoptions = {
        'virtualScroll': true,
        'showMultiSort': false,
        'showExport': false,
        'height': CalculateBottom(),
        'search': false,
    };
    finaldata = PrepareDataTable(myoptions, title);
    finaldata['columns'] = columnsSubtable;
    finaldata['data'] = dataSubtable;
    finaldata['detailView'] = true;
    finaldata['onExpandRow'] = function(index, row, $detail) {
        dataSubRow = subrows[index]; // INFORMACION DETALLADA
        subtable_name = 'subtable_' + dataSubRow[0];
        expandTable($detail, subtable_name, columns, dataSubRow);
    }
    $table.bootstrapTable('destroy').bootstrapTable(finaldata);
}
function expandTable($detail, subtable_name, columns, data) {
    const myoptions = {
        'virtualScroll': true,
        'height': CalculateBottom() / 1.5,
        'search': true,
        'showExport': true

    };
    $detail.html("<div id='nav_" + subtable_name + "'></div><table id='" + subtable_name + "'></table>");
    CreateTable(subtable_name, columns, data[1], data[0], myoptions, '#' + subtable_name, '#nav_' + subtable_name);
}