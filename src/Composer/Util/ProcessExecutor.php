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

namespace Composer\Util;

use Symfony\Component\Process\Process;

/**
 * @author Robert Schönthal <seroscho@googlemail.com>
 */
class ProcessExecutor
{
    static protected $timeout = 300;

    protected $errorOutput;

    /**
     * runs a process on the commandline
     *
     * @param string $command the command to execute
     * @param null   $output  the output will be written into this var if passed
     * @param string $cwd     the working directory
     * @return int statuscode
     */
    public function execute($command, &$output = null, $cwd = null)
    {
        $captureOutput = count(func_get_args()) > 1;
        $this->errorOutput = null;
        $process = new Process($command, $cwd, null, null, static::getTimeout());
        $process->run(function($type, $buffer) use ($captureOutput) {
            if ($captureOutput) {
                return;
            }

            echo $buffer;
        });

        if ($captureOutput) {
            $output = $process->getOutput();
        }

        $this->errorOutput = $process->getErrorOutput();

        return $process->getExitCode();
    }

    public function splitLines($output)
    {
        return ((string) $output === '') ? array() : preg_split('{\r?\n}', $output);
    }

    /**
     * Get any error output from the last command
     *
     * @return string
     */
    public function getErrorOutput()
    {
        return $this->errorOutput;
    }

    static public function getTimeout()
    {
        return static::$timeout;
    }

    static public function setTimeout($timeout)
    {
        static::$timeout = $timeout;
    }
}
