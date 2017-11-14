<?php declare(strict_types=1);

// find app packages that depend on phpunit/phpunit
// e.g. https://packagist.org/packages/phpunit/phpunit/dependents

require_once __DIR__ . '/../vendor/autoload.php';

use Rector\NAI\Application\Application;
use Rector\NAI\DependencyInjection\ContainerFactory;

// nicer error messages
(new NunoMaduro\Collision\Provider)->register();

$container = (new ContainerFactory())->createWithConfig(__DIR__ . '/../rector.yml');
$application = $container->get(Application::class);
$application->run();
