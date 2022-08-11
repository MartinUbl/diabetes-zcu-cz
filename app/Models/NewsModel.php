<?php

namespace App\Models;

/**
 * Model for managing news
 */
class NewsModel extends BaseModel
{
    public $implicitTable = 'news';
    
    public function __construct(\Nette\Database\Context $database, \Nette\DI\Container $c) {
        parent::__construct($database, $c);
    }

    /**
     * Retrieves news record by ID
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getNewsById($id)
    {
        return $this->getTable()->where('id', $id)->fetch();
    }

    /**
     * Retrieves a selection of news
     * @param int|null $start
     * @param int|null $count
     * @return \Nette\Database\Table\Selection
     */
    public function getNews($start = null, $count = null)
    {
        $sel = $this->getTable();
        
        if (is_int($start))
            $sel->limit($count, $start);
        
        return $sel;
    }

    /**
     * Creates a single news record
     * @param string $title
     * @param string $short_content
     * @param int $users_id
     * @param string|null $publish_datetime
     * @param string|null $full_content
     * @param boolean $social_media_posted
     */
    public function addNews($title, $short_content, $users_id, $publish_datetime = null, $full_content = null, $social_media_posted = false)
    {
        return $this->getTable()->insert([
            'title' => $title,
            'short_content' => $short_content,
            'full_content' => $full_content,
            'users_id' => $users_id,
            'published_on' => ($publish_datetime !== null) ? $publish_datetime : date('Y-m-d H:i:s'),
            'social_media_posted' => $social_media_posted
        ]);
    }

    /**
     * Edits a single news record
     * @param int $id
     * @param string $title
     * @param string $short_content
     * @param string|null $full_content
     */
    public function editNews($id, $title, $short_content, $full_content = null)
    {
        $this->getTable()->where('id', $id)->update([
            'title' => $title,
            'short_content' => $short_content,
            'full_content' => $full_content
        ]);
    }

    /**
     * Deletes a news record
     * @param int $id
     */
    public function deleteNews($id)
    {
        $this->getTable()->where('id', $id)->delete();
    }
    
    /**
     * Updates the news record after publishing the news to the social media
     * @param int $id
     */
    public function setPostedOnSocialMedia($id)
    {
        $this->getTable()->where('id', $id)->update([
            'social_media_posted' => true
        ]);
    }
}