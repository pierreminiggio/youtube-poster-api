<?php

use App\App;
use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

$projectDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

require $projectDirectory . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$configProvider = new ConfigProvider($projectDirectory);
$config = $configProvider->get();
$dbConfig = $config['db'];
$featureAvailable = $config['working'] ?? false;

$app = new App(
    $featureAvailable,
    new DatabaseFetcher(new DatabaseConnection(
        $dbConfig['host'],
        $dbConfig['database'],
        $dbConfig['username'],
        $dbConfig['password'],
        DatabaseConnection::UTF8_MB4
    )),
    $config['token'],
    $config['proxy']
);

/** @var string $requestUrl */
$requestUrl = $_SERVER['REQUEST_URI'];

/** @var string|null $queryParameters */
$queryParameters = ! empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : null;

/** @var string $calledEndPoint */
$calledEndPoint = $queryParameters
    ? str_replace($queryParameters, '', $requestUrl)
    : $requestUrl
;

if (strlen($calledEndPoint) > 1 && substr($calledEndPoint, -1) === '/') {
    /** @var string $calledEndPoint */
    $calledEndPoint = substr($calledEndPoint, 0, -1);
}

$app->run($requestUrl, $queryParameters, $_SERVER['HTTP_AUTHORIZATION'] ?? null);

exit;
