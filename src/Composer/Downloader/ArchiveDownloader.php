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
use Symfony\Component\Finder\Finder;
use Composer\IO\IOInterface;

/**
 * Base downloader for archives
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author François Pluchino <francois.pluchino@opendisplay.com>
 */
abstract class ArchiveDownloader extends FileDownloader
{
    /**
     * {@inheritDoc}
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    public function install(PackageInterface $package, $path, $output = true)
    {
        if ($output) {
            $this->io->writeError("  - Installing <info>" . $package->getName() . "</info> (<comment>" . $package->getFullPrettyVersion() . "</comment>): Extracting archive");
        } else {
            $this->io->writeError('Extracting archive', false);
        }

        $this->filesystem->ensureDirectoryExists($path);
        if (!$this->filesystem->isDirEmpty($path)) {
            throw new \RuntimeException('Expected empty path to extract '.$package.' into but directory exists: '.$path);
        }

        do {
            $temporaryDir = $this->config->get('vendor-dir').'/composer/'.substr(md5(uniqid('', true)), 0, 8);
        } while (is_dir($temporaryDir));

        $fileName = $this->getFileName($package, $path);

        try {
            $this->filesystem->ensureDirectoryExists($temporaryDir);
            try {
                $this->extract($package, $fileName, $temporaryDir);
            } catch (\Exception $e) {
                // remove cache if the file was corrupted
                parent::clearLastCacheWrite($package);
                throw $e;
            }

            $this->filesystem->unlink($fileName);

            $renameAsOne = false;
            if (!file_exists($path) || ($this->filesystem->isDirEmpty($path) && $this->filesystem->removeDirectory($path))) {
                $renameAsOne = true;
            }

            $contentDir = $this->getFolderContent($temporaryDir);
            $singleDirAtTopLevel = 1 === count($contentDir) && is_dir(reset($contentDir));

            if ($renameAsOne) {
                // if the target $path is clear, we can rename the whole package in one go instead of looping over the contents
                if ($singleDirAtTopLevel) {
                    $extractedDir = (string) reset($contentDir);
                } else {
                    $extractedDir = $temporaryDir;
                }
                $this->filesystem->rename($extractedDir, $path);
            } else {
                // only one dir in the archive, extract its contents out of it
                if ($singleDirAtTopLevel) {
                    $contentDir = $this->getFolderContent((string) reset($contentDir));
                }

                // move files back out of the temp dir
                foreach ($contentDir as $file) {
                    $file = (string) $file;
                    $this->filesystem->rename($file, $path . '/' . basename($file));
                }
            }

            $this->filesystem->removeDirectory($temporaryDir);
        } catch (\Exception $e) {
            // clean up
            $this->filesystem->removeDirectory($path);
            $this->filesystem->removeDirectory($temporaryDir);

            throw $e;
        }
    }

    /**
     * Extract file to directory
     *
     * @param string $file Extracted file
     * @param string $path Directory
     *
     * @throws \UnexpectedValueException If can not extract downloaded file to path
     */
    abstract protected function extract(PackageInterface $package, $file, $path);

    /**
     * Returns the folder content, excluding dotfiles
     *
     * @param  string         $dir Directory
     * @return \SplFileInfo[]
     */
    private function getFolderContent($dir)
    {
        $finder = Finder::create()
            ->ignoreVCS(false)
            ->ignoreDotFiles(false)
            ->notName('.DS_Store')
            ->depth(0)
            ->in($dir);

        return iterator_to_array($finder);
    }
}
