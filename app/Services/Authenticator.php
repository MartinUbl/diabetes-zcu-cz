<?php

namespace App\Services;

use Nette,
    Nette\Security\Passwords;

/**
 * Users management.
 */
class Authenticator implements Nette\Security\IAuthenticator
{
    const
        TABLE_NAME = 'users',
        COLUMN_ID = 'id',
        COLUMN_NAME = 'email',
        COLUMN_PASSWORD_HASH = 'password',
        COLUMN_ROLE = 'role';

    /** @var Nette\Database\Context */
    private $database;
    
    /** @var Nette\Security\Passwords */
    private $passwords;

    public function __construct(Nette\Database\Context $database, Nette\Security\Passwords $passwords)
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials): Nette\Security\IIdentity
    {
        list($email, $password) = $credentials;
        $row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $email)->fetch();

        if (!$row)
            throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
        else if (!empty($row->activation_token))
            throw new Nette\Security\AuthenticationException('The account is inactive.', self::NOT_APPROVED);
        elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH]))
            throw new Nette\Security\AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
        elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH]))
        {
            $row->update(array(
                self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
            ));
        }

        $arr = $row->toArray();
        unset($arr[self::COLUMN_PASSWORD_HASH]);

        return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
    }
}

