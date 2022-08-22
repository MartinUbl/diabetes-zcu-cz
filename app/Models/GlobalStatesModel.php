<?php

namespace App\Models;

/**
 * Model for managing global states
 */
class GlobalStatesModel extends BaseModel
{
    public $implicitTable = 'global_states';
    
    public function __construct(\Nette\Database\Context $database, \Nette\DI\Container $c) {
        parent::__construct($database, $c);
    }

    /**
     * Retrieves state value by its key
     * @param string $key
     * @return string|null
     */
    public function getState($key)
    {
        $rec = $this->getTable()->where('key', $key)->fetch();
        
        if (!$rec)
            return null;
        
        return $rec->value;
    }

    /**
     * Retrieves an associative array of all states
     * @return array
     */
    public function getAllStates()
    {
        return $this->getTable()->fetchPairs('key', 'value');
    }

    /**
     * Updates a given global state with given value
     * @param string $key
     * @param string $value
     */
    public function updateState($key, $value)
    {
        if ($this->getState($key))
            $this->getTable()->where('key', $key)->update([ 'value' => $value ]);
        else
            $this->getTable()->insert([ 'key' => $key, 'value' => $value ]);
    }
}
