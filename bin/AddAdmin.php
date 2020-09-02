<?php

// force argument count
if ($argc != 4)
{
    die("Usage:\n    php AddUser.php <email> <password> <role>");
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Bootstrap.php';

$container = App\Bootstrap::boot()->createContainer();
$users = $container->getByType(App\Models\UserModel::class);

$email = $argv[1];
$password = $argv[2];
$role = $argv[3];

if (!\App\Models\Validators::validateEmail($email))
    die("Invalid email: ".$email."\n");

if (!\App\Models\Validators::validatePasswordStrength($password))
    die("Weak password, use 6 characters or more\n");

if ($role !== 'admin' && $role !== 'user')
    die("Invalid role!");

if ($users->getUserByEmail($email))
    die("This email is already in the database!\n");

$user = $users->addUser($email, $password);
if (!$user)
    die("Could not create user.\n");

$users->setRole($user['id'], $role);

echo "User '$email' successfully registered.\n";
