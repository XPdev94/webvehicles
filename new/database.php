<?php

function database(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = getenv('WEBVEHICLES_DSN');
    $username = getenv('WEBVEHICLES_DB_USER');
    $password = getenv('WEBVEHICLES_DB_PASSWORD');
    if (!$dsn) {
        $settings = legacyDatabaseSettings();
        $dsn = 'mysql:host=' . $settings['hostname_LLUIS']
            . ';dbname=' . $settings['database_LLUIS'] . ';charset=utf8';
        $username = $settings['username_LLUIS'];
        $password = $settings['password_LLUIS'];
    }

    $connection = new PDO($dsn, (string) $username, (string) $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $connection;
}

function legacyDatabaseSettings(): array
{
    // Reuse the existing settings without executing its obsolete mysql_* connection.
    $names = ['hostname_LLUIS', 'database_LLUIS', 'username_LLUIS', 'password_LLUIS'];
    $source = file_get_contents(dirname(__DIR__) . '/Connections/LLUIS.php');
    if ($source === false) {
        throw new RuntimeException('Cannot read fleet database settings.');
    }
    $tokens = array_values(array_filter(token_get_all($source), static function ($token) {
        return !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }));
    $settings = [];
    foreach ($tokens as $index => $token) {
        if (!is_array($token) || $token[0] !== T_VARIABLE) {
            continue;
        }
        $name = substr($token[1], 1);
        $value = $tokens[$index + 2] ?? null;
        if (
            !in_array($name, $names, true) || ($tokens[$index + 1] ?? null) !== '='
            || !is_array($value) || $value[0] !== T_CONSTANT_ENCAPSED_STRING
            || ($tokens[$index + 3] ?? null) !== ';'
        ) {
            continue;
        }
        $literal = $value[1];
        $body = substr($literal, 1, -1);
        $settings[$name] = $literal[0] === "'"
            ? strtr($body, ["\\\\" => "\\", "\\'" => "'"])
            : stripcslashes($body);
    }
    if (count($settings) !== count($names)) {
        throw new RuntimeException('Fleet database settings must be string literals or supplied through the environment.');
    }
    return $settings;
}

function query(PDO $db, string $sql, array $parameters = []): PDOStatement
{
    $statement = $db->prepare($sql);
    $statement->execute($parameters);
    return $statement;
}
