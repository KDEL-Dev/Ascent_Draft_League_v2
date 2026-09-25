<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class DraftSocket implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "New connection: {$conn->resourceId}\n";
        echo "Connected clients: {$this->clients->count()}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Message received from {$from->resourceId}: {$msg}\n";

        $data = json_decode($msg, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            echo "Invalid JSON received.\n";
            return;
        }

        $this->broadcast($data);
    }

    protected function broadcast(array $data)
    {
        $message = json_encode($data);

        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        echo "Connection closed: {$conn->resourceId}\n";
        echo "Connected clients: {$this->clients->count()}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";

        $this->clients->detach($conn);
        $conn->close();
    }
}


$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new DraftSocket()
        )
    ),
    8080
);

echo "WebSocket server started on port 8080\n";

$server->run();
