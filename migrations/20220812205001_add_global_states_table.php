<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddGlobalStatesTable extends AbstractMigration
{
    public function change(): void
    {
        $tbl = $this->table('global_states', ['id' => false, 'primary_key' => 'key']);
        
        $tbl->addColumn('key', 'string', ['null' => false])
            ->addColumn('value', 'string', ['null' => true]);
        
        $tbl->create();
        
        // "dummy" data for states table
        $tbl->insert([
            ['key' => 'icarus_minimal_version', 'value' => '2019-01-01-10-55'],
            ['key' => 'icarus_current_version', 'value' => '2019-10-01-12-20'],
        ]);
        $tbl->saveData();
    }
}
