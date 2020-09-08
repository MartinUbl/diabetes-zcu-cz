<?php

use Phinx\Migration\AbstractMigration;

class RemoveUploadsStatisticsTable extends AbstractMigration
{
    public function up()
    {
        $this->table('uploads_statistics')->drop()->save();
    }
    
    public function down()
    {
        //
    }
}
