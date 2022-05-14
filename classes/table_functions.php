<?php
class table_functions {
    public $uisp_structure;

    function __construct() {
        /*$this->uisp_structure =
        [
            'id' => $data["userIdent"],
            'tipo' => $tipo,
            'nombre' => $nombre,
            'telefono' => $data["contacto"]["telefono"],
            'saldo' => $data["accountBalance"],
            'fechacorte' => $fechavalue,
            'fecha_corte_mensaje' => $fechacorte
        ];*/
    }

    function CreateColumns($data) {
        if (is_array($data) and count($data) > 0) {
            $columns = array_keys($data[0]);
            $header['columns'] = array_map(function($column) {
                $columname = $this->NameColumns($column);
                return [
                    'field' => $column,
                    'title' => $columname,
                    'sortable' => true
                ];
            }, $columns);
            $headerjson = array('columns' => $header['columns']);
            return $headerjson;
        } else {
            return false;
        }
    }
    function CreateColumnsV2($data) {
        if (is_array($data) and count($data) > 0) {
            $columns = array_keys($data[0]);
            $header['columns'] = array_map(function($column) {
                $columname = $this->NameColumns($column);
                return [
                    'H_Name' => $column,
                    'H_Title' => $columname,
                    'H_Sort' => true,
                    'H_Show' => true,
                ];
            }, $columns);
            //$headerjson = htmlspecialchars_decode(json_encode(array('columns' => $header['columns'])));
            //return $headerjson;
            return $header['columns'];
        } else {
            return false;
        }
    }
    function NameColumns($column) {
        $columname = 'undefined';
        switch ($column) {
            case "id":
                $columname = "#";
                break;
            case "tipo":
                $columname = "Tipo";
                break;
            case "nombre":
                $columname = "Nombre";
                break;
            case "telefono":
                $columname = "Telefono";
                break;
            case "saldo":
                $columname = "Saldo";
                break;
            case "fechacorte":
                $columname = "Dia Corte Servicio";
                break;
            case "fecha_corte_mensaje":
                $columname = "Fecha Corte Servicio";
                break;
            case 'CodClie':
                $columname = "Id Cliente";
                    break;
            case 'Descrip':
                $columname = "Cliente";
                    break;
            case 'Represent':
                $columname = "Representante Lega";
                    break;
            case 'ID3':
                $columname = "Documento Legal";
                    break;
            case 'TipoID3';
                $columname = "Tipo De Cliente";
                    break;
            case 'Direc1':
                $columname = "Direccion Linea 1";
                    break;
            case 'Direc2':
                $columname = "Direccion Linea 2";
                    break;
            case 'Pais':
                $columname = "País";
                    break;
            case 'Estado':
                $columname = "Estado";
                    break;
            case 'Ciudad':
                $columname = "Ciudad";
                    break;
            case 'DiasCred':
                $columname = "Dias de Crédito";
                    break;
            case 'EsCredito':
                $columname = "Permite Crédito";
                    break;
            case 'EsMoneda':
                $columname = "Tipo de Moneda";
                    break;
            case 'TipoPVP':
                $columname = "Tipo de PVP";
                    break;
            case 'Telef':
                $columname = "Telefono";
                    break;
            case 'Movil':
                $columname = "Celular";
                    break;
            case 'Email':
                $columname = "Correo Electronico";
                    break;
            default:
                $columname = $column;
                break;

        }
        return $columname;
    }
    function TableActivate($nivel = null) {
        $table = [
            'search' => true,
            'virtualScroll' => true,            
            'showColumns' => true,
            'showExport'=> true,
            'showMultiSort' => true,
        ];
        return $table;
    }
}