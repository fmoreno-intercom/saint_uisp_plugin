<?php
class myRoute_Twig {
    private $app;
    private $conex_admin;
    private $conex_contab;
    private $active_uisp_crm;

    function __construct($app) {
        $this->app = $app;
    }

    function conex_db($type) {
        switch ($type) {
            case "admin":
                if (!isset($this->conex_admin)) {
                    $this->conex_admin = $this->app->get('saint_db');
                }
                return $this->conex_admin;
                break;
        }
    }

    function check_uisp_crm() {

    }
    function PrepareTableJson($myheader, $typedata, $mydata, $myopciones = null) {
        $header = (is_array($myheader)) ? $myheader : null;
        if (is_array($mydata)) {
            $data = $mydata;
        }
        $opciones_table  = (!is_null($myopciones) && is_array($myopciones)) ? $myopciones : '';
        $jsonarray = array_merge(array(
            'columns' => $header, 
            $typedata => $data,
        ), $opciones_table);
        /*$jsonarray =array(
            'columns' => $header, 
            'data' => $data,
        );*/
        
        $myTwig = [
            'Template' => 'Reports/ReportJson.twig',
            'Template_Variable' => [
                'Table_JSON' => $jsonarray
            ]
        ];
        return $myTwig;
    }
    function FacturaMesCurso() {
        $myTwig = [
            'Template' => 'Reports/old/MesCurso.twig',
            'templateVariable' => null
        ];
        $saint_admin = $this->conex_db("admin");
        $FacturasTotal = $saint_admin->get_quoted_list();
        $myTwig['Template_Variable' ] = [ 'result' => $FacturasTotal];
        return $myTwig;
    }
    function ClienteFechaCorte($mes, $title = null) {
        $myTwig = [
            'Template' => 'Reports/FechaCorteCliente.twig',
            'Template_Variable' => null
        ];
        if (!isset($mes)) $mes = date("m");
        $uisp_crm = $this->app->get('ubnt_crm');
        $tableFunc = $this->app->get('table_functions');
        $Clientes = $uisp_crm->ListClientStatus();
        $ListadoFechaCorte = $uisp_crm->ClienteFechaCorte($Clientes['data']);
        $myTwig['Template_Variable']['table']['options'] = $tableFunc->TableActivate();
        $myTwig['Template_Variable']['table']['header'] = $tableFunc->CreateColumns($ListadoFechaCorte);
        $myTwig['Template_Variable']['table']['data'] = $ListadoFechaCorte;
        return $myTwig;
    }

    function ReporteClientesConexion() {
        $myTwig = [
            'Template' => 'Reports/old/ReporteClientesConexion.twig',
            //'Template' => 'Reports/test.twig',
            'Template_Variable' => null
        ];
        $uisp_crm = $this->app->get('ubnt_crm');
        $tableFunc = $this->app->get('table_functions');
        $Clientes = $uisp_crm->ListClientStatus();
        $ListadoClienteConexion = $uisp_crm->ClientesConexion($Clientes['data']);
        $myTwig['Template_Variable' ] = [ 'FIBRA' => $ListadoClienteConexion['FIBRA'], 'WISP' => $ListadoClienteConexion['WISP']];
        $myTwig['Template_Variable']['table']['header'] = $tableFunc->CreateColumns($ListadoClienteConexion['WISP']);
        //$myTwig['Template_Variable']['table']['data'] = $ListadoFechaCorte;
        return $myTwig;
    }

    function SyncFac() {
        $exportQuote = $this->PrepareQuote();
        if (count($exportQuote['ClienteExportar']) > 0) {
            // Proceder a Crear los Clientes en Saint
        }
        
        return "LISTO";
    }

    function PrepareQuote() {
        $saint_admin = $this->conex_db("admin");
        $ucrm = $this->app->get('ubnt_crm');
        $lastQuote = $saint_admin->LastQuoteSaint();
        if ($lastQuote["result"] == 1) {
            //$lastFecha = date_format(new datetime($lastQuote[0]["FechaI"]), 'd-m-Y H:i:s');
            $lastFecha = explode(" ", $lastQuote[0]["FechaI"])[0];
            $ClientesClean = $ucrm->NormalizeClient();
            $quotelist = $ucrm->QuotesList($lastFecha, null, false);
            $exportQuote = $this->CreateArrayQuote($lastQuote, $quotelist);
            return $exportQuote;
        }
        return false;
    }

    function CreateArrayQuote($lastQuote, $quotelist) {
        $myClienteFallido = [];
        $myClienteExportar = [];
        $myClienteOmitido = [];
        $myQuote = [];
        $saint_admin = $this->conex_db("admin");
        $uisp_crm = $this->app->get('ubnt_crm');
        $lastFecha = date_format(new DateTime($lastQuote[0]["FechaI"]), 'd-m-Y H:i:s');
        foreach($quotelist as $key => $value) {
            $quotecheckdate = date_format(new DateTime($value['createdDate']), 'd-m-Y H:i:s');
            if (strtotime($quotecheckdate) > strtotime($lastFecha) && isset($value['clientId'])) {
                $searchClient = $uisp_crm->findObjectById($uisp_crm->client_uisp, 'id', $value['clientId']);
                if ($searchClient != false) {
                    $CheckClien = $uisp_crm->CleanClient($searchClient);
                    $isClientSaint = $saint_admin->CheckClienteSaint($CheckClien);
                } else {
                    // Accion para Cliente no conseguido en el UISP
                    $isClientSaint = false;
                }
                if (is_array($isClientSaint) && isset($searchClient["userIdent"]) && $CheckClien[0] > 0) {
                    $ClientSaint = null;
                    switch($isClientSaint["result"]) {
                        case 0:
                            // Usuario que no estan Saint
                            $ClientSaint = $saint_admin->PrepareClientExport($searchClient);
                            $myClienteExportar = $uisp_crm->ClasificarClienteQuote($myClienteExportar, $value['clientId'], $ClientSaint);
                            break;
                        case 1:
                            // Cliente en Saint
                            $ClientSaint = $saint_admin->PrepareClientExportSaint($isClientSaint);
                            break;
                        case -3:
                            // Usuario con problemas de datos
                            $myClientOmitido= $uisp_crm->ClasificarClienteQuote($myClientOmitido, $value['clientId']);
                            break;
                    }
                    if ($isClientSaint["modificado"] == true) {                        
                        $myClienteFallido = $uisp_crm->ClasificarClienteQuote($myClienteFallido, $value['clientId']);
                    }
                    if (is_array($ClientSaint) && $isClientSaint["result"] > 0  && !is_null($ClientSaint)) {  
                        $QuoteExport = [];
                        $QuoteDetailsExport = [];
                        $myQuote[] = [
                            'cliente' => $ClientSaint,
                            'quote' => $QuoteExport,
                            'quotedetails'=> $QuoteDetailsExport
                        ];
                    }
                } else if (is_array($isClientSaint)) {
                    // Cliente no valida en UISP
                    switch(intval($isClientSaint["result"])) {
                        case -1: // Cliente no Valido
                            $myClienteFallido = $uisp_crm->ClasificarClienteQuote($myClienteFallido, $value['clientId']);
                            break;
                        case -3: // Cliente de Sistema Omitido
                            $myClienteOmitido = $uisp_crm->ClasificarClienteQuote($myClienteOmitido, $value['clientId']);
                            break;
                    }
                } else {
                    $myClienteFallido = $uisp_crm->ClasificarClienteQuote($myClienteFallido, $value['clientId']);
                }
            } else {
                if (!isset($value['clientId'])) {
                    $t = 2;
                }
                
            }
        }
        return array(
            "ClienteExportar" => $myClienteExportar, 
            'ClienteFallido' =>  $myClienteFallido, 
            'ClienteOmitido' =>  $myClienteOmitido, 
            'QuoteExportar' => $myQuote
        );
    }

    function RPT_ClienteExport($ArrayClientExport) {
        $tableFunc = $this->app->get('table_functions');

    }


    function PrepareQuoteExport() {

    }
}