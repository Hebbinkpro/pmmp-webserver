# HTTPS

Since v0.4.0, the web server also supports SSL certificates which make it possible to run an HTTPS server for a more
secure connection than HTTP.<br>
For enabling HTTPS on your webserver you have to add `SslSettings` to the `HttpServerInfo` BEFORE you start the web
server. This can be done in multiple ways.<br>

1. Let the WebServer detect SSL certificates in the `cert` folder of your plugin data by
   calling: `$webServer->detectSSL()`.
2. Create a new `SslSettings` by calling `$ssl = new SslSettings(...)` and add it to
   the `HttpServerInfo`: `new HttpServerInfo(..., $ssl)` or by calling `$serverInfo->setSSL($ssl)`.
   If you add the SSL settings AFTER you started the server, HTTPS will not be applied and your server will function as
   a normal HTTP server.