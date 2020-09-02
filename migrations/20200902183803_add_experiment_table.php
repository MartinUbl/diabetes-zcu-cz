<?php

use Phinx\Migration\AbstractMigration;

class AddExperimentTable extends AbstractMigration
{
    public function change()
    {
        $tbl = $this->table('experiment', array('id' => true));
        
        $tbl->addColumn('name', 'string', array('null' => false));
        
        $tbl->create();
    }
}
