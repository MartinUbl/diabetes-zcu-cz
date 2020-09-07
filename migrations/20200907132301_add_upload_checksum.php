<?php

use Phinx\Migration\AbstractMigration;

class AddUploadChecksum extends AbstractMigration
{
    public function change()
    {
        $this->table('uploads')->addColumn('checksum', 'string', array('null' => true, 'default' => null, 'limit' => 64))->update();
    }
}
