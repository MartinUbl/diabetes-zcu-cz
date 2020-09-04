<?php

use Phinx\Migration\AbstractMigration;

class AddUserFields extends AbstractMigration
{
    public function change()
    {
        $tbl = $this->table('users');
        
        $tbl->addColumn('first_name', 'string', array('null' => true, 'default' => null))
            ->addColumn('last_name', 'string', array('null' => true, 'default' => null))
            ->addColumn('birthdate', 'date', array('null' => true, 'default' => null))
            ->addColumn('diagnosis', 'string', array('null' => true, 'default' => null));
        
        $tbl->update();
    }
}
