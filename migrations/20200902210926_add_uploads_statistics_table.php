<?php

use Phinx\Migration\AbstractMigration;

class AddUploadsStatisticsTable extends AbstractMigration
{
    public function change()
    {
        $tbl = $this->table('uploads_statistics', array('id' => false));
        
        $tbl->addColumn('uploads_id', 'integer', array('null' => false))
            ->addColumn('last_measurement_date', 'datetime', array('null' => true, 'default' => null))
            ->addColumn('current_ig', 'float', array('null' => true, 'default' => null))
            ->addColumn('average_ig', 'float', array('null' => true, 'default' => null))
            ->addColumn('time_in_range', 'float', array('null' => true, 'default' => null));
        
        $tbl->addForeignKey('uploads_id', 'uploads', 'id');
        
        $tbl->create();
    }
}
