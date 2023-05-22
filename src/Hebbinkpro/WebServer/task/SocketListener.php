<?php

namespace Hebbinkpro\WebServer\task;

use Hebbinkpro\WebServer\WebClient;

interface SocketListener
{
    /**
     * Close the listener
     * @return void
     */
    public function close(): void;

    /**
     * @return WebClient[]
     */
    public function getClients(): array;
}