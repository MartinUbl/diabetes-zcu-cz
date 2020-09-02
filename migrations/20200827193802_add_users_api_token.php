<?php

use Phinx\Migration\AbstractMigration;

class AddUsersApiToken extends AbstractMigration
{
    public function change()
    {
        $this->table('users')
             ->addColumn('api_token', 'string', array('default' => null))
             ->addColumn('api_token_valid_to', 'datetime', array('default' => null))
             ->addIndex('api_token')
             ->update();
    }
}
