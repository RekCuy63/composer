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

use Composer\Repository\WritableRepositoryInterface;
use Composer\Package\PackageInterface;

/**
 * Metapackage installation manager.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class MetapackageInstaller implements InstallerInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === 'metapackage';
    }

    /**
     * {@inheritDoc}
     */
    public function isInstalled(WritableRepositoryInterface $repo, PackageInterface $package)
    {
        return $repo->hasPackage($package);
    }

    /**
     * {@inheritDoc}
     */
    public function install(WritableRepositoryInterface $repo, PackageInterface $package)
    {
        $repo->addPackage(clone $package);
    }

    /**
     * {@inheritDoc}
     */
    public function update(WritableRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        if (!$repo->hasPackage($initial)) {
            throw new \InvalidArgumentException('Package is not installed: '.$initial);
        }

        $repo->removePackage($initial);
        $repo->addPackage(clone $target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(WritableRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$repo->hasPackage($package)) {
            // TODO throw exception again here, when update is fixed and we don't have to remove+install (see #125)
            return;
            throw new \InvalidArgumentException('Package is not installed: '.$package);
        }

        $repo->removePackage($package);
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        return '';
    }
}
