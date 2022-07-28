<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserPassResetToken extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
             ->addColumn('password_reset_token', 'string', array('null' => true, 'default' => null))
             ->addColumn('password_reset_token_valid_to', 'datetime', array('null' => true, 'default' => null))
             ->addIndex('password_reset_token')
             ->update();
    }
}
