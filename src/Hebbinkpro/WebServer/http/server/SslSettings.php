<?php

namespace Hebbinkpro\WebServer\http\server;

use pmmp\thread\ThreadSafe;

class SslSettings extends ThreadSafe
{
    /*
     * The TLSv2 and TLSv3 ciphers given below are taken from the Intermediate compatibility Server Side TLS from https://wiki.mozilla.org/Security/Server_Side_TLS
     */

    /**
     * Modern compatibility
     *
     * For services with clients that support TLS 1.3 and don't need backward compatibility, the Modern configuration provides an extremely high level of security.
     * - Protocols: TLS 1.3
     * - Certificate type: ECDSA (P-256)
     * - TLS curves: X25519, prime256v1, secp384r1
     * - HSTS: max-age=63072000 (two years)
     * - Certificate lifespan: 90 days
     * - Cipher preference: client chooses
     */
    public const MODERN_CIPHERS = "TLS_AES_128_GCM_SHA256,TLS_AES_256_GCM_SHA384,TLS_CHACHA20_POLY1305_SHA256";

    /**
     * Intermediate compatibility (recommended)
     *
     * For services that don't need compatibility with legacy clients such as Windows XP or old versions of OpenSSL. This is the recommended configuration for the vast majority of services, as it is highly secure and compatible with nearly every client released in the last five (or more) years.
     * - Protocols: TLS 1.2, TLS 1.3
     * - TLS curves: X25519, prime256v1, secp384r1
     * - Certificate type: ECDSA (P-256) (recommended), or RSA (2048 bits)
     * - DH parameter size: 2048 (ffdhe2048, RFC 7919)
     * - HSTS: max-age=63072000 (two years)
     * - Certificate lifespan: 90 days (recommended) to 366 days
     * - Cipher preference: client chooses
     */
    public const INTERMEDIATE_CIPHERS = "TLS_AES_128_GCM_SHA256,TLS_AES_256_GCM_SHA384,TLS_CHACHA20_POLY1305_SHA256,ECDHE-ECDSA-AES128-GCM-SHA256,ECDHE-RSA-AES128-GCM-SHA256,ECDHE-ECDSA-AES256-GCM-SHA384,ECDHE-RSA-AES256-GCM-SHA384,ECDHE-ECDSA-CHACHA20-POLY1305,ECDHE-RSA-CHACHA20-POLY1305,DHE-RSA-AES128-GCM-SHA256,DHE-RSA-AES256-GCM-SHA384,DHE-RSA-CHACHA20-POLY1305";

    /**
     * Old backward compatibility.
     *
     * This configuration is compatible with a number of very old clients, and should be used only as a last resort.
     * - Protocols: TLS 1.0, TLS 1.1, TLS 1.2, TLS 1.3
     * - TLS curves: X25519, prime256v1, secp384r1
     * - Certificate curve: None
     * - DH parameter size: 1024 (generated with openssl dhparam 1024)
     * - HSTS: max-age=63072000 (two years)
     * - Certificate lifespan: 90 days (recommended) to 366 days
     * - Cipher preference: server chooses
     */
    public const OLD_CIPHERS = "TLS_AES_128_GCM_SHA256,TLS_AES_256_GCM_SHA384,TLS_CHACHA20_POLY1305_SHA256,ECDHE-ECDSA-AES128-GCM-SHA256,ECDHE-RSA-AES128-GCM-SHA256,ECDHE-ECDSA-AES256-GCM-SHA384,ECDHE-RSA-AES256-GCM-SHA384,ECDHE-ECDSA-CHACHA20-POLY1305,ECDHE-RSA-CHACHA20-POLY1305,DHE-RSA-AES128-GCM-SHA256,DHE-RSA-AES256-GCM-SHA384,DHE-RSA-CHACHA20-POLY1305,ECDHE-ECDSA-AES128-SHA256,ECDHE-RSA-AES128-SHA256,ECDHE-ECDSA-AES128-SHA,ECDHE-RSA-AES128-SHA,ECDHE-ECDSA-AES256-SHA384,ECDHE-RSA-AES256-SHA384,ECDHE-ECDSA-AES256-SHA,ECDHE-RSA-AES256-SHA,DHE-RSA-AES128-SHA256,DHE-RSA-AES256-SHA256,AES128-GCM-SHA256,AES256-GCM-SHA384,AES128-SHA256,AES256-SHA256,AES128-SHA,AES256-SHA,DES-CBC3-SHA";


    private string $localCert;
    private ?string $localPk;
    private ?string $passphrase;
    private string $ciphers;

    /**
     * @param string $localCert file path of the certificate file
     * @param string|null $localPk file path of the private key file, if not included in the certificate file
     * @param string|null $passphrase the passphrase when the private key is encrypted with one
     * @param string $ciphers the ciphers to use in the ssl connection, Default: Intermediate compatibility ciphers
     */
    public function __construct(string $localCert, ?string $localPk = null, ?string $passphrase = null, string $ciphers = self::INTERMEDIATE_CIPHERS)
    {
        $this->localCert = $localCert;
        $this->localPk = $localPk;
        $this->passphrase = $passphrase;
        $this->ciphers = $ciphers;
    }

    /**
     * Get the SSL stream context options
     * @return array{ssl: array{local_cert: string, ciphers: string, local_pk?: string, passphrase?: string}}
     */
    public function getContextOptions(): array
    {
        // set the options that are always available
        $options = [
            "local_cert" => $this->localCert,
            "ciphers" => $this->ciphers,
            "verify_peer" => false
        ];

        // set the optional options
        if ($this->localPk !== null) $options["local_pk"] = $this->localPk;
        if ($this->passphrase !== null) $options["passphrase"] = $this->passphrase;

        // return the ssl context options
        return ["ssl" => $options];
    }
}