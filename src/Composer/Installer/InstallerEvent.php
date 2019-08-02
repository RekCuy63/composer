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

namespace Composer\Installer;

use Composer\Composer;
use Composer\DependencyResolver\PolicyInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\Event;
use Composer\IO\IOInterface;
use Composer\Repository\CompositeRepository;
use Composer\Repository\RepositorySet;

/**
 * An event for all installer.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class InstallerEvent extends Event
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var bool
     */
    private $devMode;

    /**
     * @var PolicyInterface
     */
    private $policy;

    /**
     * @var RepositorySet
     */
    private $repositorySet;

    /**
     * @var CompositeRepository
     */
    private $installedRepo;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var OperationInterface[]
     */
    private $operations;

    /**
     * Constructor.
     *
     * @param string               $eventName
     * @param Composer             $composer
     * @param IOInterface          $io
     * @param bool                 $devMode
     * @param PolicyInterface      $policy
     * @param RepositorySet        $repositorySet
     * @param CompositeRepository  $installedRepo
     * @param Request              $request
     * @param OperationInterface[] $operations
     */
    public function __construct($eventName, Composer $composer, IOInterface $io, $devMode, PolicyInterface $policy, RepositorySet $repositorySet, CompositeRepository $installedRepo, Request $request, array $operations = array())
    {
        parent::__construct($eventName);

        $this->composer = $composer;
        $this->io = $io;
        $this->devMode = $devMode;
        $this->policy = $policy;
        $this->repositorySet = $repositorySet;
        $this->installedRepo = $installedRepo;
        $this->request = $request;
        $this->operations = $operations;
    }

    /**
     * @return Composer
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * @return IOInterface
     */
    public function getIO()
    {
        return $this->io;
    }

    /**
     * @return bool
     */
    public function isDevMode()
    {
        return $this->devMode;
    }

    /**
     * @return PolicyInterface
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * @return RepositorySet
     */
    public function getRepositorySet()
    {
        return $this->repositorySet;
    }

    /**
     * @return CompositeRepository
     */
    public function getInstalledRepo()
    {
        return $this->installedRepo;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return OperationInterface[]
     */
    public function getOperations()
    {
        return $this->operations;
    }
}
