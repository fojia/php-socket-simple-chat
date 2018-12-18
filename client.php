<?php

namespace SimpleChat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    /**
     * Storage for connections
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * Messages history storage
     * @var array
     */
    protected $lastMessages = [];

    /**
     * Count messages in storage
     * @var int
     */
    protected $countHistoryMessages = 10;

    /**
     * Storage for input and response data
     * @var
     */
    protected $data;

    /**
     * @return string
     */
    public function getData(): string
    {
        return json_encode($this->data);
    }

    /**
     * @param string $data
     */
    public function setData(string $data): void
    {
        $this->data = json_decode($data);
    }

    /**
     * Get last messages from history storage
     * @return array
     */
    public function getLastMessages(): array
    {
        return $this->lastMessages;
    }

    /**
     * Add to history storage last message
     * @param  string $lastMessages
     * @return void
     */
    public function setLastMessages(string $lastMessages): void
    {
        $this->lastMessages[] = $lastMessages;
        $this->lastMessages = array_slice($this->lastMessages, '-' . $this->countHistoryMessages);
    }

    /**
     * Create connection storage
     * Chat constructor.
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    /**
     * Execute for create new connection
     * @param ConnectionInterface $connection
     * @return void
     */
    public function onOpen(ConnectionInterface $connection): void
    {
        // Store the new connection to send messages to later
        $this->clients->attach($connection);
        $connection->send(json_encode(['last_messages' => $this->getLastMessages()]));
        $this->consoleMessage('New connection! (' . $connection->resourceId . ') ');
    }

    /**
     * @param ConnectionInterface $from
     * @param string $message
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $message): void
    {
        $this->setData($message);
        $this->setLastMessages($message);
        foreach ($this->clients as $client) {
            //Don't send own
            if ($from !== $client) {
                $client->send($this->getData());
            }
        }
    }

    /**
     * Execte when connection close, or if no message no longer
     * @param ConnectionInterface $connection
     * @return void
     */
    public function onClose(ConnectionInterface $connection): void
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($connection);
        $this->consoleMessage('Connection ' . $connection->resourceId . ' has disconnected');
    }

    /**
     * Execute when was error
     * @param ConnectionInterface $connection
     * @param \Exception $e
     * @return void
     */
    public function onError(ConnectionInterface $connection, \Exception $e): void
    {
        $this->consoleMessage('An error has occured: ' . $e->getMessage());
        $connection->close();
    }

    /**
     * Write message to console for tracking proccess
     * @param $message
     * @return void
     */
    public function consoleMessage(string $message): void
    {
        echo $message . PHP_EOL;
    }
}

