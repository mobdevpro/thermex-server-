<?php
namespace MyApp;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Pusher implements WampServerInterface {
    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array();

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $this->subscribedTopics[$topic->getId()] = $topic;
    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onBlogEntry($entry) {
        echo 'entry: '.$entry;
        $entryData = json_decode($entry, true);

        // If the lookup topic object isn't set there is no one to publish to
        // if (!array_key_exists($entryData['category'], $this->subscribedTopics)) {
        //     return;
        // }

        // $topic = $this->subscribedTopics[$entryData['category']];

        // // re-send the data to all the clients subscribed to that category
        // $topic->broadcast($entryData);
    }

    /* The rest of our methods were as they were, omitted from docs to save space */
}