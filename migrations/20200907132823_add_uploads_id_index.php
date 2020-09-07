<?php

use Phinx\Migration\AbstractMigration;

class AddUploadsIdIndex extends AbstractMigration
{
    public function change()
    {
        $this->table('uploads')->addIndex('upload_id')->update();
    }
}
