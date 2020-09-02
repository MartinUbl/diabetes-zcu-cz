<?php

use Phinx\Migration\AbstractMigration;

class AddUsersTable extends AbstractMigration
{
    public function change()
    {
        $tbl = $this->table('users');
        
        $tbl->addColumn('email', 'string')
            ->addColumn('password', 'string')
            ->addColumn('role', 'string', array('default' => 'user'));
        
        $tbl->create();
    }
}
