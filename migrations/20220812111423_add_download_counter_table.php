<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDownloadCounterTable extends AbstractMigration
{
    public function change(): void
    {
        $tbl = $this->table('download_counter', ['id' => false, 'primary_key' => ['name', 'month', 'year']]);
        
        $tbl->addColumn('name', 'string')
            ->addColumn('month', 'integer')
            ->addColumn('year', 'integer')
            ->addColumn('counter', 'integer');
        
        $tbl->create();
    }
}
