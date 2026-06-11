<?php
// Script untuk generate password hash

// Ganti dengan password yang Anda inginkan
$plain_password = "admin123";

// Hash dengan Argon2id
$hashed = password_hash($plain_password, PASSWORD_ARGON2ID, [
    'memory_cost' => 19456,
    'time_cost' => 4,
    'threads' => 1
]);

echo "Password Plaintext: " . $plain_password . "\n";
echo "Password Hash: " . $hashed . "\n";
echo "\n";
echo "SQL Command:\n";
echo "INSERT INTO admin (username, password, nama) VALUES ('ilhamzp', '" . $hashed . "', 'Ilham Rizqiawan');\n";
?>