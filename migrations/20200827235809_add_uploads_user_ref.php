<?php

use Phinx\Migration\AbstractMigration;

class AddUploadsUserRef extends AbstractMigration
{
    public function change()
    {
        $this->table('uploads')
             ->addColumn('users_id', 'integer', array('null' => false))
             ->addForeignKey('users_id', 'users', 'id')
             ->update();
    }
}
