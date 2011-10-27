<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../src/Composer/Autoload/ClassLoader.php';

$loader = new Composer\Autoload\ClassLoader();
$loader->add('Composer\\', dirname(__DIR__).'/src/');
$loader->add('Symfony\\Component\\', dirname(__DIR__).'/src/');
$loader->add('Composer_', dirname(__DIR__).'/src/');
$loader->register();
