<?php
class myRoute_Twig {
    private $app;
    private $conex_admin;
    private $conex_contab;
    private $active_uisp_crm;
    private $debug;
    private $developer = "fmoreno";
    private $security;

    function __construct($app, $debug = false) {
        $this->app = $app;
        $this->debug = $debug;
        unset($_SESSION['security']);
        if (!isset($_SESSION['security']))
        {
            $_SESSION['security']= $this->user_security();
        } 
        $this->security = $_SESSION['security'];
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
        if (!isset($this->active_uisp_crm)) {
            $this->active_uisp_crm = $this->app->get('ubnt_crm');
        }
        return $this->active_uisp_crm;
    }
    function user_security() {
        $developer_user['username'] = null;
        $security_permission = [
            'VIEW' => [
                'CLIENTS_SERVICES' => null,
                'BILLING_INVOICES' => null,
                'BILLING_QUOTES' => null,
                'BILLING_PAYMENTS' => null,
                'BILLING_REFUNDS' => null,
                'SYSTEM_PLUGINS' => null,
            ],
            'EDIT' => [
                'CLIENTS_CLIENTS' => null,
                'SYSTEM_ITEMS_SERVICE_PLANS' => null,
                'SYSTEM_ITEMS_PRODUCTS' => null,
            ],
            'SYNC' => [
                //'CLIENTS_FINANCIAL_INFORMATION' => null,
                'CLIENT_EXPORT' => null
            ]
        ];
        $security = $this->app->get('ubnt_security');
        $ubnt_network = $this->app->get('ubnt_nms');
        $user = $security->getUser();
        if (is_null($user)) {
            $users = $ubnt_network->get_list_users();
            $developer_user = $ubnt_network->findObjectById($users, 'username', $this->developer);
        } 
        if ($developer_user['username'] == $this->developer){
            if ($developer_user['role'] == 'admin' ) {
                foreach($security_permission['VIEW'] as $key => $value) {
                    $security_permission['VIEW'][$key] = true;
                }
                foreach($security_permission['EDIT'] as $key => $value) {
                    $security_permission['EDIT'][$key] = true;
                }
                foreach($security_permission['SYNC'] as $key => $value) {
                    $security_permission['SYNC'][$key] = true;
                } 
                // Colocar aqui los permisos especificos para pruebas
                $security_permission['SYNC']['CLIENT_EXPORT'] = false;
            }
        } else {
            // Permiso para ver
            $user_vw_CLIENTS_SERVICES = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::CLIENTS_CLIENTS) == true) ? true : false;
            $user_vw_BILLING_INVOICES = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::BILLING_INVOICES) == true) ? true : false;
            $user_vw_BILLING_QUOTES = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::BILLING_QUOTES) == true) ? true : false;
            $user_vw_BILLING_PAYMENTS = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::BILLING_PAYMENTS) == true) ? true : false;
            $user_vw_BILLING_REFUNDS = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::BILLING_REFUNDS) == true) ? true : false;
            $user_vw_SYSTEM_PLUGINS = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SYSTEM_PLUGINS) == true) ? true : false;
            // Permisos para Editar
            $user_ed_CLIENTS_CLIENTS  = ($user->hasEditPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::CLIENTS_CLIENTS ) == true) ? true : false;
            $user_ed_SYSTEM_ITEMS_SERVICE_PLANS = ($user->hasEditPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SYSTEM_ITEMS_SERVICE_PLANS) == true) ? true : false;
            $user_ed_SYSTEM_ITEMS_PRODUCTS  = ($user->hasEditPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SYSTEM_ITEMS_PRODUCTS) == true) ? true : false;
            // Permisos Sync
            $user_vw_CLIENTS_FINANCIAL_INFORMATION = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\SpecialPermissionNames::CLIENTS_FINANCIAL_INFORMATION) == true) ? true : false;
            $user_vw_CLIENT_EXPORT = ($user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\SpecialPermissionNames::CLIENT_EXPORT) == true) ? true : false;
            // Asignar los Permisos al Arreglo
            $security_permission['VIEW'] = [
                'CLIENTS_SERVICES' => $user_vw_CLIENTS_SERVICES,
                'BILLING_INVOICES' => $user_vw_BILLING_INVOICES,
                'BILLING_QUOTES' => $user_vw_BILLING_QUOTES,
                'BILLING_PAYMENTS' => $user_vw_BILLING_PAYMENTS,
                'BILLING_REFUNDS' => $user_vw_BILLING_REFUNDS,
                'SYSTEM_PLUGINS' => $user_vw_SYSTEM_PLUGINS,
            ];
            $security_permission['EDIT'] = [
                'CLIENTS_CLIENTS' => $user_ed_CLIENTS_CLIENTS,
                'SYSTEM_ITEMS_SERVICE_PLANS' => $user_ed_SYSTEM_ITEMS_SERVICE_PLANS,
                'SYSTEM_ITEMS_PRODUCTS' => $user_ed_SYSTEM_ITEMS_PRODUCTS,
            ];
            $security_permission['SYNC'] = [
                //'CLIENTS_FINANCIAL_INFORMATION' => $user_vw_CLIENTS_FINANCIAL_INFORMATION,
                'CLIENT_EXPORT' => $user_vw_CLIENT_EXPORT
            ];
        }
        $_SESSION['security'] = $security_permission;
        return $security_permission;
    }
    function check_security($type_security = 'VIEW', $permision = null) {
        $permission = false;
        foreach($this->security[$type_security] as $key => $value) {
            if (is_null($permision)) {
                if ($value != $permission) {
                    $permission = $value;
                }
            } else {
                // PROCEDIMIENTO PARA UN PERMISO EN ESPECIFICO
            }
        }
        return $permission;
    }
    function PrepareTableJson($myheader, $typedata, $mydata, $myopciones = null) {
        $jsonarray = [];
        $header = (is_array($myheader)) ? $myheader : null;
        if (is_array($mydata)) {
            $data = $mydata;
        }
        $opciones_table  = (!is_null($myopciones) && is_array($myopciones)) ? $myopciones : null;
        /*$jsonarray = array_merge(array(
            'columns' => $header, 
            $typedata => $data,
        ), $opciones_table);*/
        /*$jsonarray =array(
            'columns' => $header, 
            'data' => $data,
        );*/
        if (!is_null($header)) {
            $jsonarray['columns'] = $header;
        }
        if (!is_null($data)) {
            $jsonarray[$typedata] = $data;
        }
        if (!is_null($opciones_table)) {
            foreach($opciones_table as $key => $value) {
                $jsonarray[$key] = $value;
            }
        }
        
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
            $t = 1;
        }
        return "LISTO";
    }
    function NomalizeUisp() {
        $ucrm = $this->check_uisp_crm();
        $ClientesUpdate = $ucrm->NormalizeClient();
        $AddressUpdate = $ucrm->NormalizeAddress();
        if (count($ClientesUpdate) > 0 || count($AddressUpdate) > 0) {
            unset($_SESSION['client_uisp']);
            $ucrm->RefreshData();
        }
        return "LISTO";
    }
    function PrepareQuote() {
        $saint_admin = $this->conex_db("admin");
        $ucrm = $this->check_uisp_crm();
        $lastQuote = $saint_admin->LastQuoteSaint();
        if ($lastQuote["result"] == 1) {
            $lastFecha = explode(" ", $lastQuote[0]["FechaI"])[0];
            $normalize = $this->NomalizeUisp();
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
        $saint_admin = $this->conex_db("admin");
        $data = [];
        foreach($ArrayClientExport as $key => $value) {
            $myCliente = $value['userIdent'];
            $myCliente['Ciudad'] = $saint_admin->SearchCity($myCliente['Ciudad'])[0]['Descrip'];
            $myCliente['Estado'] = $saint_admin->SearchState($myCliente['Estado'])[0]['Descrip'];
            $myCliente['Pais'] = "Venezuela";
            if ($myCliente['TipoID3'] == 1) {
                $myCliente['TipoID3'] = "NATURAL";
            } else {
                $myCliente['TipoID3'] = "JURIDICO";
            }
            if ($myCliente['EsCredito'] == 1) {
                $myCliente['EsCredito'] = "Si";
            } else {
                $myCliente['EsCredito'] = "No";
            }           
            $myClienteFinal = [
                'ID Cliente' => $myCliente['CodClie'],
                'Cliente' => $myCliente['Descrip'],
                'Representante Legal' => $myCliente['Represent'],
                'Tipo Cliente' => $myCliente['TipoID3'],
                'Direccion' => $myCliente['Direc1'] . ' ' . $myCliente['Direc2'],
                'Telefono' => $myCliente['Telef'],
                'Movil' => $myCliente['Movil'],
                'Email' => $myCliente['Email'],
                'Ciudad'  => $myCliente['Ciudad'],
                'Estado' => $myCliente['Estado'],
                'Pais' => $myCliente['Pais'],                
                'Tipo PVP' => $myCliente['TipoPVP'],
                'Dias de Credito' => $myCliente['DiasCred'],
                'Permite Credito' => $myCliente['EsCredito'],
                'Moneda' => "Bolivares",
            ];
            $data[] = $myClienteFinal;
        }
        $myTwig['Template_Variable']['table']['header'] = $tableFunc->CreateColumns($data);
        $myTwig['Template_Variable']['table']['data'] = $data;
        $myTwig['Template_Variable']['table']['options'] = $tableFunc->TableActivate();
        return $myTwig;
    }


    function PrepareQuoteExport() {

    }
}