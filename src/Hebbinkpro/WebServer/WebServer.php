<?php
/*
 * MIT License
 *
 * Copyright (c) 2025 Hebbinkpro
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Hebbinkpro\WebServer;

use Hebbinkpro\WebServer\exception\WebServerAlreadyStartedException;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\server\SslSettings;
use Hebbinkpro\WebServer\utils\log\PrefixedThreadSafeLogger;
use pocketmine\plugin\PluginBase;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\VersionInfo;

class WebServer
{
    public const VERSION_NAME = "PMMP-WebServer";
    public const VERSION = "0.5.0";
    public const PREFIX = "WebServer";

    private PluginBase $plugin;
    private HttpServerInfo $serverInfo;

    private ThreadSafeLogger $logger;

    private ?HttpServer $httpServer = null;

    /**
     * Construct a new WebServer
     * @param PluginBase $plugin
     * @param HttpServerInfo $serverInfo
     */
    public function __construct(PluginBase $plugin, HttpServerInfo $serverInfo)
    {
        $this->plugin = $plugin;
        $this->serverInfo = $serverInfo;
        $this->logger = new PrefixedThreadSafeLogger($this->plugin->getServer()->getLogger(), self::PREFIX);
    }

    /**
     * Get the server name to use in the server header
     * @return string PMMP-WebServer/x.x.x PocketMine-MP/x.x.x
     */
    public static function getServerName(): string
    {
        return self::VERSION_NAME . "/" . self::VERSION . " " . VersionInfo::NAME . "/" . VersionInfo::BASE_VERSION;
    }

    /**
     * Let the WebServer detect if there is a folder called "cert" in the plugin data for an SSL certificate.
     * And activates it in the server info.
     *
     * - Certificate file: \<domain\>.cert file
     * - Private key file: \<domain\>.pem file, not required
     *
     * WARNING: You can only call this function BEFORE you have started the HTTP server, otherwise the detected SSL CANNOT be used by the web server.
     * @param string|null $domain the domain to use, when no domain is given, the certificate will automatically be detected
     * @param string $folder the folder inside the plugin data containing the certificate
     * @return bool true if SSL is detected, false otherwise
     */
    public function detectSSL(string $domain = null, string $folder = "cert", ?string $passphrase = null, ?string $ciphers = null): bool
    {
        $certFolder = $this->plugin->getDataFolder() . $folder;

        // no such folder
        if (!is_dir($certFolder)) return false;

        $cert = null;
        $pem = null;

        // we got a domain
        if ($domain !== null) {
            $cert = $certFolder . "/$domain.cert";
            if (!is_file($cert)) return false;

            $pem = $certFolder . "/$domain.pem";
            if (is_file($pem)) $pem = null;
        }

        // the cert or pem is not yet set
        if ($cert === null || $pem === null) {
            // search for the first certificate
            $files = scandir($certFolder);
            if ($files === false) return false;

            $certs = [];
            $pems = [];

            foreach ($files as $file) {
                if (str_ends_with($file, ".cert")) $certs[substr($file, 0, -5)] = $certFolder . "/$file";
                else if (str_ends_with($file, ".pem")) $pems[substr($file, 0, -4)] = $certFolder . "/$file";
            }

            if (sizeof($certs) == 0) return false;

            if ($cert === null) {
                $domain = array_key_first($certs);
                $cert = $certs[$domain];
            }

            if ($pem === null && sizeof($pems) > 0) {
                $pem = $pems[$domain] ?? $pems[array_key_first($pems)] ?? null;
            }
        }


        if ($ciphers === null) $ssl = new SslSettings($cert, $pem, $passphrase);
        else $ssl = new SslSettings($cert, $pem, $passphrase, $ciphers);

        $this->serverInfo->setSsl($ssl);

        return true;
    }

    /**
     * @return HttpServerInfo
     */
    public function getServerInfo(): HttpServerInfo
    {
        return $this->serverInfo;
    }

    /**
     * Start the web server
     * @return void
     * @throws WebServerAlreadyStartedException
     */
    public function start(): void
    {
        if ($this->httpServer !== null) throw new WebServerAlreadyStartedException();

        $classLoader = $this->plugin->getServer()->getLoader();
        $classLoader->addPath("Hebbinkpro\\WebServer", __DIR__);

        $this->httpServer = new HttpServer($this->serverInfo, $classLoader, $this->logger);

        $this->logger->info("Starting the webserver at: {$this->serverInfo->getAddress()}/");
        $this->httpServer->start();

    }

    /**
     * Get if the web server is started
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->httpServer !== null;
    }

    /**
     * Stop the web server
     * @return void
     */
    public function close(): void
    {
        if ($this->httpServer === null) {
            $this->logger->warning("Could not stop the web server, it is not running.");
            return;
        }

        $this->logger->info("Stopping the web server...");
        // join the http server thread
        $this->httpServer->join();
        $this->logger->info("The web server has been stopped.");

    }
}