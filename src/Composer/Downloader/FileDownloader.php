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

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;

/**
 * Base downloader for file packages
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author François Pluchino <francois.pluchino@opendisplay.com>
 */
abstract class FileDownloader implements DownloaderInterface
{
    protected $io;
    private $bytesMax;
    private $firstCall;
    private $url;
    private $fileUrl;
    private $fileName;

    /**
     * Constructor.
     *
     * @param IOInterface  $io  The IO instance
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallationSource()
    {
        return 'dist';
    }

    /**
     * {@inheritDoc}
     */
    public function download(PackageInterface $package, $path)
    {
        $this->firstCall = true;
        $this->url = $package->getSourceUrl();
        $this->fileUrl = $package->getDistUrl();

        // init the progress bar
        $this->bytesMax = 0;

        $url = $package->getDistUrl();
        $checksum = $package->getDistSha1Checksum();

        if (!is_dir($path)) {
            if (file_exists($path)) {
                throw new \UnexpectedValueException($path.' exists and is not a directory');
            }
            if (!mkdir($path, 0777, true)) {
                throw new \UnexpectedValueException($path.' does not exist and could not be created');
            }
        }

        $fileName = rtrim($path.'/'.md5(time().rand()).'.'.pathinfo($url, PATHINFO_EXTENSION), '.');
        $this->fileName = $fileName;

        $this->io->write("  - Package <info>" . $package->getName() . "</info> (<comment>" . $package->getPrettyVersion() . "</comment>)");

        if (!extension_loaded('openssl') && (0 === strpos($url, 'https:') || 0 === strpos($url, 'http://github.com'))) {
            // bypass https for github if openssl is disabled
            if (preg_match('{^https?://(github.com/[^/]+/[^/]+/(zip|tar)ball/[^/]+)$}i', $url, $match)) {
                $url = 'http://nodeload.'.$match[1];
            } else {
                throw new \RuntimeException('You must enable the openssl extension to download files via https');
            }
        }

        $this->copy($this->url, $this->fileName, $this->fileUrl);
        $this->io->write('');

        if (!file_exists($fileName)) {
            throw new \UnexpectedValueException($url.' could not be saved to '.$fileName.', make sure the'
                .' directory is writable and you have internet connectivity');
        }

        if ($checksum && hash_file('sha1', $fileName) !== $checksum) {
            throw new \UnexpectedValueException('The checksum verification of the archive failed (downloaded from '.$url.')');
        }

        $this->io->write('    Unpacking archive');
        $this->extract($fileName, $path);

        $this->io->write('    Cleaning up');
        unlink($fileName);

        // If we have only a one dir inside it suppose to be a package itself
        $contentDir = glob($path . '/*');
        if (1 === count($contentDir)) {
            $contentDir = $contentDir[0];
            foreach (array_merge(glob($contentDir . '/.*'), glob($contentDir . '/*')) as $file) {
                if (trim(basename($file), '.')) {
                    rename($file, $path . '/' . basename($file));
                }
            }
            rmdir($contentDir);
        }

        $this->io->write('');
    }

    /**
     * {@inheritDoc}
     */
    public function update(PackageInterface $initial, PackageInterface $target, $path)
    {
        $fs = new Filesystem();
        $fs->removeDirectory($path);
        $this->download($target, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function remove(PackageInterface $package, $path)
    {
        $fs = new Filesystem();
        $fs->removeDirectory($path);
    }

    /**
     * Get notification action.
     *
     * @param integer $notificationCode The notification code
     * @param integer $severity         The severity level
     * @param string  $message          The message
     * @param integer $messageCode      The message code
     * @param integer $bytesTransferred The loaded size
     * @param integer $bytesMax         The total size
     */
    protected function callbackGet($notificationCode, $severity, $message, $messageCode, $bytesTransferred, $bytesMax)
    {
        switch ($notificationCode) {
            case STREAM_NOTIFY_AUTH_REQUIRED:
            case STREAM_NOTIFY_FAILURE:
                // for private repository returning 404 error when the authorization is incorrect
                $auth = $this->io->getAuthorization($this->url);
                $ps = $this->firstCall && 404 === $messageCode
                && null === $this->io->getLastUsername()
                && null === $auth['username'];

                if (404 === $messageCode && !$this->firstCall) {
                    throw new \RuntimeException("The '" . $this->fileUrl . "' URL not found");
                }

                $this->firstCall = false;

                // get authorization informations
                if (401 === $messageCode || $ps) {
                    if (!$this->io->isInteractive()) {
                        $mess = "The '" . $this->fileUrl . "' URL not found";

                        if (401 === $code || $ps) {
                            $mess = "The '" . $this->fileUrl . "' URL required the authorization.\nYou must be used the interactive console";
                        }

                        throw new \RuntimeException($mess);
                    }

                    $this->io->overwrite('    Authorization required:');
                    $username = $this->io->ask('      Username: ');
                    $password = $this->io->askAndHideAnswer('      Password: ');
                    $this->io->setAuthorization($this->url, $username, $password);

                    $this->copy($this->url, $this->fileName, $this->fileUrl);
                }
                break;

            case STREAM_NOTIFY_FILE_SIZE_IS:
                if ($this->bytesMax < $bytesMax) {
                    $this->bytesMax = $bytesMax;
                }
                break;

            case STREAM_NOTIFY_PROGRESS:
                if ($this->bytesMax > 0) {
                    $progression = 0;

                    if ($this->bytesMax > 0) {
                        $progression = round($bytesTransferred / $this->bytesMax * 100);
                    }

                    if (0 === $progression % 5) {
                        $this->io->overwrite("    Downloading: <comment>$progression%</comment>", false);
                    }
                }
                break;

            default:
                break;
        }
    }

    protected function copy($url, $fileName, $fileUrl)
    {
        // Handle system proxy
        $params = array('http' => array());

        if (isset($_SERVER['HTTP_PROXY'])) {
            // http(s):// is not supported in proxy
            $proxy = str_replace(array('http://', 'https://'), array('tcp://', 'ssl://'), $_SERVER['HTTP_PROXY']);

            if (0 === strpos($proxy, 'ssl:') && !extension_loaded('openssl')) {
                throw new \RuntimeException('You must enable the openssl extension to use a proxy over https');
            }

            $params['http'] = array(
                    'proxy'           => $proxy,
                    'request_fulluri' => true,
            );
        }

        if ($this->io->hasAuthorization($url)) {
            $auth = $this->io->getAuthorization($url);
            $authStr = base64_encode($auth['username'] . ':' . $auth['password']);
            $params['http'] = array_merge($params['http'], array('header' => "Authorization: Basic $authStr\r\n"));
        }

        $ctx = stream_context_create($params);
        stream_context_set_params($ctx, array("notification" => array($this, 'callbackGet')));

        $this->io->overwrite("    Downloading: <comment>connection...</comment>", false);
        @copy($fileUrl, $fileName, $ctx);
        $this->io->overwrite("    Downloading", false);
    }

    /**
     * Extract file to directory
     *
     * @param string $file Extracted file
     * @param string $path Directory
     *
     * @throws \UnexpectedValueException If can not extract downloaded file to path
     */
    protected abstract function extract($file, $path);
}
