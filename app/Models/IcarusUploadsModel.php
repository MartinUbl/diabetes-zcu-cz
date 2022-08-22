<?php

namespace App\Models;

/**
 * Model for managing icarus uploads
 */
class IcarusUploadsModel extends BaseModel
{
    public $implicitTable = 'icarus_uploads';
    
    public $uploadsPath;
    
    public function __construct(string $uploadsPath, \Nette\Database\Context $database, \Nette\DI\Container $c) {
        parent::__construct($database, $c);
        
        $this->uploadsPath = $uploadsPath;
    }
    
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
     * Retrieves an upload record by its generated handle
     * @param string $uploadId
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUploadByUploadId($uploadId)
    {
        return $this->getTable()->where('upload_id', $uploadId)->fetch();
    }
    
    /**
     * Creates a new icarus upload record
     * @param int $score
     * @param int $player_qualification
     * @param string $disease_code
     * @param string $nickname
     * @param int $difficulty
     * @param string $date
     * @return string
     */
    public function createUpload(int $score, int $player_qualification, string $disease_code, string $nickname, int $difficulty, string $date)
    {
        $uploadId = "";
        
        do
        {
            $uploadId = $this->generateUploadId();
        } while ($this->getUploadByUploadId($uploadId) != null);
        
        $this->getTable()->insert([
            'upload_id' => $uploadId,
            'score' => $score,
            'player_qualification' => $player_qualification,
            'disease_code' => $disease_code,
            'nickname' => $nickname,
            'difficulty' => $difficulty,
            'date' => $date,
        ]);
        
        return $uploadId;
    }
    
    /**
     * Shreds used uploadId
     * @param string $uploadId
     */
    public function invalidateUploadId($uploadId)
    {
        $this->getTable()->where('upload_id', $uploadId)->update([ 'upload_id' => null ]);
    }

    private function fetchDifficultyScoreboard($difficulty)
    {
        $sel = $this->getTable()->where('difficulty', $difficulty)->order('score DESC')->limit(10);
        
        $res = [];
        foreach ($sel as $sl)
            $res[] = [ 'nickname' => $sl->nickname, 'score' => $sl->score ];
        
        return $res;
    }

    /**
     * Retrieves a scoreboard
     * @return array
     */
    public function getScoreboard()
    {
        return [
            'easy' => $this->fetchDifficultyScoreboard(0),
            'medium' => $this->fetchDifficultyScoreboard(1),
            'hard' => $this->fetchDifficultyScoreboard(2)
        ];
    }
}
