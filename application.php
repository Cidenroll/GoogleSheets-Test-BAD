<?php

use Src\Application\Command\UploadCommand;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/bootstrap.php';

$application = new Application();
$application->add(new UploadCommand(null));

try {
    $application->run();
} catch (Exception $e) {
    echo '[Symfony-Console] ' . $e->getMessage();
}