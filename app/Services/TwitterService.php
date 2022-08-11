<?php

namespace App\Services;

use Nette,
    DG\Twitter\Twitter;

class TwitterService
{
    /** @var Twitter */
    private $twitter = null;
    
    /** @var string */
    private $apiKey;
    /** @var string */
    private $apiSecret;
    /** @var string */
    private $accessToken;
    /** @var string */
    private $accessTokenSecret;
    
    public function __construct(string $apiKey, string $apiSecret, string $accessToken, string $accessTokenSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->accessToken = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
    }
    
    private function authIfNeeded() {
        if ($this->twitter === null) {
            $this->twitter = new Twitter($this->apiKey, $this->apiSecret, $this->accessToken, $this->accessTokenSecret);
        }
        
        return ($this->twitter !== null);
    }
    
    public function tweet($message) {
        if (!$this->authIfNeeded()) {
            throw new \RuntimeException('Cannot configure Twitter module');
        }
        $this->twitter->send($message);
    }
}
