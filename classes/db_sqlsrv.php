<?php

class DB_MSQLSRV {
    protected $conn;
    
    function __construct($db) {
        $pdo = $this->OpenConnection($db);
        $this->conn = $pdo;
    }

    function OpenConnection($db) {
        try  
        {  
            $serverName = $db['host'];
            $database = $db['database'];
            $uid = $db['username'];
            $pwd = $db['password'];
            $conn = new PDO(
                "sqlsrv:server=$serverName;Database=$database;TrustServerCertificate=true",
                $uid,
                $pwd,
                array(
                    //PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                )
            );
            return $conn;
        }  
        catch(Exception $e)  
        {   
	    $error = $e->getMessage();
            die('DB_MSQLSRV: ' . $error );   
        }  
    }

    function bindParams($stmt, $params) {   
        if(is_object($stmt) && ($stmt instanceof PDOStatement))
        {  
            foreach($params as $key => $value)
            {
                if(is_int($value)) {
                    $param = PDO::PARAM_INT; 
                } elseif(is_bool($value)) {
                    $param = PDO::PARAM_BOOL;
                } elseif(is_null($value)) {
                    $param = PDO::PARAM_NULL;
                } elseif(is_string($value)) {
                    $param = PDO::PARAM_STR;
                } else {
                    $param = FALSE;
                }
                if($param) {                                                        
                    $stmt->bindValue(":$key", $value, $param);                  
                }    
            }
        }
    }
    function SelectSQL_IN($sql_inicio, $sql_cierre, $string_keys, $params, $debug = false) {
        try {
            $arr = explode(',', $string_keys);
            $in_list = array();
            for ($i = 0; $i < count($arr); $i++) {
                $key = 'i_' . $i;
                $in_list[':' . $key] = array('id' => $arr[$i], 'param' => $key);
            }
            $keys = implode(',', array_keys($in_list));
            $stmt = $this->conn->prepare($sql_inicio.$keys.$sql_cierre);
            foreach ($in_list as $item) {
                $stmt->bindValue($item['param'], $item['id'], $params);
            }
            $stmt->execute();    
            if (strpos($sql_inicio, "SELECT") === 0) {
                $result = $stmt->fetchAll();
                $result['result'] = $stmt->rowCount();
            } else {
                $result = array('result' => $stmt->rowCount());
            }
            if ($debug == true) {
                //$stmt->debugDumpParams();
                $result['debug'] = $stmt->debugDumpParams();
            }
            return $result;
        } catch (Exception $e) {
            return array('result' => -1, 'error' => $e->getMessage());
        }
    }
    function SelectSQL($sql, $arrayFields = null, $debug = false) {
        try {
            $stmt = $this->conn->prepare($sql);
            if ($arrayFields != null) {
                foreach($arrayFields as $key => &$value) {
                    //$stmt->bindParam(':'.$key, $value);
                    $this->bindParams($stmt, array($key => $value));
                }  
            } 
            $stmt->execute();
            if (strpos($sql, "SELECT") === 0) {
                $result = $stmt->fetchAll();
                $result['result'] = $stmt->rowCount();
            } else {
                $result = array('result' => $stmt->rowCount());
            }
            if ($debug == true) {
                //$stmt->debugDumpParams();
                $result['debug'] = $stmt->debugDumpParams();
            }
            return $result;    
        } catch (Exception $e) {
            return array('result' => -1, 'error' => $e->getMessage());
        }
    } 
}