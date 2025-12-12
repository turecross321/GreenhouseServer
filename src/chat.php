<?php

namespace MyApp;

use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

const CONTROLLER = "controller";
const WORKER = "worker";

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $tunnels;


    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->tunnels = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // e.g. ws://192.168.30.1:80?type=controller
        // e.g. ws://192.168.30.1:80?type=worker

        $this->clients->attach($conn);
    }

    public function getClientType(ConnectionInterface $conn)
    {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $params);
        $type = $params['type'];

        return $type;
    }


    public function onMessage(ConnectionInterface $from, $msg)
    {
        $fromType = $this->getClientType($from);

        $parts = explode('|', $msg);

        if (count($parts) > 0) {
            $header = json_decode($parts[0]);
        } else {
            $header = json_decode($msg);
        }

        if (isset($header->receiveFrom)) {
            foreach ($header->receiveFrom as $tunnel) {
                if (!array_key_exists($tunnel, $this->tunnels)) {
                    $this->tunnels[$tunnel] = [];
                }

                echo "Adding " . $from->resourceId . " to receiveFrom:" . $tunnel . "\n";
                array_push($this->tunnels[$tunnel], $from);
            }
        }

        if (isset($header->stopReceivingFrom)) {
            foreach ($header->stopReceivingFrom as $tunnel) {
                if (!array_key_exists($tunnel, $this->tunnels)) {
                    continue;
                }

                $find_key = array_search($from, $this->tunnels[$tunnel]);
                echo "Removing " . $from->resourceId . " from receiveFrom:" . $tunnel . "\n";
                unset($this->tunnels[$tunnel][$find_key]);
            }
        }

        if (isset($header->sendTo)) {

            echo "Sending!\n";

            foreach ($header->sendTo as $key => $sendTo) {
                // "sendTo": [
                //   {"tunnel": "temp", "recipientType": "controller"}
                //   {"tunnel": "humidity", "recipientType": "controller"}
                //]


                $tunnel = $sendTo->tunnel;
                $recipientType = $sendTo->recipientType;

                if (!array_key_exists($tunnel, $this->tunnels)) {
                    // If the tunnel doesn't exist, create it.
                    echo "Tried sending message to tunnel that hasn't been added. Adding...\n";
                    $this->tunnels[$tunnel] = [];
                }

                // For each client that has joined the tunnel
                foreach ($this->tunnels[$tunnel] as $client) {
                    if ($this->getClientType($client) != $recipientType) {
                        continue;
                    }

                    $part = $parts[$key + 1];
                    $messageInfo = ['fromType' => $fromType, 'fromTunnel' => $tunnel];
                    $message = json_encode($messageInfo) . "|" . $part;

                    echo "Sending message from " . $from->resourceId . " to " . $tunnel . "\n";

                    // formatted message example: {"fromType": "worker", "fromTunnel": "temp"}|{"temperature": 12}

                    $client->send($message);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        foreach ($this->tunnels as $tunnel) {
            $find_key = array_search($conn, $this->tunnels[$tunnel]);
            unset($this->tunnels[$tunnel][$find_key]);
        }

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
