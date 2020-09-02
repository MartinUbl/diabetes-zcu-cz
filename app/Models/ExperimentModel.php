<?php

namespace App\Models;

use Nette\Security\Passwords;

/**
 * Model for managing experiments
 */
class ExperimentModel extends BaseModel
{
    public $implicitTable = 'experiment';
    
    /**
     * Retrieves selection of all experiments
     * @return \Nette\Database\Table\Selection
     */
    public function getAllExperiments()
    {
        return $this->getTable();
    }

    /**
     * Inserts a new experiment
     * @param string $name
     */
    public function addExperiment($name)
    {
        $this->getTable()->insert(array(
            'name' => $name
        ));
    }
    
    /**
     * Removes an experiment record
     * @param int $id
     */
    public function deleteExperiment($id)
    {
        $this->getTable()->where('id', $id)->delete();
    }
    
    /**
     * Retrieves experiment by its name
     * @param string $name
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getExperimentByName($name)
    {
        return $this->getTable()->where('name', $name)->fetch();
    }
    
    /**
     * Retrieves a selection of experiment participants
     * @param int $id
     * @return \Nette\Database\Table\Selection
     */
    public function getExperimentParticipants($id)    
    {
        return $this->table('experiment_participant')->where('experiment_id', $id);
    }
    
    /**
     * Adds experiment participant
     * @param int $experiment_id
     * @param int $users_id
     */
    public function addExperimentParticipant($experiment_id, $users_id)
    {
        $this->table('experiment_participant')->insert(array(
            'experiment_id' => $experiment_id,
            'users_id' => $users_id
        ));
    }
    
    /**
     * Removes experiment participant
     * @param int $experiment_id
     * @param int $users_id
     */
    public function removeExperimentParticipant($experiment_id, $users_id)
    {
        $this->table('experiment_participant')->where('experiment_id', $experiment_id)->where('users_id', $users_id)->delete();
    }
}
