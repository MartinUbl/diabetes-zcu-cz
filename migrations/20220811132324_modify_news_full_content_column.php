<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyNewsFullContentColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('news')->changeColumn('full_content', 'text', array('null' => true))->update();
    }
}
