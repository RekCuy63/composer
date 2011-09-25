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

namespace Composer\Downloader;

use Composer\Package\PackageInterface;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class GitDownloader implements DownloaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function download(PackageInterface $package, $path, $url, $checksum = null, $useSource = false)
    {
        system('git clone '.escapeshellarg($url).' -b master '.escapeshellarg($path));

        // TODO non-source installs:
        // system('git archive --format=tar --prefix='.escapeshellarg($package->getName()).' --remote='.escapeshellarg($url).' master | tar -xf -');
    }

    /**
     * {@inheritDoc}
     */
    public function update(PackageInterface $initial, PackageInterface $target, $path, $useSource = false)
    {
        $cwd = getcwd();
        chdir($path);
        system('git pull');
        chdir($cwd);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(PackageInterface $package, $path, $useSource = false)
    {
        echo 'rm -rf '.$path; // TODO
    }
}
