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
use Composer\Util\ProcessExecutor;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class GitDownloader extends VcsDownloader
{
    /**
     * {@inheritDoc}
     */
    public function doDownload(PackageInterface $package, $path)
    {
        $ref = $package->getSourceReference();
        $command = 'git clone %s %s && cd %2$s && git checkout %3$s && git reset --hard %3$s';
        $this->io->write("    Cloning ".$package->getSourceReference());

        $commandCallable = function($url) use ($ref, $path, $command) {
            return sprintf($command, escapeshellarg($url), escapeshellarg($path), escapeshellarg($ref));
        };

        $this->runCommand($commandCallable, $package->getSourceUrl(), $path);
        $this->setPushUrl($package, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function doUpdate(PackageInterface $initial, PackageInterface $target, $path)
    {
        $ref = $target->getSourceReference();
        $this->io->write("    Checking out ".$target->getSourceReference());
        $command = 'cd %s && git remote set-url origin %s && git fetch origin && git fetch --tags origin && git checkout %3$s && git reset --hard %3$s';

        $commandCallable = function($url) use ($ref, $path, $command) {
            return sprintf($command, escapeshellarg($path), escapeshellarg($url), escapeshellarg($ref));
        };

        $this->runCommand($commandCallable, $target->getSourceUrl());
        $this->setPushUrl($target, $path);
    }

    /**
     * {@inheritDoc}
     */
    protected function enforceCleanDirectory($path)
    {
        $command = sprintf('cd %s && git status --porcelain', escapeshellarg($path));
        if (0 !== $this->process->execute($command, $output)) {
            throw new \RuntimeException('Failed to execute ' . $command . "\n\n" . $this->process->getErrorOutput());
        }

        if (trim($output)) {
            throw new \RuntimeException('Source directory ' . $path . ' has uncommitted changes');
        }
    }

    /**
     * Runs a command doing attempts for each protocol supported by github.
     *
     * @param callable $commandCallable A callable building the command for the given url
     * @param string $url
     * @param string $path The directory to remove for each attempt (null if not needed)
     * @throws \RuntimeException
     */
    protected function runCommand($commandCallable, $url, $path = null)
    {
        $handler = array($this, 'outputHandler');

        // github, autoswitch protocols
        if (preg_match('{^(?:https?|git)(://github.com/.*)}', $url, $match)) {
            $protocols = array('git', 'https', 'http');
            foreach ($protocols as $protocol) {
                $url = $protocol . $match[1];
                if (0 === $this->process->execute(call_user_func($commandCallable, $url), $handler)) {
                    return;
                }
                if (null !== $path) {
                    $this->filesystem->removeDirectory($path);
                }
            }
            throw new \RuntimeException('Failed to checkout ' . $url .' via git, https and http protocols, aborting.' . "\n\n" . $this->process->getErrorOutput());
        }

        $command = call_user_func($commandCallable, $url);
        if (0 !== $this->process->execute($command, $handler)) {
            throw new \RuntimeException('Failed to execute ' . $command . "\n\n" . $this->process->getErrorOutput());
        }
    }

    public function outputHandler($type, $buffer)
    {
        if ($type !== 'out') {
            return;
        }
        if ($this->io->isVerbose()) {
            $this->io->write($buffer, false);
        }
    }

    protected function setPushUrl(PackageInterface $package, $path)
    {
        // set push url for github projects
        if (preg_match('{^(?:https?|git)://github.com/([^/]+)/([^/]+?)(?:\.git)?$}', $package->getSourceUrl(), $match)) {
            $pushUrl = 'git@github.com:'.$match[1].'/'.$match[2].'.git';
            $cmd = sprintf('git remote set-url --push origin %s', escapeshellarg($pushUrl));
            $this->process->execute($cmd, $ignoredOutput, $path);
        }
    }
}
