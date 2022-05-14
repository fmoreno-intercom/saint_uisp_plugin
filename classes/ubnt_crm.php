<?php
require_once __DIR__ . '/../vendor/autoload.php';
class ubnt_crm {
    private $clientcrm;
    private $log;
    private $db;
    private $client_system = ["14426", "14167", "15128"];
    public $service_iptv;
    public $service_internet;
    public $client_uisp;
    public $client_service;

    
    function __construct($client, $db, $pluginUrl) {
        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->clientcrm = $client;        
        $this->db = $db;
        $this->log = ($pluginUrl == $actual_link) ? \Ubnt\UcrmPluginSdk\Service\PluginLogManager::create() : null;
        $this->RefreshData();
    }
    
    function RefreshData($force = false) {
        if  ($force == true) session_unset();
        if (!isset($_SESSION['service_iptv']) && !isset($_SESSION['service_internet']))
        {
            $_SESSION['service_iptv'] = $this->get_service_iptv();
            $_SESSION['service_internet'] = $this->get_service_internet();
        } 
        if (!isset($_SESSION['client_uisp']))
        {
            $this->client_uisp = $this->get_client_uisp();
        } else {
            $this->client_uisp = $_SESSION['client_uisp'];
        }
        if (isset($_SESSION['client_service']))
        {
            $this->client_service = $_SESSION['client_service'];
        }
        $this->service_iptv = $_SESSION['service_iptv'];
        $this->service_internet = $_SESSION['service_internet'];
    }
    function appendlog($message) {
        if (!is_null($this->log)) {
            $this->log->appendLog($message);
        }
    }
    function findObjectById($array, $id, $value){
        if (is_array($array)) {
            foreach($array as $key => $val){
                /*if (!isset($val[$id])) {
                    error_log("Error: $id not found in array element $value");
                }*/
                if($val[$id] == $value){
                    return $val;
                }
            }    
        }
        return false;
    }
    function get_client_uisp($telefono = null) {
        $url = '/clients';
        $variable["lead"] = 0;
        if ($telefono != null) {
            $variable["phone"] = $telefono;
        }
        /*$variable["limit"] = 5;*/ // limitar a 5 clientes
        $result = $this->clientcrm->get($url, $variable);
        if (count($result) > 0) {
            $_SESSION['client_uisp'] = $result;
            return $result;   
        } else {
            return null;
        }
    }
    function get_info_user($id) {
        $result = $this->clientcrm->get(
            'clients/' . $id
        );
        return $result;
    }
    function get_info_user2($user) {
        $result = $this->clientcrm->get(
            'clients/',
            [ 'username' => $user] 
        );

        return (count($result) == 1) ? $result : false;
    }
    function get_service_iptv($id_service = null) {
        $url = 'service-plans';
        $variable = [ 'servicePlanType' => 'general' ];
        $result = $this->clientcrm->get(
            $url,
            $variable
        );
        $services = array();
        foreach($result as $key => $value) {
            $invoiceLabel = strpos($value['invoiceLabel'], "IPTV");
            if ($invoiceLabel !== false) {
                $services[] = $value;
            }
            /*if ($value['invoiceLabel'] == 'IPTV' || $value['invoiceLabel'] == 'IPTV ADICIONAL') {
                $services[] = $value;
            }*/
        }
        if ($id_service != null) {
            return $this->findObjectById($services, 'id', $id_service);
        }
        return $services;
    }
    function get_service_internet($id_service = null) {
        $url = 'service-plans';
        $variable = [ 'servicePlanType' => 'internet' ];
        $result = $this->clientcrm->get(
            $url,
            $variable
        );
        $services = array();
        foreach($result as $key => $value) {
            $invoiceLabel = strpos($value["servicePlanType"], "Internet");
            if ($invoiceLabel !== false) {
                $services[] = $value;
            }
            /*if ($value['invoiceLabel'] == 'IPTV' || $value['invoiceLabel'] == 'IPTV ADICIONAL') {
                $services[] = $value;
            }*/
        }
        if ($id_service != null) {
            return $this->findObjectById($services, 'id', $id_service);
        }
        return $services;
    }
    function my_service($clientid) {
        $searchweb = true;
        $url = 'clients/services';
        $variable = ['clientId' => $clientid ];
        if (isset($_SESSION['client_service'])) {
            $search_client_service = $this->findObjectById($this->client_service, 'clientId', $clientid);
            if ($search_client_service) {
                $searchweb = false;
            }            
        }
        $client_service =  ($searchweb == true) ? $this->clientcrm->get($url, $variable) : array( 0 => $search_client_service);
        if (count($client_service) == 1 && $searchweb == true && $client_service[0]['clientId'] == $clientid) {
            $_SESSION['client_service'][] = $client_service[0];
            $this->client_service =  $_SESSION['client_service'];
        } 
        return $client_service;
    }
    function get_client_service($clientid, $typeservice = "IPTV") {
        $result = null;
        $activo = null;
        $finalizado = null;
        $suspendido = null;        
        $client_service = $this->my_service($clientid);
        if ($typeservice == "IPTV") {
            $myservices = $this->service_iptv;
        } else  {
            $myservices = $this->service_internet;
        }
        foreach($client_service as $key => $value) {
            if (isset($value['servicePlanId']) && isset($myservices)) {
                $service = $this->findObjectById($myservices, 'id', $value['servicePlanId']);
            } elseif  (isset($value['serviceid']) && isset($myservices)){
                $service = $this->findObjectById($myservices, 'id', $value['serviceid']);
            } else {
                $service = false;
            }
            if ($service != false) {
                $array_data = explode("T", $value["activeFrom"]);
                $date_tmp = strtotime($array_data[0]);
                $today = strtotime(date("Y-m-d"));
                $lastInvoice = strtotime(date("Y", $today)."-".date("m", $today)."-".date("d", $date_tmp));
                if (is_null($value["activeTo"])) {
                    $activeTo = date('Y-m-d',(strtotime('next month', strtotime(date('Y-m-d', $lastInvoice)))))."T".$array_data[1];
                } else {
                    $activeTo = $value["activeTo"];
                }
                $myservice = array( 
                    'id' => $value['servicePlanId'], 
                    'serviceid' => $value['id'],
                    'name' => $service['name'], 
                    'status' => $value['status'], 
                    'invoiceLabel' => $service['invoiceLabel'],
                    'total_price' => $value["totalPrice"], 
                    'activeFrom' => $value["activeFrom"],
                    'activeTo' => $activeTo,
                    'lastInvoice' => date('Y-m-d', $lastInvoice)."T".$array_data[1],
                    /*'typeService' => $service['typeService']*/
                );
                switch ($value['status']) {
                    case 1:
                        $activo[] = $myservice;
                        break;
                    case 2:
                        $finalizado[] = $myservice;
                        break;
                    case 3:
                        $suspendido[] = $myservice;
                        break;
                }
            }
        }
        if ($activo == null) {
            $noactivo = array(0 => array('id' => -2, 'name' => 'No activado', 'status' => 0));
            $result = array('activo' => $noactivo,  'finalizado' => $finalizado, 'suspendido' => $suspendido);
        } else {
            $result = array('activo' => $activo, 'finalizado' => $finalizado, 'suspendido' => $suspendido);
        }
        return $result;
    }
    function reactivate_plan($serviceid, $servicePlanPeriodId, $activeFrom, $activeTo) {
        try {
            $url = '/clients/services/'.$serviceid;
            $variable = [
                "servicePlanPeriodId" => $servicePlanPeriodId,
                "activeTo" => $activeTo,
                "note" => "Reactivacion de Servicios"
            ];
            $reactivate = $this->clientcrm->patch($url, $variable);
            if (count($reactivate) > 0 && $reactivate['status'] == 1) {
                $result = array('code' => 200, 'result' => 'Finalizado');
            } else {
                $result = array('code' => 422, 'result' => 'Error al reactivar2');
            }
            return $result;
        } catch (\Exception $e) {
            $this->appendlog($e->getMessage());
            return array('code' => 500, 'result' => 'Error al finalizar plan', 'data' => $e->getMessage());
        }
    }
    function end_plan($serviceid) {
        try {
            $url = '/clients/services/'.$serviceid.'/end';
            $result = $this->clientcrm->patch($url);
            if (count($result) > 0 && $result['status'] == 2){
                $result = array('code' => 200, 'result' => 'Finalizado');
            } else {
                $result = array('code' => 422, 'result' => 'Error al finalizar plan');
                // generar reporte a administrador de falla de creacion de plan
            }
            return $result;
        } catch (\Exception $e) {
            $this->appendlog($e->getMessage());
            return array('code' => 500, 'result' => 'Error al finalizar plan', 'data' => $e->getMessage());
        }
    }
    function suspend_plan($serviceid, $reason) {
        try {
            $url = '/clients/services/'.$serviceid.'/suspend';
            $variable = ["suspensionReasonId" => $reason];
            $result = $this->clientcrm->patch($url, $variable);
            if ($result == ""){
                $result = array('code' => 200, 'result' => 'Suspendido');
            }
            return $result;
        } catch (\Exception $e) {
            $this->appendlog($e->getMessage());
            return array('code' => 500, 'result' => 'Error al agregar plan', 'data' => $e->getMessage());
        }
    }
    function add_plan($clientid, $plan_period_id, $activeFrom = null, $activeTo = null, $invoicingStart = null) {
        try {
            $url = '/clients/'.$clientid.'/services';
            $variable["servicePlanPeriodId"] = $plan_period_id;
            if (!is_null($activeFrom)) $variable["activeFrom"] = $activeFrom;
            if (!is_null($activeTo)) $variable["activeTo"] = $activeTo;
            if (!is_null($invoicingStart)) {
                $variable["invoicingStart"] = $invoicingStart;
                $date_tmp = explode("T", $invoicingStart);
                $variable["invoicingPeriodStartDay"] = intval(date('d', strtotime($date_tmp[0])));
            }
            $addplan = $this->clientcrm->post($url, $variable);
            if ($addplan != ""){
                $result = array("code" => 200, "message" => "Se ha agregado el plan correctamente", "data" => $addplan);
            } else {
                $result = array("code" => 401, "message" => "Problemas al agregar el plan correctamente", "data" => $addplan);
            }
            return $result;                
        } catch (\Exception $e) {
            $this->appendlog($e->getMessage());
            return array('code' => 500, 'result' => 'Error al agregar plan', 'data' => $e->getMessage());
        }
    }
    function ListClientStatus() {
        $client_structure = [
            "userIdent" => "",
            "clientType" => "",
            "companyName" => "",
            "firstName" => "",
            "lastName" => "",
            "registrationDate" => "",
            "id" => "",
            "isActive" => "",
            "accountBalance" => "",
            "hasSuspendedService" => "",
            "contacto" => "",
            "agente" => "",
            "tipo_servicio" => "",
            "financiado" => false,
        ];

        try {
            $result = (!isset($_SESSION['client_uisp'])) ? $this->clientcrm->get($url, $variable) : $_SESSION['client_uisp'];
            if (count($result) > 0 ){
                $data = [];
                foreach ($result as $key => $value) {
                    $blanco = $client_structure;
                    $blanco["userIdent"] = $value["userIdent"];
                    $blanco["clientType"] = $value["clientType"];
                    $blanco["companyName"] = $value["companyName"];
                    $blanco["firstName"] = $value["firstName"];
                    $blanco["lastName"] = $value["lastName"];
                    $blanco["registrationDate"] = $value["registrationDate"];
                    $blanco["id"] = $value["id"];
                    $blanco["isActive"] = $value["isActive"];
                    $blanco["accountBalance"] = $value["accountBalance"];
                    $blanco["hasSuspendedService"] = $value["hasSuspendedService"];
                    $blanco["contacto"] = [
                        'direccion' => $value["fullAddress"],
                        'telefono' => $value["contacts"][0]["phone"],
                    ];
                    if ($blanco["hasSuspendedService"] == 0) {
                        $servicioInternet =$this->get_client_service($value['id'], 'INTERNET');
                        $blanco['planes']['internet'] = $servicioInternet;    
                    }
                    $agente = (isset($value["attributes"])) ? $this->findObjectById($value["attributes"], "customAttributeId", 19) : "";
                    $blanco["agente"] = ($agente != false) ? $agente : "";
                    if (isset($value["tags"])) {
                        $fibra = $this->findObjectById($value["tags"], "id", 64);
                        $financiado = $this->findObjectById($value["tags"], "id", 34);
                    } 
                    if (isset($fibra) && $fibra != false) {
                        $blanco["tipo_servicio"] = "FIBRA";
                    } else {
                        $blanco["tipo_servicio"] = "WISP";
                    }
                    if (isset($financiado)) {
                        $blanco["financiado"] = true;
                    }
                    $data[] = $blanco;
                }
                $data = $this->ClasificarCliente($data);
                $result = array("code" => 200, "message" => "Listado de clientes", "data" => $data, "result" => count($result));
            } else {
                $result = array('code' => 404, 'message' => 'Error al finalizar plan', "result" => -1);
            }
            return $result;
        } catch (\Exception $e) {
            $this->appendlog($e->getMessage());
            return array('code' => 500, 'result' => 'Error al finalizar plan', 'data' => $e->getMessage());
        }
    }

    function ClasificarCliente($listadoCliente) {
        $activo = [];
        $suspend = [];
        $terminate = [];
        foreach($listadoCliente as $key => $value) {
            if ($value["hasSuspendedService"] == 0) {
               $action = true;
            } else {
                $action = false;   
            }
            if ($value['clientType'] == 1) {
                $typeclient = 'RESIDENCIAL';
            } else {
                $typeclient = 'COMERCIAL';
            }
            if ($value['accountBalance'] >= 0) {
                $solvente = true;
            } else {
                $solvente = false;
            }
            if ($action) {
                if ($solvente) {
                    $activo[$typeclient]['SOLVENTE'][] = $value;
                } else {
                    $activo[$typeclient]['DEUDOR'][] = $value;
                }
            } else {
                if ($solvente) {
                    $suspend[$typeclient]['SOLVENTE'][] = $value;
                } else {
                    $suspend[$typeclient]['DEUDOR'][] = $value;
                }
            }
        }
        $clienteclasificado = [
            "activo" => $activo,
            "suspend" => $suspend,
            "terminate" => $terminate
        ];
        return $clienteclasificado;
    }

    function ClienteFechaCorte($Clientes) {
        $ListadoFechaCorte = [];
        $ClienteActivo = $Clientes['activo'];
        foreach($ClienteActivo as $key => $value) {
            foreach($value as $key2 => $value2) {
                foreach($value2 as $key3 => $value3) {
                    if (isset($value3["planes"]["internet"]["activo"][0]["lastInvoice"])) {
                        $myfechaCorte = $this->ConvertUispDate($value3["planes"]["internet"]["activo"][0]["lastInvoice"]);
                        $ListadoFechaCorte[] = $this->CreateDataReport($value3, $myfechaCorte["dateUisp"]);
                    }
                }
            }
        }
        return $this->OrderFechaCorte($ListadoFechaCorte);
    }  
    function OrderFechaCorte($ListadoFechaCorte) {
        $tmpFechaCorte = [];
        for($i = 1; $i < 31; $i++) {
            foreach($ListadoFechaCorte as $key => $value) {
                if (intval($value['fechacorte']) == $i) {
                    $tmpFechaCorte[] = $value;
                }
            }
        }
        return  $tmpFechaCorte;
    }
    function ConvertUispDate($DateUisp)  {
        $array_data = explode("T", $DateUisp);
        $date_tmp = strtotime($array_data[0]);
        $myDateUisp = date('d', $date_tmp);
        return array('date' => $date_tmp, 'time' => $array_data[1], 'dateUisp' => $myDateUisp);
    }
    function CreateDataReport($data, $myfechacorte = null) {
        $tipo = ($data["clientType"] == 1) ? 'RESIDENCIAL' : 'COMERCIAL';
        $nombre = ($data["clientType"] == 1) ? $data["firstName"] . ' ' . $data["lastName"] : $data["companyName"];
        if ($myfechacorte != null) {
            $fechavalue = intval($myfechacorte);
            $fechacorte = $fechavalue . " de cada mes";
        } else {
            $fechavalue = "";
            $fechacorte = "";
        }
        
        $dataClient = [
            'id' => $data["userIdent"],
            'tipo' => $tipo,
            'nombre' => $nombre,
            'telefono' => $data["contacto"]["telefono"],
            'saldo' => $data["accountBalance"],
            'fechacorte' => $fechavalue,
            'fecha_corte_mensaje' => $fechacorte
        ];
        return $dataClient;
    }
    function ClientesConexion($Clientes) {
        $FIBRA = [];
        $WISP = [];
        $ClienteActivo = $Clientes['activo'];
        foreach($ClienteActivo as $key => $value) {
            foreach($value as $key2 => $value2) {
                foreach($value2 as $key3 => $value3) {
                    $Cliente = $this->CreateDataReport($value3);
                    switch($value3["tipo_servicio"]) {
                        case 'FIBRA':
                            $FIBRA[] = $Cliente;
                            break;
                        case 'WISP':
                            $WISP[] = $Cliente;
                            break;
                    }
                }
            }
        }
        return array('FIBRA' => $FIBRA, 'WISP' => $WISP);
    }
    function QuotesList($QuoteFrom, $QuoteTo = null, $proforma = -1) {
        $url = 'invoices';
        $variable['createdDateFrom'] = $QuoteFrom;
        if (isset($QuoteTo)) {
            $variable['createdDateTo'] = $QuoteTo;
        }
        if ($proforma != -1) {
            if (is_bool($proforma)) {
                $variable['proforma'] = $proforma;
            }
        }
        $result = $this->clientcrm->get($url, $variable);
        if (count($result) > 0) {
            return $result;   
        } else {
            return null;
        }
    }
    function NormalizeClient($refresh = false) {
        $ClientClean = [];
        $ClientUpdate = [];
        if ($refresh == true) $this->RefreshData(true);
        $ClientUisp = $this->client_uisp;
        foreach($ClientUisp as $key => $value) {
            $myClientId = $this->CleanClient($value);
            if ($myClientId[0] > 0 && $myClientId[2] == true) {
                $myClientId['id'] = $value['id'];
                $myClientId['userIdent_toClean'] = $value['userIdent'];
                $ClientClean[] = $myClientId;
            }            
        }
        if (count($ClientClean) > 0) {
            foreach($ClientClean as $key => $value) {
                $ClientId = $value["id"];
                $fieldUpdate = array('userIdent' => $value[1]);
                $result = $this->ClientUpdate($ClientId, $fieldUpdate);
                if ($result != false ) {
                    $value['Cambiado'] = true;
                    $ClientUpdate[] = $value;
                }
            }    
        }
        return $ClientUpdate;
    }
    function NormalizeAddress($refresh = false) {
        if ($refresh == true) $this->RefreshData(true);
        unset($key);
        unset($value);
        $ClientClean = [];
        $ClientUpdate = [];
        $key = null;
        $value = null;
        $array_accent = array();
        $array_accent[0]  = '/á/';
        $array_accent[1]  = '/é/';
        $array_accent[2]  = '/í/';
        $array_accent[3]  = '/ó/';
        $array_accent[4]  = '/ú/';
        $array_accent[5]  = '/ñ/';
        $array_accent_change = array();
        $array_accent_change[0]  = 'Á';
        $array_accent_change[1]  = 'É';
        $array_accent_change[2]  = 'Í';
        $array_accent_change[3]  = 'Ó';
        $array_accent_change[4]  = 'Ú';
        $array_accent_change[5]  = 'Ñ';

        $ClientUisp = $this->client_uisp; // revisar el valor $key y $value y por que da errot
        foreach($ClientUisp as $key => $value) {
            /*if (strpos($value['street1'], 'ó') > 0) {
                $t = 1;
            }*/ // Debug
            $clean = false;
            $regex_find = [];
            $regex_find_latim = [];
            if (!is_null($value['city'])) {
                $Ciudad = strtoupper($value['city']);
                $CantCiudad = strlen($Ciudad);
                $regex_replace = '/(ESTADO LARA|EDO LARA|.EDO LARA|'.$Ciudad.', LARA|'.$Ciudad.' LARA|BARQUISIMETO, LARA|BARQUISIMETO LARA)/i';
            }             
            $direccion1 = trim(strtoupper($value['street1']));
            $direccion2 = trim(strtoupper($value['street2']));
            $direccionfull = trim(strtoupper($value['fullAddress']));
            preg_match($regex_replace, $direccion1, $regex_find);
            preg_match('/([áéíóú])/u', $direccion1, $regex_find_latim, PREG_OFFSET_CAPTURE);
            if (is_null($direccion1) && !is_null($direccionfull)) {
                $clean = true;
            } 
            if ($direccion1 == $direccion2 && !is_null($direccion1) && $direccion1 != "") {
                $clean = true;
                $direccion2 = null;
            }
            if (!is_null($direccion2) && $direccion2 != "") {
                $clean = true;
                $direccion1 = $direccion1 . ' ' . $direccion2;
                $direccion2 = null;
            }
            if ((strpos($direccion1, $Ciudad) == (strlen($direccion1) - $CantCiudad) || count($regex_find) >= 2)  && isset($Ciudad)) {
                $clean = true;
                if (strpos($direccion1, $Ciudad) == (strlen($direccion1) - $CantCiudad)) {
                    $direccion1 = substr($direccion1, 0, (strlen($direccion1) - $CantCiudad));
                } else {
                    $direccion1 = preg_replace($regex_replace, '', $direccion1);
                }
                
            }
            if (count($regex_find_latim) > 0) {
                $clean = true;
                $direccion1 = preg_replace($array_accent, $array_accent_change, $direccion1);
            }
            if ($clean) {
                $direccion1 = trim(preg_replace('/\s+/', ' ', $direccion1)); // elimina el doble espacio
                $direccionfull = trim(preg_replace('/\s+/', ' ', $direccionfull)); // elimina el doble espacio
                $value['city'] = (isset($Ciudad)) ? $Ciudad : "Barquisimeto";
                $value['street1'] = trim($direccion1);
                $value['street2'] = (!is_null($direccion2)) ? trim($direccion2) : null;
                $ZipCode = (!is_null($value['zipCode'])) ? ", ".$value['zipCode'] : "";
                $direccionfull = $value['street1'] . ', ' . $value['city'] . $ZipCode;
                $value['fullAddress'] = $direccionfull;
                $ClientClean[] = $value;
            }
        }
        if (count($ClientClean) > 0) {
            foreach($ClientClean as $key => $value) {
                $ClientId = $value["id"];
                $fieldUpdate = array('street1' => $value['street1'], 'street2' => $value['street2'], 'fullAddress' => $value['fullAddress']);
                //$result = true;
                $result = $this->ClientUpdate($ClientId, $fieldUpdate);
                if ($result != false ) {
                    $value['Cambiado'] = true;
                    $ClientUpdate[] = $value;
                }
            }
        }
        return $ClientUpdate;

    }
    function ClientUpdate($ClientId, $fieldUpdate) {
        $url = 'clients/'.$ClientId;
        if (is_array($fieldUpdate) && count($fieldUpdate) > 0) {
            $result = $this->clientcrm->patch(
                $url,
                $fieldUpdate
            );    
        } else {
            $result = false;
        }
        return $result;
    }

    function CleanClient($CodClie, $regex_options = '') {
        $CodClientOmit = $this->client_system;
        $ClienValid = [];
        $ClienSymbol = [];
        $modificado = false;
        //Limpia Espacios en blanco
        $CodClienOriginal = $CodClie['userIdent'];
        $Id_Client = $CodClie['id'];
        $CodClie = trim($CodClienOriginal);
        
        // Debug
        //if (strpos($CodClie, 'v') == 0) {
        if ($Id_Client == 14167) {
            $y = 1;
        }
        // Chequeo
        if (array_search($Id_Client, $CodClientOmit) === false) {
            preg_match('/([.\-, ])/'.$regex_options, $CodClie, $ClienSymbol);
            if (count($ClienSymbol) != 0)  {
                $CodClie = preg_replace('/([.,\- ])/'.$regex_options, '', $CodClie);
                $modificado = true;
            } 
            preg_match('/([VEJG])/'.$regex_options, $CodClie, $ClienValid);
            if (count($ClienValid) == 2) {
                // Cliente Valido
                $ClienValid = array(2,$CodClie,$modificado);
            } else {
                // Cliente No Valido
                $ClienValid = array(-1,$CodClie,$modificado);
            }
        } else {
            // Cliente Omitido de Pruebas
            $ClienValid = array(-3,$CodClie,TRUE);
        }
        return $ClienValid;
    }
    function ClasificarClienteQuote($arraySearch, $id_search, $userIdenLoad = null, $field_search = 'clientId') {
        $searchClient = $this->findObjectById($arraySearch, $field_search, $id_search);
        if ($searchClient === false) {
            if (is_null($userIdenLoad)) {
                $field_check = ( $field_search = 'clientId') ? 'id' : $field_search;
                $datosCliente = $this->findObjectById($this->client_uisp, $field_check, $id_search);    
            } else {
                $datosCliente = $userIdenLoad;
            }
            $arraySearch[] = array( 
                'clientId' => $id_search, 
                'userIdent' => $datosCliente
            );
        } 
        return $arraySearch;
    }
}
