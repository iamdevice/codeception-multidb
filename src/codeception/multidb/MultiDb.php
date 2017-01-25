<?php

namespace Codeception\Extension;

use Codeception\Configuration;
use Codeception\Exception\ModuleConfigException;
use Codeception\Exception\ModuleException;
use Codeception\Lib\Driver\Db as Driver;
use Codeception\Module;

class MultiDb extends Module
{
    /**
     * @var array
     */
    public $connections = [];

    /**
     * @var Driver[]
     */
    protected $drivers = [];

    protected $config = [
        'connections' => null
    ];

    /**
     * @var array
     */
    protected $sql = [];

    /**
     * @var array
     */
    protected $insertedRows = [];

    /**
     * @var array
     */
    protected $requiredFields = ['connections'];

    /**
     * @var array
     */
    protected $connectionRequiredFields = ['dsn', 'user', 'password'];

    /**
     * @var array
     */
    protected $populated = [];

    public function _initialize()
    {
        $validConfig = false;

        if (is_array($this->config['connections'])) {
            foreach ($this->config['connections'] as $db => $connectionConfig) {
                $params = array_keys($connectionConfig);

                $this->populated[$db] = $connectionConfig['populate'];

                if (array_intersect($this->connectionRequiredFields, $params) == $this->connectionRequiredFields) {
                    $validConfig = true;
                }

                if (!$validConfig) {
                    throw new ModuleConfigException(
                        __CLASS__,
                        "\nOptions: " . implode(', ', $this->connectionRequiredFields) . " are required\n
                            Please, update the configuration and set all the required fields\n\n"
                    );
                }
            }
        }

        foreach ($this->config['connections'] as $db => $connectionConfig) {
            $this->connect($db);

            if ($connectionConfig['dump'] && ($connectionConfig['populate'] || $connectionConfig['cleanup'])) {
                $this->readSql($db);
            }

            if ($connectionConfig['populate']) {
                if ($connectionConfig['cleanup']) {
                    $this->cleanup($db);
                }

                $this->loadDump($db);
            }

            if ($connectionConfig['reconnect']) {
                $this->disconnect($db);
            }
        }
    }

    private function readSql($connection)
    {
        if (!file_exists(Configuration::projectDir() . $this->config['dump'])) {
            throw new ModuleConfigException(
                __CLASS__,
                "\nFile with dump doesn't exist.\n"
                . "Please, check path for sql file: "
                . $this->config['dump']
            );
        }

        $sql = file_get_contents(Configuration::projectDir() . $this->config['dump']);

        // remove C-style comments (except MySQL directives)
        $sql = preg_replace('%/\*(?!!\d+).*?\*/%s', '', $sql);

        if (!empty($sql)) {
            // split SQL dump into lines
            $this->sql[$connection] = preg_split('/\r\n|\n|\r/', $sql, -1, PREG_SPLIT_NO_EMPTY);
        }
    }

    protected function cleanup($connection)
    {
        $dbh = $this->drivers[$connection];
        if (!$dbh) {
            throw new ModuleConfigException(
                __CLASS__,
                'No connection to database. Remove this module from config if you don\'t need database repopulation'
            );
        }

        try {
            // don't clear database for empty dump
            if (!count($this->sql[$connection])) {
                return;
            }
            $this->drivers[$connection]->cleanup();
        } catch (\Exception $e) {
            throw new ModuleException(__CLASS__, $e->getMessage());
        }
    }

    protected function loadDump($connection)
    {
        if (!array_key_exists($connection, $this->sql)) {
            return null;
        }

        try {
            $this->drivers[$connection]->load($this->sql[$connection]);
        } catch (\PDOException $e) {
            throw new ModuleException(
                __CLASS__,
                $e->getMessage() . "\nSQL query being executed: " . $this->drivers[$connection]->sqlToRun
            );
        }
    }

    private function connect($connection)
    {
        $config = $this->config['connections'][$connection];

        try {
            $this->drivers[$connection] = Driver::connect($config['dsn'], $config['user'], $config['password']);
        } catch (\PDOException $e) {
            $msg = $e->getMessage();

            if ($msg == 'could not find driver') {
                list ($missingDriver, ) = explode(':', $this->config['dsn'], 2);
                $msg = "could not find {$missingDriver} driver";
            }

            throw new ModuleException(__CLASS__, $msg . ' while creating PDO connection');
        }

        $this->connections[$connection] = $this->drivers[$connection]->getDbh();
    }

    private function disconnect($connection)
    {
        unset($this->connections[$connection]);
        unset($this->drivers[$connection]);
    }
}