<?php

declare(strict_types=1);

use Symfony\Component\ErrorHandler\ErrorHandler;

require \dirname(__DIR__) . '/vendor/autoload.php';

// Booting a Symfony kernel registers an exception handler that PHPUnit then reports as risky.
// Registering one up front keeps the handler stack balanced across tests.
ErrorHandler::register(null, false);
