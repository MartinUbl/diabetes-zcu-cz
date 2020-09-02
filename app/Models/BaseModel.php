<?php

namespace App\Models;

use Nette;

abstract class BaseModel
{
    /**
     * @var \Nette\Database\Context
     */
    public $database;

    /**
     * @var string
     */
    public $implicitTable = NULL;

    /**
     * @param \Nette\Database\Context $database
     */
    public function __construct(\Nette\Database\Context $database, \Nette\DI\Container $c)
    {
        $this->database = $database;
    }

    /**
     * Set implicit table for derived model
     * @param string $table - table name
     */
    public function setImplicitTable($table)
    {
        $this->implicitTable = $table;
    }

    /**
     * Selecting table
     * @param string $table - table name
     * @return \Nette\Database\Table\Selection
     */
    public function table($table)
    {
        return $this->database->table($table);
    }

    /**
     * Returns implicit table (preselected with setImplicitTable method)
     * @return \Nette\Database\Table\Selection
     */
    public function getTable()
    {
        return $this->table($this->implicitTable);
    }
}

