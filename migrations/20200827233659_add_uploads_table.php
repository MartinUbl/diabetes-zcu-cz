<?php

use Phinx\Migration\AbstractMigration;

class AddUploadsTable extends AbstractMigration
{
    public function change()
    {
        $tbl = $this->table('uploads', array('id' => true));
        
        $tbl->addColumn('date', 'datetime')
            ->addColumn('device_info', 'string')
            ->addColumn('upload_id', 'string', array('null' => true, 'default' => null));
        
        $tbl->create();
    }
}
