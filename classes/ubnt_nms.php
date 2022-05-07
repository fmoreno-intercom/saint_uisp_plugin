<?php
require_once __DIR__ . '/../vendor/autoload.php';
class ubnt_nms {
    private $clientnms;
    private $db;
    //private $log;

    function __construct($client, $db) {
        $actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->clientnms = $client;
        $this->db = $db;
        //$this->log = ($pluginUrl == $actual_link) ? \Ubnt\UcrmPluginSdk\Service\PluginLogManager::create() : null;
    }

    function get_list_users() {
        $result = $this->clientnms->get(
            'users'
            );
        return $result;
    }
    function get_info_user($user) {
        $result = $this->get_list_users();
        foreach($result as $key => $value) {
            if ($value['username'] == $user) {
                return $value;
            }
        } 
        return false;       
    }
    function get_user_bouquet($user) {
        
    }
}