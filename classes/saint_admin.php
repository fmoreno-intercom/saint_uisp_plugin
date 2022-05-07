<?php

class SAINT_DB {
    private $db_Admin;
    private $db_Contab;

    function __construct($conn_db) {
        $this->db_Admin = $conn_db['Saint_Admin'];
        $this->db_Contab = $conn_db['Saint_Contable'];
    }

    function get_quoted_list() {
        $sql = "SELECT * FROM [IntercomAdminDb].[dbo].[SAFACT] ORDER BY FechaI ASC";
        $result_sql = $this->db_Admin->SelectSQL($sql);
        return $result_sql;
    }

    function get_quoted_list_filter($filter = null, $order = false, $order_fields = null, $order_direcction = "ASC", $cant_row = 0) {
        if ($cant_row > 0) {
            $mytop = "TOP ".$cant_row;
        } else {
            $mytop = "";
        }
        if ($order == true) {
            $myorder = sprintf("ORDER BY %s %s", $order_fields, $order_direcction);
        } else {
            $myorder = "";
        }
        if (!is_null($filter) && is_array($filer)) {
            $myfilter = "where ";
            foreach($filter as $key => $value) {
                $myfilter .= $key . " = :" . $value;
            }

        } else {
            $myfilter = "";
        }
        $sql = sprintf("SELECT %s * FROM [IntercomAdminDb].[dbo].[SAFACT] %s %s", $mytop, $myfilter, $myorder);
        $result_sql = $this->db_Admin->SelectSQL($sql);
        return $result_sql;
    }
    function LastQuoteSaint() {
        $FacturasFinal = $this->get_quoted_list_filter(null, "true", "FechaI", "DESC", 1);
        return $FacturasFinal;
    }

    function CheckClienteSaint($Check) {
        // REcomendado llamar la funcion Clean Client de ubnt_crm Class
        // Array(Codigo, Cedula, Modificado)
        if (is_array($Check) && $Check[0] > 0) {
            $myclient = array('CodClie' => $Check[1]);
            $sql = "SELECT * FROM [IntercomAdminDb].[dbo].[SACLIE] WHERE CodClie = :CodClie";
            $result_sql = $this->db_Admin->SelectSQL($sql, $myclient);
            $result_sql['modificado'] = $Check[2];
            return $result_sql;    
        } else {
            return array('result' => $Check[0], 'modificado' => $Check[2], 'CodClieClean' => $Check[1]);
        }
    }

    function CleanClient($CodClie, $regex_options = '') {
        $CodClientOmit = ["14426", "14167", "15128"];
        $ClienValid = [];
        $ClienSymbol = [];
        $modificado = false;
        //Limpia Espacios en blanco
        $CodClie = trim($CodClie);
        
        // Debug
        if ($CodClie == "15560") {
            $y = 1;
        }
        // Chequeo
        if (array_search($CodClie, $CodClientOmit) === false || is_null($CodClie)) {
            preg_match('/([.\-, ])/'.$regex_options, $CodClie, $ClienSymbol);
            if (count($ClienSymbol) != 0)  {
                $CodClie = preg_replace('/([.,\-])/i', '', $CodClie);
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

    function SearchCity($city) {
        if (!is_null($city)) {
            $mycity = array('Descrip' => $city);
            $sql = "SELECT * FROM [IntercomAdminDb].[dbo].[SACIUDAD] WHERE Descrip = :Descrip AND Pais = 1";
            $result_sql = $this->db_Admin->SelectSQL($sql, $mycity);
            return $result_sql;    
        } else {
            return array('result' => -1);
        }
    }
    function PrepareClientExport($cliente) {
        // Tipo de Cliente 1 = Residencial / 0 = Juridico
        if ($cliente["clientType"] == 1) {
            $Cliente = $cliente["firstName"] . " " . $cliente["lastName"];
            $TipoID3 = 1;
            $RepresentanteLegal = null;
        } else {
            $Cliente = $cliente["companyName"];
            $RepresentanteLegal = $cliente['companyContactFirstName']. ' '. $cliente['companyContactLastName'];
            $TipoID3 = 0;
        }
        // Busca la ciudad, si no esta coloca por defecto Barquisimeto
        if (!is_null($cliente['city'])) {
            $checkcity = $this->SearchCity($cliente['city']);
        } else {
            $checkcity = $this->SearchCity("Barquisimeto");
        }
        if ($checkcity["result"] > 0) {
            $EstadoCliente = intval($checkcity[0]["Estado"]);
            $CiudadCliente = intval($checkcity[0]["Ciudad"]);
        } else {
            $EstadoCliente = 4;
            $CiudadCliente = 158;
        }
        // Si Tiene direccion detalla la seleccion, sino pone la Direccion Completa
        if (!is_null($cliente['street1']) || !is_null($cliente['street2'])) {
            $direccion1 = $cliente['street1'];
            $direccion2 = $cliente['street1'];
        } else {
            $direccion1 = $cliente['fullAddress'];
            $direccion2 = null;
        }
        // Chequeo de Telefono
        $TelefonoAltenativo = [];
        $Telefono = $cliente['contacts'][0]['phone'];
        preg_match('/([\/.\-, ])/', $Telefono, $TelefonoAltenativo);
        if (count($TelefonoAltenativo) > 0) {
            $ArrayTelefono = explode($TelefonoAltenativo[0], $Telefono);
            $Telefono = $ArrayTelefono[0];
            $TelefonoAltenativo = $ArrayTelefono[1];
        } else {
            $TelefonoAltenativo = null;
        }
        
        $Email = $cliente['contacts'][0]['email'];
        // Prepara Array para Saint
        $ClienteData = [
            'CodClie' => $cliente["userIdent"],
            'Descrip' => $Cliente,
            'Represent' => $RepresentanteLegal,
            'ID3' => $cliente["userIdent"],
            'TipoID3' => $TipoID3,
            'Direc1' => $direccion1,
            'Direc2' => $direccion2,
            'Pais' => 1,
            'Estado' => $EstadoCliente,
            'Ciudad'  => $CiudadCliente,
            'DiasCred' => 5,
            'EsCredito' => 1,
            'EsMoneda' => 0,
            'TipoPVP' => 1,
            'Telef' => $Telefono,
            'Movil' => $TelefonoAltenativo,
            'Email' => $Email
        ];
        
        return $ClienteData;

    }

    function PrepareClientExportSaint($ClientSaint) {
        if (is_array($ClientSaint) && $ClientSaint["result"] == 1 && $ClientSaint['modificado'] == false) {
            $ClienteData = [
                'CodClie' => $ClientSaint[0]["CodClie"],
                'Descrip' => $ClientSaint[0]["Descrip"],
                'Represent' => $ClientSaint[0]['Represent'],
                'ID3' => $ClientSaint[0]["ID3"], 
                'TipoID3' => $ClientSaint[0]["TipoID3"],
                'Direc1' => $ClientSaint[0]["Direc1"],
                'Direc2' => $ClientSaint[0]["Direc2"],
                'Pais' => $ClientSaint[0]["Pais"],
                'Estado' => $ClientSaint[0]["Estado"],
                'Ciudad'  => $ClientSaint[0]["Ciudad"],
                'DiasCred' => $ClientSaint[0]['DiasCred'],
                'EsCredito' => $ClientSaint[0]['EsCredito'],
                'EsMoneda' => $ClientSaint[0]['EsMoneda'],
                'TipoPVP' => $ClientSaint[0]['TipoPVP'],
                'Telef' => $ClientSaint[0]['Telef'],
                'Movil' => $ClientSaint[0]['Movil'],
                'Email' => $ClientSaint[0]['Email']
            ];
            return $ClienteData;    
        } else {
            return false;
        }
    }

    function PrepareQuoteExport($Quote, $Client) {

    }
}