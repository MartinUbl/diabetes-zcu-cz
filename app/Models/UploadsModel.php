<?php

namespace App\Models;

use Nette\Security\Passwords;

/**
 * Model for managing uploaded files
 */
class UploadsModel extends BaseModel
{
    public $implicitTable = 'uploads';
    
    private function generateUploadId()
    {
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 32; $i++)
                $randomString .= $characters[rand(0, $charactersLength - 1)];
        return $randomString;
    }

    public static function validateUploadId($id)
    {
        for ($i = 0; $i < strlen($id); $i++)
        {
            if ($id[$i] >= '0' && $id[$i] <= '9')
                continue;
            if ($id[$i] >= 'a' && $id[$i] <= 'f')
                continue;
            return false;
        }

        return true;
    }

    /**
     * Retrieves upload record by id
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUploadById($id)
    {
        return $this->getTable()->where('id', $id)->fetch();
    }
    
    /**
     * Retrieves upload record by assigned upload id
     * @param string $id
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUploadByUploadId($upload_id)
    {
        return $this->getTable()->where('upload_id', $upload_id)->fetch();
    }
    
    /**
     * Retrieves selection of all uploads
     * @return \Nette\Database\Table\Selection
     */
    public function getAllUploads()
    {
        return $this->getTable();
    }

    public function addUpload($date, $deviceInfo, $users_id)
    {
        $uploadId = $this->generateUploadId();
        
        $this->getTable()->insert(array(
            'date' => $date,
            'device_info' => $deviceInfo,
            'upload_id' => $uploadId,
            'users_id' => $users_id
        ));
        
        return $uploadId;
    }
    
    public function eraseUploadId($id)
    {
        $this->getTable()->where('id', $id)->update(array(
            'upload_id' => null
        ));
    }
}
