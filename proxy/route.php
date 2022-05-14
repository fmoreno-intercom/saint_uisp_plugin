<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\TwigMiddleware;

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app));
$url = $_SERVER['PHP_SELF'];
$url = preg_replace('(\/public.php)','',$url);
$app->setBasePath($url);
// Routes
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("DEMO");
    return $response;
});
$app->any('/amoCRM.php', function (Request $request, Response $response, $args) {
        //$variable_query["leads"]
    //$variable_query["account"]
    //$variable_query["leads"]["add"]
    /*if (isset($variable_query["leads"]) || isset($variable_query["account"])) {
        $page = "amoCRM";
    }*/
    $methods = $request->getMethod();
    switch($methods){
        case 'GET':
            $variable_query = $request->getQueryParams();
            break;
        case 'POST':
            $variable_query = $request->getParsedBody();
            break;
    }
    $keys = array_keys($variable_query);
    switch ($keys[0]) {
        case 'leads':
            break;
        case 'accounts':
            break;
        case 'talk':
            break;
        case 'message':
            break;
    }
    $response->getBody()->write("DEMO");
    return $response;
});

$app->any('/public.php', function (Request $request, Response $response, $args) {
    $development = true;
    $opciones = new myRoute_Twig($this, $development);
    $tableFunc = $this->get('table_functions');
    $methods = $request->getMethod();
    switch($methods){
        case 'GET':
            $variable_query = $request->getQueryParams();
            break;
        case 'POST':
            $variable_query = $request->getParsedBody();
            break;
    }
    $page = (isset($variable_query['page'])) ? $variable_query['page'] : -1;
    $mes = (isset($variable_query['Mes'])) ? $variable_query['Mes'] : date("m");
    $security_view = $opciones->check_security();
    $security_edit = $opciones->check_security('EDIT');
    $security_sync = $opciones->check_security('SYNC');
    if ($security_view == false) {
        if (! headers_sent()) {
            header("HTTP/1.1 403 Forbidden");
        } 
        die('Lo siento, no tiene permiso para acceder a esta pagina');
    } 

    switch ($page) {
        case 'ClienteJson':
            $telefono = (isset($variable_query['telefono'])) ? $variable_query['telefono'] : -1;
            $uisp_crm = $this->get('ubnt_crm');
            $Cliente = $uisp_crm->get_client_uisp($telefono);
            $myTwig = [
                'Template' => 'webhook/ClienteJson.twig',
                'Template_Variable' => [
                    'Cliente' => $Cliente
                ]
            ];
            break;
        case 'FacturaMesCurso':
            $myTwig = $opciones->FacturaMesCurso($mes);
            break;
        case 'SyncFac':
            $myTwig = $opciones->SyncFac();
            $response->getBody()->write($myTwig);
            break;
        case 'Normalize':
            $myTwig = $opciones->NomalizeUisp();
            $response->getBody()->write($myTwig);
            break;
        case 'ReporteClienteFechaCorte':
            $myTwig = $opciones->ClienteFechaCorte($mes, $variable_query['title']);
            break;
        // Respuesta tipo JSON
        case 'ReporteClienteFechaCorte2':
            $result = $opciones->ClienteFechaCorte($mes);
            $header = $result["Template_Variable"]["table"]["header"]['columns'];
            $data = $result["Template_Variable"]["table"]["data"];
            $opciones_table  = $result["Template_Variable"]["table"]["options"];
            $myTwig = $opciones->PrepareTableJson($header, 'data', $data, $opciones_table);
            break;
        case 'ReporteClientesConexion':
            $result = $opciones->ReporteClientesConexion();
            $header = $result["Template_Variable"]["table"]["header"]['columns'];
            $data = [
                'FIBRA' => $result["Template_Variable"]["FIBRA"],
                'WISP' => $result["Template_Variable"]["WISP"]
            ];
            $myTwig = $opciones->PrepareTableJson($header, 'multiplerows', $data);
            break;
        case 'ReporteClientesIPTV':
            $myTwig = $opciones->ReporteClientesIPTV();
            break;
        case 'ReporteClienteExportar':
            $result = $opciones->PrepareQuote();
            if ($page == 'ReporteClienteExportar') {
                $table_report = $opciones->RPT_ClienteExport($result["ClienteExportar"]);
            }
            $header = $table_report["Template_Variable"]["table"]["header"]['columns'];
            $data = $table_report["Template_Variable"]["table"]["data"];
            $opciones_table  = $table_report["Template_Variable"]["table"]["options"];
            $myTwig = $opciones->PrepareTableJson($header, 'data', $data, $opciones_table);
            break;
        case 'Test':
             $columntest = [
                 'columns' => [
                     0 => [
                         'field' => 'id',
                         'title' => 'ID',
                     ],
                     1 => [
                         'field' => 'name',
                         'title' => 'Name',
                     ],
                 ]
            ];
            $t = '{"columns":[{"field":"id","title":"ID"},{"field":"name","title":"Name"}]}';
            $c = $columntest;
            $myTwig = [
                'Template' => 'Reports/ReportJson.twig',
                'Template_Variable' => [
                    'header' => $c
                ]
            ];
            break;
        default:
            $myTwig = [
                'Template' => 'home/index.twig',
                'Template_Variable' => isset($variable_query['user']) ? [ 'name' =>  $variable_query['user']] : null
            ];
    }
    if (isset($myTwig['Template'])) {
        $myTwig['Template_Variable']['Security'] = $_SESSION['security'];
        $myTemplate = $myTwig['Template'];
        $myVariable = isset($myTwig['Template_Variable']) ? $myTwig['Template_Variable'] : [];
        return $this->get('view')->render($response, $myTemplate, $myVariable);    
    } else {
        $values_body = $response->getBody();
        $final = ($values_body->getsize() != null) ? $response : $response->getBody()->write("Sin Repuesta");
        return $final;
    }
});

            /*$myTwig['Template_Variable']['table'] = [
                'title' => '',
                'id_table' => 'table_base',
                'search' => false,
                'show_columns' => false,
                'sort' => false,
                'export' => false
            ];
            if ($development){
                $myTwig['Template_Variable']['table']['header'] = [
                    0 => [
                        'H_Name' => 'id',
                        'H_Title' => '#',
                        'H_Sort' => true,
                        'H_Show' => false,
                    ],
                    1 => [
                        'H_Name' => 'Column1',
                        'H_Title' => 'Column 1',
                        'H_Sort' => true,
                        'H_Show' => true,
                    ],
                    2 => [
                        'H_Name' => 'Column2',
                        'H_Title' => 'Column 2',
                        'H_Sort' => true,
                        'H_Show' => true,
                    ],
                ];
                $myTwig['Template_Variable']['table']['data'] = [
                    0 => [
                        'Column1' => 'Test 1',
                        'Column2' => 'Column 2',
                    ],
                    1 => [
                        'Column1' => 'Column 3',
                        'Column2' => 'Column 4',
                    ],
                ];
            }*/