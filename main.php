<?php

require_once __DIR__ . '/vendor/autoload.php';

$api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
$security = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();

/*$user = $security->getUser();
if (! $user || ! $user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SCHEDULING_MY_JOBS)) {
    die('You do not have permission to view this page.');
}

$jobs = $api->get(
    'scheduling/jobs',
    [
        'statuses' => [0],
        'assignedUserId' => $user->userId,
    ]
);

echo 'The following jobs are open and assigned to you:<br>';
echo '<ul>';
foreach ($jobs as $job) {
    echo sprintf('<li>%s</li>', htmlspecialchars($job['title'], ENT_QUOTES));
}
echo '</ul>';
*/
echo "Esto es una prueba";