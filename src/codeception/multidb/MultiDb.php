<?php

namespace Codeception\Extension;

use Codeception\Lib\Driver\Db;
use Codeception\Module;

class MultiDb extends Module
{
    /**
     * @var array
     */
    protected $connections = [];

    /**
     * @var Db[]
     */
    protected $drivers = [];

    protected $config = [
        'connectors' => null
    ];

    /**
     * @var array
     */
    protected $insertedRows = [];

    protected $requiredFields = ['connections'];

    protected $connectionRequiredFields = ['dsn', 'user', 'password'];

    public function _initialize()
    {
        if (is_array($this->config['connectors'])) {
            print_r($this->config['connectors']);
        }
    }

    private function readSql()
    {

    }
}