<?php

namespace App\Models;

use Nette\Security\Passwords;

/**
 * Model for managing users
 */
class UserModel extends BaseModel
{
    public $implicitTable = 'users';
    
    /** @var \Nette\Security\Passwords */
    public $passwords;
    
    const API_TOKEN_VALID_PERIOD = 2592000; // 30 days [s]
    const ACTIVATION_TOKEN_VALID_PERIOD = 3600; // 1 hour [s]
    
    public function __construct(\Nette\Database\Context $database, \Nette\DI\Container $c, \Nette\Security\Passwords $pass) {
        parent::__construct($database, $c);
        
        $this->passwords = $pass;
    }

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
     * @return \Nette\Database\Table\Selection
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
     * Sets a new password to a given user 
     * @param int $id
     * @param string $password
     */
    public function setUserPassword($id, $password)
    {
        $this->getTable()->where('id', $id)->update(array(
            'password' => $this->passwords->hash($password)
        ));
    }

    /**
     * Adds new user, returns the instance; this method is called from CLI scripts
     * To register a user from web interface, use registerUser method
     * @param string $email
     * @param string $password
     * @return mixed | null
     * @return \Nette\Database\Table\ActiveRow
     */
    public function addUser($email, $password, $firstName = null, $lastName = null)
    {
        return $this->getTable()->insert(array(
            'email' => $email,
            'password' => $this->passwords->hash($password),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'user',
            'api_token' => "",
            'api_token_valid_to' => "1970-01-01 00:00:00",
        ));
    }
    
    /**
     * Registers new user, returns the instance
     * @param string $email
     * @param string $password
     * @param string $firstName
     * @param string $lastName
     * @return array | null
     */
    public function registerUser($email, $password, $firstName = null, $lastName = null)
    {
        $baseUser = $this->addUser($email, $password);
        if (!$baseUser)
            return null;
        
        $token = $this->createActivationTokenForUser($baseUser->id);
        if (!$token)
            return null;
        
        return [ 'id' => $baseUser->id, 'activation_token' => $token ];
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
    
    /**
     * Clears API token for given user
     * @param int $id
     */
    public function clearApiTokenForUser($id)
    {
        $this->getTable()->where('id', $id)->update(array(
            'api_token' => '',
            'api_token_valid_to' => date('Y-m-d H:i:s', time() - self::API_TOKEN_VALID_PERIOD) // set some invalid date
        ));
    }
    
    /**
     * Retrieves user record by its activation token
     * @param string $token
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUserByActivationToken($token)
    {
        return $this->getTable()->where('activation_token', $token)->where('activation_token_valid_to > ?', date('Y-m-d H:i:s'))->fetch();
    }

    /**
     * Creates activation token for user and returns it
     * @param int $id
     * @return string
     */
    public function createActivationTokenForUser($id)
    {
        $valid_to = date('Y-m-d H:i:s', time() + self::ACTIVATION_TOKEN_VALID_PERIOD);

        do
        {
            $token = md5('outersalt'.sha1('saltysalt'.rand(0,10000000))).md5('outersalt'.sha1('saltysalt'.rand(0,10000000)));
        } while ($this->getUserByActivationToken($token) != null);

        $this->getTable()->where('id', $id)->update(array(
            'activation_token' => $token,
            'activation_token_valid_to' => $valid_to
        ));

        return [ 'token' => $token, 'valid-to' => $valid_to];
    }
    
    /**
     * Clears activation token for given user
     * @param int $id
     */
    public function clearActivationTokenForUser($id)
    {
        $this->getTable()->where('id', $id)->update(array(
            'activation_token' => null,
            'activation_token_valid_to' => null
        ));
    }
    
    /**
     * Retrieves user record by its password reset token
     * @param string $token
     * @return \Nette\Database\Table\ActiveRow
     */
    public function getUserByPasswordResetToken($token)
    {
        return $this->getTable()->where('password_reset_token', $token)->where('password_reset_token_valid_to > ?', date('Y-m-d H:i:s'))->fetch();
    }

    /**
     * Creates password reset token for user and returns it
     * @param int $id
     * @return string
     */
    public function createPasswordResetTokenForUser($id)
    {
        $valid_to = date('Y-m-d H:i:s', time() + self::ACTIVATION_TOKEN_VALID_PERIOD);

        do
        {
            $token = md5('outersalt'.sha1('saltysalt'.rand(0,10000000))).md5('outersalt'.sha1('saltysalt'.rand(0,10000000)));
        } while ($this->getUserByPasswordResetToken($token) != null);

        $this->getTable()->where('id', $id)->update(array(
            'password_reset_token' => $token,
            'password_reset_token_valid_to' => $valid_to
        ));

        return [ 'token' => $token, 'valid-to' => $valid_to];
    }
    
    /**
     * Clears password reset token for given user
     * @param int $id
     */
    public function clearPasswordResetTokenForUser($id)
    {
        $this->getTable()->where('id', $id)->update(array(
            'password_reset_token' => null,
            'password_reset_token_valid_to' => null
        ));
    }
}

