<?php

namespace Poller\Web\Services;

use PDO;

class Database
{
    //Settings
    const SONAR_URL = 'SONAR_URL';
    const POLLER_API_KEY = 'POLLER_API_KEY';
    const LOG_EXCEPTIONS = 'LOG_EXCEPTIONS';

    //Credential types
    const MIKROTIK_API = 'MIKROTIK_API';
    const NETONIX_SSH = 'NETONIX_SSH';
    const UBIQUITI_TOUGHSWITCH_SSH = 'UBIQUITI_TOUGHSWITCH_SSH';

    private $translations = [
        self::MIKROTIK_API => 'MikroTik API (SSL)',
        self::NETONIX_SSH => 'Netonix SSH',
        self::UBIQUITI_TOUGHSWITCH_SSH => 'Ubiquiti ToughSwitch SSH',
    ];

    private PDO $dbh;
    public function __construct()
    {
        $cnf = [
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];
        $this->dbh = new PDO('sqlite:'.  __DIR__ . './../../../permanent_config/database', null, null, $cnf);
        $this->createTablesIfRequired();
    }

    public function get(string $key):?string
    {
        $query = <<<SQL
SELECT value
FROM settings
WHERE key = ?
SQL;

        $statement = $this->dbh->prepare($query);
        $statement->execute([trim($key)]);
        $result = $statement->fetch();
        if (!$result) {
            return null;
        }
        return $result['value'];
    }

    public function set(string $key, string $value)
    {
        if ($this->get($key) === null) {
            $query = <<<SQL
INSERT INTO settings (value, key) VALUES(?, ?);
SQL;
        } else {
            $query = <<<SQL
UPDATE settings SET value = ? WHERE key = ?
SQL;
        }

        $statement = $this->dbh->prepare($query);
        return $statement->execute([$value, $key]);
    }

    public function getAllCredentials():array
    {
        $query = <<<SQL
SELECT * from credentials ORDER BY type ASC;
SQL;
        $statement = $this->dbh->prepare($query);
        $statement->execute();
        $result = $statement->fetchAll();
        foreach ($result as $key => $value) {
            $type = $value['type'];
            $result[$key]['english_type'] = $this->translations[$type];
        }
        return $result;
    }

    public function getCredential(string $type):?array
    {
        $query = <<<SQL
SELECT username, password, port
FROM credentials
WHERE type = ?
SQL;
        $statement = $this->dbh->prepare($query);
        $statement->execute([trim($type)]);
        $result = $statement->fetch();
        if (!$result) {
            return null;
        }
        return $result;
    }

    public function setCredential(string $type, string $username, string $password, int $port)
    {
        if ($this->getCredential($type) === null) {
            $query = <<<SQL
INSERT INTO credentials (username, password, port, type) VALUES(?, ?, ?, ?);
SQL;
        } else {
            $query = <<<SQL
UPDATE credentials SET username = ?, password = ?, port = ? WHERE type = ?
SQL;
        }

        $statement = $this->dbh->prepare($query);
        return $statement->execute([$username, $password, (int)$port, $type]);
    }

    public function deleteCredential(string $type)
    {
        $query = <<<SQL
DELETE from credentials WHERE type = ?
SQL;
        $statement = $this->dbh->prepare($query);
        $statement->execute([trim($type)]);
    }

    private function createTablesIfRequired()
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS
 settings (
    key STRING PRIMARY KEY,
    value STRING NOT NULL
) WITHOUT ROWID;
SQL;

        $this->dbh->exec($query);

        $query = <<<SQL
CREATE TABLE IF NOT EXISTS
 credentials (
    type STRING PRIMARY KEY,
    username STRING NOT NULL,
    password STRING NOT NULL,
    port INTEGER NOT NULL
) WITHOUT ROWID;
SQL;

        $this->dbh->exec($query);
    }
}
