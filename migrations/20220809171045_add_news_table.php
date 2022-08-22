<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddNewsTable extends AbstractMigration
{
    public function change(): void
    {
        $tbl = $this->table('news');
        
        $tbl->addColumn('title', 'string', ['null' => false])
            ->addColumn('short_content', 'string', ['null' => false])
            ->addColumn('full_content', 'string', ['null' => true])
            ->addColumn('users_id', 'integer')
            ->addColumn('published_on', 'datetime')
            ->addColumn('social_media_posted', 'boolean', ['null' => false, 'default' => false]);
        
        $tbl->addForeignKey('users_id', 'users', 'id');
     
        $tbl->create();
    }
}
