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

use Composer\Package\PackageInterface;
use Composer\Package\AliasPackage;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Util\Filesystem;

/**
 * Package operation manager.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class InstallationManager
{
    private $installers = array();
    private $cache = array();
    private $vendorPath;

    /**
     * Creates an instance of InstallationManager
     *
     * @param    string    $vendorDir    Relative path to the vendor directory
     * @throws   \InvalidArgumentException
     */
    public function __construct($vendorDir = 'vendor')
    {
        $fs = new Filesystem();

        if ($fs->isAbsolutePath($vendorDir)) {
            $basePath = getcwd();
            $relativePath = $fs->findShortestPath($basePath.'/file', $vendorDir);
            if ($fs->isAbsolutePath($relativePath)) {
                throw new \InvalidArgumentException("Vendor dir ($vendorDir) must be accessible from the directory ($basePath).");
            }
            $this->vendorPath = $relativePath;
        } else {
            $this->vendorPath = rtrim($vendorDir, '/');
        }
    }

    /**
     * Adds installer
     *
     * @param   InstallerInterface  $installer  installer instance
     */
    public function addInstaller(InstallerInterface $installer)
    {
        array_unshift($this->installers, $installer);
        $this->cache = array();
    }

    /**
     * Returns installer for a specific package type.
     *
     * @param   string              $type       package type
     *
     * @return  InstallerInterface
     *
     * @throws  InvalidArgumentException        if installer for provided type is not registered
     */
    public function getInstaller($type)
    {
        $type = strtolower($type);

        if (isset($this->cache[$type])) {
            return $this->cache[$type];
        }

        foreach ($this->installers as $installer) {
            if ($installer->supports($type)) {
                return $this->cache[$type] = $installer;
            }
        }

        throw new \InvalidArgumentException('Unknown installer type: '.$type);
    }

    /**
     * Checks whether provided package is installed in one of the registered installers.
     *
     * @param   PackageInterface    $package    package instance
     *
     * @return  Boolean
     */
    public function isPackageInstalled(PackageInterface $package)
    {
        return $this->getInstaller($package->getType())->isInstalled($package);
    }

    /**
     * Executes solver operation.
     *
     * @param   OperationInterface  $operation  operation instance
     */
    public function execute(OperationInterface $operation)
    {
        $method = $operation->getJobType();
        $this->$method($operation);
    }

    /**
     * Executes install operation.
     *
     * @param   InstallOperation    $operation  operation instance
     */
    public function install(InstallOperation $operation)
    {
        $package = $operation->getPackage();
        if ($package instanceof AliasPackage) {
            $package = $package->getAliasOf();
            $package->setInstalledAsAlias(true);
        }
        $installer = $this->getInstaller($package->getType());
        $installer->install($package);
    }

    /**
     * Executes update operation.
     *
     * @param   InstallOperation    $operation  operation instance
     */
    public function update(UpdateOperation $operation)
    {
        $initial = $operation->getInitialPackage();
        if ($initial instanceof AliasPackage) {
            $initial = $initial->getAliasOf();
        }
        $target = $operation->getTargetPackage();
        if ($target instanceof AliasPackage) {
            $target = $target->getAliasOf();
            $target->setInstalledAsAlias(true);
        }

        $initialType = $initial->getType();
        $targetType  = $target->getType();

        if ($initialType === $targetType) {
            $installer = $this->getInstaller($initialType);
            $installer->update($initial, $target);
        } else {
            $this->getInstaller($initialType)->uninstall($initial);
            $this->getInstaller($targetType)->install($target);
        }
    }

    /**
     * Uninstalls package.
     *
     * @param   UninstallOperation  $operation  operation instance
     */
    public function uninstall(UninstallOperation $operation)
    {
        $package = $operation->getPackage();
        if ($package instanceof AliasPackage) {
            $package = $package->getAliasOf();
        }
        $installer = $this->getInstaller($package->getType());
        $installer->uninstall($package);
    }

    /**
     * Returns the installation path of a package
     *
     * @param   PackageInterface    $package
     * @return  string path
     */
    public function getInstallPath(PackageInterface $package)
    {
        $installer = $this->getInstaller($package->getType());
        return $installer->getInstallPath($package);
    }

    /**
     * Returns the vendor path
     *
     * @param   boolean  $absolute  Whether or not to return an absolute path
     * @return  string path
     */
    public function getVendorPath($absolute = false)
    {
        if (!$absolute) {
            return $this->vendorPath;
        }

        return getcwd().DIRECTORY_SEPARATOR.$this->vendorPath;
    }
}
