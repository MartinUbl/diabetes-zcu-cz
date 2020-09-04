<?php

namespace App\Models;

use Nette\Security\Passwords;

/**
 * Model for managing users
 */
class UserModel extends BaseModel
{
    public $implicitTable = 'users';
    
    const API_TOKEN_VALID_PERIOD = 2592000; // 30 days [s]

    /**
     * Retrieves user record by email
     * @param string $email
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUserByEmail($email)
    {
        return $this->getTable()->where('email', $email)->fetch();
    }

    /**
     * Retrieves user record by id
     * @param int $id
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUserById($id)
    {
        return $this->getTable()->where('id', $id)->fetch();
    }
    
    /**
     * Retrieves selection of all users
     * @return \Nette\Database\Table\Selection
     */
    public function getAllUsers()
    {
        return $this->getTable();
    }
    
    /**
     * Retrieves user record based on part of email address
     * @param string $part
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUserByEmailPart($part)
    {
        return $this->getTable()->where('email LIKE ?', '%'.$part.'%');
    }
    
    /**
     * Retrieves users fetched into associative map
     * @return array
     */
    public function getUserMap()
    {
        $sel = $this->getAllUsers();
        $arr = array();
        foreach ($sel as $usr)
            $arr[$usr->id] = $usr->email;
        
        return $arr;
    }

    /**
     * Adds new user, returns the instance
     * @param string $email
     * @param string $password
     * @return mixed | null
     * @return \Nette\Database\Table\ActiveRow
     */
    public function addUser($email, $password)
    {
        return $this->getTable()->insert(array(
            'email' => $email,
            'password' => Passwords::hash($password),
            'role' => 'user',
            'api_token' => "",
            'api_token_valid_to' => "1970-01-01 00:00:00",
        ));
    }

    /**
     * Sets role field to given value
     * @param int $id
     * @param string $role
     */
    public function setRole($id, $role)
    {
        $this->getTable()->where('id', $id)->update(array(
            'role' => $role
        ));
    }
    
    /**
     * Retrieves user record by username
     * @param string $token
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUserByApiToken($token)
    {
        return $this->getTable()->where('api_token', $token)->where('api_token_valid_to > ?', date('Y-m-d H:i:s'))->fetch();
    }

    /**
     * Creates API token for user and returns it
     * @param int $id
     * @return string
     */
    public function createApiTokenForUser($id)
    {
        $valid_to = date('Y-m-d H:i:s', time() + self::API_TOKEN_VALID_PERIOD);

        do
        {
            $token = md5('outersalt'.sha1('saltysalt'.rand(0,10000000))).md5('outersalt'.sha1('saltysalt'.rand(0,10000000)));
        } while ($this->getUserByApiToken($token) != null);

        $this->getTable()->where('id', $id)->update(array(
            'api_token' => $token,
            'api_token_valid_to' => $valid_to
        ));

        return [ 'token' => $token, 'valid-to' => $valid_to];
    }
}

