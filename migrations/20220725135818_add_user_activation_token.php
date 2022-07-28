<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserActivationToken extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
             ->addColumn('activation_token', 'string', array('null' => true, 'default' => null))
             ->addColumn('activation_token_valid_to', 'datetime', array('null' => true, 'default' => null))
             ->addIndex('activation_token')
             ->update();
    }
}
