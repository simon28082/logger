<?php

namespace CrCms\Log;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\Logger;
use Illuminate\Log\ParsesLogConfiguration;
use Monolog\Handler\MongoDBHandler;
use Monolog\Logger as MongoLogger;

/**
 * Class MongoDBLogger.
 */
class MongoDBLogger
{
    use ParsesLogConfiguration;

    /**
     * @var DatabaseManager
     */
    protected $db;

    /**
     * @var Repository
     */
    protected $config;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * MongoDBLogger constructor.
     *
     * @param Repository $config
     * @param Dispatcher $events
     * @param DatabaseManager $db
     */
    public function __construct(DatabaseManager $db, Repository $config, Dispatcher $events)
    {
        $this->db = $db;
        $this->config = $config;
        $this->events = $events;
    }

    /**
     * @param array $config
     *
     * @return Logger
     */
    public function __invoke(array $config)
    {
        return new Logger($this->mongoLogger($config), $this->events);
    }

    /**
     * @param array $config
     *
     * @return MongoLogger
     */
    protected function mongoLogger(array $config): MongoLogger
    {
        $channel = $this->parseChannelName($config);

        $database = $this->parseDatabase($config);

        return new MongoLogger(
            $channel,
            [
                new MongoDBHandler(
                    $this->db->connection($config['database']['driver'])->getMongoClient(),
                    $database,
                    $config['database']['collection'],
                    $this->level($config)
                ),
            ]
        );
    }

    /**
     * parseDatabase
     *
     * @param array $config
     * @return string
     */
    protected function parseDatabase(array $config): string
    {
        return empty($config['database']['database']) ?
            $this->config->get("database.connections.{$config['database']['driver']}.database") :
            $config['database']['database'];
    }

    /**
     * channelName
     *
     * @param array $config
     * @return string
     */
    protected function parseChannelName(array $config): string
    {
        return empty($config['name']) ?
            $this->config->get('app.env', 'production') :
            $config['name'];
    }
}
