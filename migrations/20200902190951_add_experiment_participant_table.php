<?php

use Phinx\Migration\AbstractMigration;

class AddExperimentParticipantTable extends AbstractMigration
{
    public function change()
    {
        $tbl = $this->table('experiment_participant', array('id' => false, 'primary_key' => array('experiment_id', 'users_id')));
        
        $tbl->addColumn('experiment_id', 'integer', array('null' => false))
            ->addColumn('users_id', 'integer', array('null' => false));
        
        $tbl->addForeignKey('experiment_id', 'experiment', 'id')
            ->addForeignKey('users_id', 'users', 'id');
        
        $tbl->create();
    }
}
