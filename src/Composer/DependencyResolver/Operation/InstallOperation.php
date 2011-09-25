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

namespace Composer\DependencyResolver\Operation;

use Composer\Package\PackageInterface;

/**
 * Solver install operation.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class InstallOperation extends SolverOperation
{
    protected $package;

    /**
     * Initializes operation.
     *
     * @param   PackageInterface    $package    package instance
     * @param   string              $reason     operation reason
     */
    public function __construct(PackageInterface $package, $reason = null)
    {
        parent::__construct($reason);

        $this->package = $package;
    }

    /**
     * Returns package instance.
     *
     * @return  PackageInterface
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Returns installer type to be used with this operation.
     *
     * @return  string
     */
    public function getInstallerType()
    {
        return $this->package->getType();
    }

    /**
     * Returns job type.
     *
     * @return  string
     */
    public function getJobType()
    {
        return 'install';
    }
}
