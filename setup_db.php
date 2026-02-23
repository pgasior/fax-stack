<?php

$host = 'mariadb';
$db   = getenv('MYSQL_DATABASE');
$user = getenv('MYSQL_USER');
$pass = getenv('MYSQL_PASSWORD');

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function insertIfNotExists(PDO $pdo, string $table, array $data, string $checkField): void {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));

    $stmt = $pdo->prepare("
        INSERT INTO $table ($columns)
        SELECT $placeholders
        WHERE NOT EXISTS (
            SELECT 1 FROM $table WHERE $checkField = :$checkField
        )
    ");

    $stmt->execute($data);

    if ($stmt->rowCount()) {
        echo "Inserted into $table where $checkField = {$data[$checkField]}\n";
    } else {
        echo "Skipped $table where $checkField = {$data[$checkField]} (already exists)\n";
    }
}

insertIfNotExists($pdo, 'UserAccount', [
    'uid'         => 1,
    'name'        => 'AvantFAX Admin',
    'username'    => 'admin',
    'password'    => '5f4dcc3b5aa765d61d8327deb882cf99',
    'wasreset'    => 1,
    'email'       => 'root@localhost',
    'is_admin'    => 1,
    'language'    => 'en',
    'acc_enabled' => 1,
    'any_modem'   => 1,
    'superuser'   => 1,
], 'username');

insertIfNotExists($pdo, 'UserPasswords', [
    'uid'     => 1,
    'pwdhash' => '5f4dcc3b5aa765d61d8327deb882cf99',
], 'uid');

insertIfNotExists($pdo, 'AddressBook', [
    'company' => 'XXXXXXX',
], 'company');

insertIfNotExists($pdo, 'AddressBookFAX', [
    'abook_id'  => 1,
    'faxnumber' => 'XXXXXXX',
], 'abook_id');

insertIfNotExists($pdo, 'CoverPages', ['title' => 'Generic A4',      'file' => 'cover.ps'],       'title');
insertIfNotExists($pdo, 'CoverPages', ['title' => 'Generic Letter',  'file' => 'cover-letter.ps'], 'title');
insertIfNotExists($pdo, 'CoverPages', ['title' => 'Generic HTML',    'file' => 'coverpage.html'],  'title');
insertIfNotExists($pdo, 'Modems', ['devid' => 1, 'device' => 'ttyIAX', 'alias' => 'ttyIAX', 'contact' => '', 'printer' => '', 'faxcatid' => 0], 'devid');