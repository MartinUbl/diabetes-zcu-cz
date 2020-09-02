<?php

namespace App\Models;

/**
 * Static validation class
 */
class Validators
{
    /**
     * Validates email
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email)
    {
        // TODO: use some real email validator, this is VERY primitive
        return strpos($email, '@') < strpos($email, '.');
    }
    
    /**
     * Validates password strength
     * @param string $password
     * @return bool
     */
    public static function validatePasswordStrength($password)
    {
        return strlen($password) >= 6;
    }
}
