<?php
require 'C:/xampp/htdocs/pjt/painel-cti/vendor/autoload.php';
require 'C:/xampp/htdocs/pjt/painel-cti/includes/app.php';
use App\Model\Entity\User;
$email = 'danilorods@outlook.com';
$user = User::getUserByEmail($email);
if (!$user) { echo "user: NOT FOUND\n"; exit(1); }
$ok = password_verify('12345678', (string)$user->senha);
echo 'email: ' . $user->email . "\n";
echo 'id: ' . $user->id . "\n";
echo 'nivel: ' . ($user->nivel ?? '') . "\n";
echo 'ativo: ' . ($user->ativo ?? '') . "\n";
echo 'password_verify(12345678): ' . ($ok ? 'true' : 'false') . "\n";
