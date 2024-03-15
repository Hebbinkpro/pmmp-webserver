<?php

namespace Hebbinkpro\WebServer;

use Hebbinkpro\WebServer\exception\WebServerAlreadyStartedException;
use Hebbinkpro\WebServer\http\server\HttpServer;
use Hebbinkpro\WebServer\http\server\HttpServerInfo;
use Hebbinkpro\WebServer\http\server\SslSettings;
use pocketmine\plugin\PluginBase;
use pocketmine\VersionInfo;

class WebServer
{
    public const VERSION_NAME = "PMMP-WebServer";
    public const VERSION = "1.0.0";
    public const PREFIX = "[WebServer]";

    private PluginBase $plugin;
    private HttpServerInfo $serverInfo;

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


        $this->httpServer = new HttpServer($this->serverInfo, $classLoader);
        $this->httpServer->start();

        $this->plugin->getLogger()->notice(self::PREFIX . " The web server is running at: {$this->serverInfo->getAddress()}/");
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
            $this->plugin->getLogger()->warning(self::PREFIX . " Could not stop the web server, it is not running.");
            return;
        }

        $this->plugin->getLogger()->info(self::PREFIX . " Stopping the web server...");
        // join the http server thread
        $this->httpServer->join();
        $this->plugin->getLogger()->notice(self::PREFIX . " The web server has been stopped.");

    }

    /**
     * Get the server name to use in the server header
     * @return string PMMP-WebServer/x.x.x PocketMine-MP/x.x.x
     */
    public static function getServerName(): string
    {
        return self::VERSION_NAME . "/" . self::VERSION . " " . VersionInfo::NAME . "/" . VersionInfo::BASE_VERSION;
    }
}