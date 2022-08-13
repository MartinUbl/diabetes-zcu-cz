<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIcarusUploadsTable extends AbstractMigration
{
    public function change(): void
    {
        $tbl = $this->table('icarus_uploads');
        
        $tbl->addColumn('upload_id', 'string', ['null' => true])
            ->addColumn('score', 'integer', ['null' => false])
            ->addColumn('player_qualification', 'integer', ['null' => false])
            ->addColumn('disease_code', 'string', ['null' => false])
            ->addColumn('nickname', 'string', ['null' => false])
            ->addColumn('difficulty', 'integer', ['null' => false])
            ->addColumn('date', 'datetime', ['null' => false]);
        
        $tbl->addIndex('upload_id');
        $tbl->addIndex('difficulty');
        
        $tbl->create();
    }
}