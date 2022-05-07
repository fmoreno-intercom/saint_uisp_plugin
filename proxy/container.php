<?php
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
global $settings;

$container = new Container();
AppFactory::setContainer($container);
// Configuraciones Basicas
$container->set('settings', function() {
    global $settings;
    return $settings;
});
// Set view in Container
$container->set('view', function() {
    global $app, $settings;
    return Twig::create($settings["view"]["path"], $settings['view']['twig']);
});
// Conexion MSQL Server
$container->set('db', function ($c) {
    $myDB = [
        'Saint_Admin' => $c->get('db_admin_saint'),
        'Saint_Contable' => null
    ];
    return $myDB;
});
$container->set('db_admin_saint', function ($c) {
    $mydbconfig = $c->get('settings')['db_msqlsrv'];
    $mydb = new DB_MSQLSRV($mydbconfig);
    return $mydb;
});
// CALL UISP Security
$container->set('ubnt_security', function ($c) {
    // https://github.com/Ubiquiti-App/UCRM-plugins/blob/master/docs/security.md
    try {
        $security = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();
        $user = $security->getUser();
        return $user;    
    } catch (Exception $e) {
        // If there is an error, return false.
        return false;
    }
});
// CALL UISP NETWORK
$container->set('ubnt_nms', function ($c) {
    try {
        //$optionsManager = \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
        //$pluginPublicUrl = $optionsManager->loadOptions()->pluginPublicUrl;
        $myapi = $c->get('settings')['api_unms'];
        $nms = \Ubnt\UcrmPluginSdk\Service\UnmsApi::create($myapi);
        $ubnt_nms = new ubnt_nms($nms, $c->get('db'));
        return $ubnt_nms;
        //return $nms;
    } catch (Exception $e) {
        // If there is an error, return false.
        return false;
    }
});
// CALL UISP CRM
$container->set('ubnt_crm', function ($c) {
    try {
        $optionsManager = \Ubnt\UcrmPluginSdk\Service\UcrmOptionsManager::create();
        $pluginPublicUrl = $optionsManager->loadOptions()->pluginPublicUrl;
        $crm = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
        $ubnt_crm = new ubnt_crm($crm, $c->get('db'), $pluginPublicUrl);
        return $ubnt_crm;
        //return $nms;
    } catch (Exception $e) {
        // If there is an error, return false.
        return false;
    }
});
$container->set('saint_db', function ($c) {
    try {
        $db_admin= $c->get('db');
        $saintDB = new SAINT_DB($db_admin);
        return $saintDB;
    } catch (Exception $e) {
        // If there is an error, return false.
        return false;
    }
});

$container->set('table_functions', function ($c) {
    try {
        $tableFunc = new table_functions();
        return $tableFunc;
    } catch (Exception $e) {
        // If there is an error, return false.
        return false;
    }
});