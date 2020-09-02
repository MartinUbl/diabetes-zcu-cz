<?php

use Phinx\Migration\AbstractMigration;

class AddUploadExperimentLink extends AbstractMigration
{
    public function change()
    {
        $this->table('uploads')
             ->addColumn('experiment_id', 'integer', array('null' => true, 'default' => null))
             ->addForeignKey('experiment_id', 'experiment', 'id')
             ->update();
    }
}
