<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDownloadTokenTable extends AbstractMigration
{
    public function change(): void
    {
        $tbl = $this->table('download_token');
        
        $tbl->addColumn('category', 'string', ['null' => false])
            ->addColumn('token', 'string', ['null' => false])
            ->addColumn('valid_to', 'datetime', ['null' => false])
            ->addColumn('used', 'boolean', ['default' => false])
            ->addColumn('requester_name', 'string', ['null' => false])
            ->addColumn('requester_phone', 'string', ['null' => false])
            ->addColumn('requester_address', 'string', ['null' => false])
            ->addColumn('requester_email', 'string', ['null' => false])
            ->addColumn('requester_ip', 'string', ['null' => false]);
        
        $tbl->addIndex(['category', 'token']);
        
        $tbl->create();
    }
}
