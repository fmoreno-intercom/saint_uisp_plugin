<?php
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../classes/db_pdo.php';
require __DIR__ . '/../classes/db_sqlsrv.php';
require __DIR__ . '/../classes/ubnt_crm.php';
require __DIR__ . '/../classes/ubnt_nms.php';
require __DIR__ . '/../classes/ubnt_email.php';
require __DIR__ . '/../classes/saint_admin.php';
require __DIR__ . '/../classes/route_twig.php';
require __DIR__ . '/../classes/table_functions.php';
// Get UCRM log manager.
$log = \Ubnt\UcrmPluginSdk\Service\PluginLogManager::create();

// Inicializar RESTAPI SLIM
$settings = require __DIR__ . '/settings.php';
// Container
require __DIR__ . '/container.php';
// Create App
$app = AppFactory::create();
$app->setBasePath('');
// Router SLIM
require __DIR__ . '/route.php';
$app->run();
