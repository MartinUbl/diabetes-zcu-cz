<?php

namespace App\Models;

/**
 * Model for managing download tokens
 */
class DownloadTokensModel extends BaseModel
{
    public $implicitTable = 'download_token';
    
    public function __construct(\Nette\Database\Context $database, \Nette\DI\Container $c) {
        parent::__construct($database, $c);
    }

    /**
     * Retrieves download token record by ID
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getDownloadTokenById($id)
    {
        return $this->getTable()->where('id', $id)->fetch();
    }

    /**
     * Stores a single download into counter
     * @param string $name
     */
    public function storeUntokenedDownload($name)
    {
        $m = intval(date('n'));
        $y = intval(date('Y'));
        
        $rec = $this->table('download_counter')->where('name', $name)->where('month', $m)->where('year', $y)->fetch();
        if (!$rec)
        {
           $this->table('download_counter')->insert([
               'name' => $name,
               'month' => $m,
               'year' => $y,
               'counter' => 1
           ]);
        }
        else
        {
            $this->table('download_counter')->where('name', $name)->where('month', $m)->where('year', $y)->update([
                'counter' => $rec->counter + 1
            ]);
        }
    }
    
    /**
     * Lists untokened downloads per name
     * @return array
     */
    public function listUntokenedDownloadCounts()
    {
        $res = $this->table('download_counter')->select('name, SUM(counter) AS amount')->group('name');
        $fetched = [];
        
        foreach ($res as $r)
            $fetched[$r->name] = $r->amount;
        
        return $fetched;
    }

    /**
     * Retrieves statistics of untokened downloads
     * @return array
     */
    public function getUntokenedDownloadStats()
    {
        $dtNow = new \Nette\Utils\DateTime("now");
        
        $mNow = intval($dtNow->format('n')) - 1; // rebase to 0
        $yNow = intval($dtNow->format('Y'));
        
        $fetchPairs = [
            [ $mNow, $yNow ],
            [ $mNow - 1, $yNow ],
            [ $mNow - 2, $yNow ],
        ];
        
        $monthly = []; //$mothly[name][month_offset] = count;
        
        foreach ($fetchPairs as $i => $fp)
        {
            if ($fp[0] < 0)
            {
                $fp[0] = 12 - $fp[0];
                $fp[1]--;
            }
            
            $sel = $this->table('download_counter')->where('month',  $fp[0] + 1)->where('year', $fp[1]);
            
            foreach ($sel as $s)
            {
                if (!isset($monthly[$s->name]))
                    $monthly[$s->name] = [];
                if (!isset($monthly[$s->name][$i]))
                    $monthly[$s->name][$i] = $s->counter;
            }
        }
        
        $stats = [
            'last_3_months' => $monthly,
        ];
        
        return $stats;
    }

    /**
     * Lists count of downloads per category
     * @return array
     */
    public function listCategoryCounts()
    {
        $res = $this->getTable()->select('category, COUNT(1) AS amount')->where('used', true)->group('category');
        $categories = [];
        
        foreach ($res as $r)
            $categories[$r->category] = $r->amount;
        
        return $categories;
    }
    
    /**
     * Lists all download tokens requested for given category
     * @param string $category
     * @return \Nette\Database\Table\Selection
     */
    public function listDownloadsForCategory($category, $usedOnly = false)
    {
        $sel = $this->getTable()->where('category', $category);
        if ($usedOnly)
            $sel = $sel->where('used', true);
        
        return $sel->order('valid_to DESC');
    }
    
    /**
     * Retrieves month offset from current date
     * @param \Nette\Utils\DateTime | string $date
     * @return int
     */
    public static function getMonthOffset($dateIn)
    {
        $dtNow = new \Nette\Utils\DateTime("now");
		$date = new \Nette\Utils\DateTime($dateIn);
        
        $mNow = intval($dtNow->format('n')) - 1; // rebase to 0
        $yNow = intval($dtNow->format('Y'));
        
        $mPast = intval($date->format('n')) - 1;
        $yPast = intval($date->format('Y'));
        
        $monthOffset = 0;
        if ($yNow != $yPast)
            $monthOffset += ($yNow - $yPast - 1) * 12;
        
        $monthOffset += abs($mNow - $mPast);
        
        return $monthOffset;
    }
    
    /**
     * Calculates download statistics per category
     * @return array
     */
    public function getCategoryStats()
    {
        // NOTE: this could be achieved with better performance using native MySQL/PostgreSQL/... procedures and query formats;
        // however, since we intend to use SQLite with the simplest driver possible, we should do it manually as many of these
        // constructs are not available in this particular SQLite driver
        
        $tokens = $this->getTable()->where('used', true);
        $monthly = []; // $mothly[category][month_offset] = count; // month_offset is 0 for current, 1 for past month, 2 for a month before, etc.
        
        $maxOff = 3;
        
        foreach ($tokens as $tk)
        {
            $off = self::getMonthOffset($tk->valid_to);
            if ($off > $maxOff)
                continue;
            
            if (!isset($monthly[$tk->category]))
                $monthly[$tk->category] = [];
            
            if (!isset($monthly[$tk->category][$off]))
                $monthly[$tk->category][$off] = 1;
            else
                $monthly[$tk->category][$off]++;
        }

        foreach ($monthly as $mt)
        {
            for ($i = 0; $i <= $maxOff; $i++)
            {
                if (!isset($mt[$i]))
                    $mt[$i] = 0;
            }
        }
        
        $stats = [
            'last_3_months' => $monthly,
        ];
        
        return $stats;
    }
    
    /**
     * Finds a valid token for given category
     * @param string $category
     * @param string $token
     * @return type
     */
    public function findTokenForCategory($category, $token)
    {
        return $this->getTable()->where('category', $category)->where('token', $token)->where('used', false)->where('valid_to > ?', date('Y-m-d H:i:s'))->fetch();
    }

    /**
     * Creates a new token for given category
     * @param string $category
     * @param int $validityMinutes
     * @param string $requesterName
     * @param string $requesterPhone
     * @param string $requesterAddress
     * @param string $requesterEmail
     * @param string $requesterIP
     * @return string
     */
    public function createToken($category, $validityMinutes, $requesterName, $requesterPhone, $requesterAddress, $requesterEmail, $requesterIP)
    {
        $valid_to = date('Y-m-d H:i:s', time() + $validityMinutes*60);

        do
        {
            $token = md5('outersalt'.sha1('saltysalt'.rand(0,10000000))).md5('outersalt'.sha1('saltysalt'.rand(0,10000000)));
        }
        while ($this->findTokenForCategory($category, $token));
        
        $this->getTable()->insert([
            'category' => $category,
            'token' => $token,
            'valid_to' => $valid_to,
            'used' => false,
            'requester_name' => $requesterName,
            'requester_phone' => $requesterPhone,
            'requester_address' => $requesterAddress,
            'requester_email' => $requesterEmail,
            'requester_ip' => $requesterIP
        ]);
        
        return $token;
    }
    
    /**
     * Mark the given token as used
     * @param string $token
     * @param string $category
     */
    public function markUsed($token, $category)
    {
        $this->getTable()->where('category', $category)->where('token', $token)->update([
            'used' => true
        ]);
    }
}