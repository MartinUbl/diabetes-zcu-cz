<?php

namespace App\Services;

use Nette,
    Facebook;

class FacebookService
{
    /** @var Facebook */
    private $fb = null;
    
    /** @var string */
    private $appId;
    /** @var string */
    private $appSecret;
    /** @var string */
    private $pageId;
    
    public function __construct(string $appId, string $appSecret, string $pageId) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->pageId = $pageId;
    }
    
    private function authIfNeeded() {
        if ($this->fb === null) {
            $this->fb = new Facebook\Facebook([
                'app_id' => $this->appId,
                'app_secret' => $this->appSecret,
                'default_graph_version' => 'v14.0'
            ]);
        }
        
        return ($this->fb !== null);
    }
    
    public function postToPage($message) {
        if (!$this->authIfNeeded()) {
            throw new \RuntimeException('Cannot configure Facebook module');
        }
        
        /*
        $accessToken = '???'; // TODO: obtain page access token
        $response = $fb->post('/'.$this->pageId.'/feed', [ 'message' => $message ], $accessToken);
        return true;
        */
        
        return true; // NYI, but pretend we succeeded for now
    }
}
