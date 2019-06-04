<?php

namespace App\Models;

use App\Views\View;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Class Service
 * @package App\Models
 *
 * @property object $pdo
 * @property array $config
 * @property array TYPES
 * @property array ELEMENTS
 */
class Service
{
    protected $pdo;
    public $config;

    public const TYPES = [
        1 => 'BIGINT UNSIGNED NULL',
        2 => 'VARCHAR(255) NULL',
        3 => 'INT UNSIGNED NULL',
        4 => 'BOOL',
    ];
    public const ELEMENTS = [
        'id'                           => [1, 'instagram id'],
        'full_name'                    => [2, 'full name'],
        'biography'                    => [2, 'description'],
        'external_url'                 => [2, 'external url'],
        'profile_pic_url_hd'           => [2, 'user picture'],
        'business_category_name'       => [2, 'business category:'],
        'edge_followed_by'             => [3, 'followers'],
        'edge_follow'                  => [3, 'followed'],
        'edge_owner_to_timeline_media' => [3, 'posts'],
        'edge_felix_video_timeline'    => [3, 'video timeline'],
        'highlight_reel_count'         => [3, 'highlight reel count'],
        'is_business_account'          => [4, 'business account marker'],
        'is_private'                   => [4, 'privacy marker'],
        'is_verified'                  => [4, 'verification marker'],
        'is_joined_recently'           => [4, 'recent account marker'],
        'has_channel'                  => [4, 'has channel'],
        'has_blocked_viewer'           => [4, 'has blocked viewer'],
    ];
    public const MESSAGES = [
        'user'      => [
            'added'           => 'was added',
            'delete'          => 'was delete',
            'returned'        => 'was returned',
            'exist'           => 'already exist',
            'not exist'       => 'not exist in service',
            'nothing changed' => 'nothing was changed',
        ],
        'table'     => [
            'exist'   => 'already exist',
            'created' => 'was created',
        ],
        'exception' => [
            'can`t' => 'Can`t take data from Instagram.',
        ],
    ];

    /**
     * User constructor.
     * @param string|null $userName
     */
    public function __construct()
    {
        try {
            $config = require __DIR__ . '/../config.php';

            $this->pdo = new PDO('mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
                $config['db']['username'],
                $config['db']['password']
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $config['db']['password'] = null;
            $this->config = $config;
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * @param User $user
     * @return User
     * @throws GuzzleException
     */
    public function getUserDataFromInstagram(User $user): User
    {
        $response = [];

        $meta = $this->getUserFromUsers_tracked($user)->getMeta();

        if ($user->usersn === null) {
            $meta = $user->getMeta();
        }

        $client = new Client(['base_uri' => $this->config['instagram']['base_uri']]);
        try {
            $response = $client->request('GET', $user->username,
                ['http_errors' => false, 'timeout' => $this->config['service']['timeout']]);

            $meta['response'] = [
                'code'   => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
            ];
        } catch (RequestException $e) {
            $meta['response'] = [
                'code'    => 504,
                'reason'  => $e->getMessage(),
                'message' => self::MESSAGES['exception']['can`t'],
            ];
        }

        // If username exist in the instagram
        if (isset($meta['response']['code']) && $meta['response']['code'] === 200) {

            // Get data from the Instagram website.
            $content = (string)$response->getBody();

            // Get json from data from the Instagram website
            $document = new Document($content);

            // Get data from json
            $data = $document->first('body script');
            $data = substr(strip_tags($data), 21);
            $data = substr($data, 0, -1);
            $data = json_decode($data, true);
            $data = $data['entry_data']['ProfilePage'][0]['graphql']['user'];

            // Setting user variables for service database from json Instagram website
            foreach (array_keys($data) as $element) {
                if (strpos($element, 'edge_') !== false) {
                    $meta[$element] = (int)$data[$element]['count'];
                } else {
                    $meta[$element] = $data[$element];
                }
            }

            $meta['response']['content_length'] = strlen($content);
        }

        $meta['field_counter'] = count($meta);
        $user->addMeta($meta);

        return $user;
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param string $table Table to search for.
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    public function tableExists($table): bool
    {
        $pdo = $this->pdo;
        try {
            $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        } catch (PDOException $e) {
            return false;
        }

        return $result !== false;
    }

    /**
     * @return array|null
     */
    public function prepareDataBase(): array
    {
        $pdo = $this->pdo;
        $prefix = $this->config['db']['tablesprefix'];
        $elements = static::ELEMENTS;
        $types = static::TYPES;
        $report = [];

        try {

            $pdo->beginTransaction();

            if (!$this->tableExists($prefix . 'tracked')) {
                $sql = 'CREATE TABLE IF NOT EXISTS ' . $prefix . 'tracked
                    (
                        usersn SERIAL,
                        username VARCHAR(190) UNIQUE NOT NULL,
                        is_tracked BOOL DEFAULT TRUE,
                        is_updated BOOL DEFAULT TRUE,
                        is_notify BOOL DEFAULT FALSE,
                        is_delete BOOL DEFAULT FALSE,
                        update_time TIMESTAMP,
                        PRIMARY KEY (usersn)
                    )';
                $pdo->exec($sql);
                $report[$prefix . 'tracked'] = self::MESSAGES['table']['created'];
            } else {
                $report[$prefix . 'tracked'] = self::MESSAGES['table']['exist'];
            }

            foreach ($elements as $element => $type) {
                if (!$this->tableExists($prefix . $element)) {
                    $sql = 'CREATE TABLE IF NOT EXISTS ' . $prefix . $element . '                       
                        (
                            sn SERIAL,
                            usersn BIGINT UNSIGNED NOT NULL,
                            ' . $element . ' ' . $types[$type[0]] . ',
                            update_time TIMESTAMP,
                            FOREIGN KEY (usersn) REFERENCES ' . $prefix . 'tracked (usersn)
                            ON DELETE CASCADE
                        )';
                    $pdo->exec($sql);
                    $report[$prefix . $element] = self::MESSAGES['table']['created'];
                } else {
                    $report[$prefix . $element] = self::MESSAGES['table']['exist'];
                }
            }

            $pdo->commit();

            return $report;

        } catch (PDOException $e) {

            $pdo->rollBack();
            echo 'Error: ' . $e->getMessage();
            exit();
        }

        return $report;
    }

    /**
     * @param User $user
     * @return User
     * @throws GuzzleException
     */
    public function insertUserIntoService(User $user): User
    {
        $meta = array_merge($this->getUserFromUsers_tracked($user)->getMeta(),
            $this->getUserDataFromService($user)->getMeta());

        if ($user->usersn === null) {

            $meta = $this->getUserDataFromInstagram($user)->getMeta();
            $user->addMeta($meta);

            if (isset($meta['response']['code']) && $meta['response']['code'] == 200) {
                $user = $this->insertUserIntoUsers_tracked($user);
                $meta['usersn'] = $user->usersn;

                foreach (array_keys(self::ELEMENTS) as $element) {
                    try {
                        $this->insertIntoUsers_element($meta, $element);
                    } catch (PDOException $e) {
                        echo 'Error: ' . $e->getMessage();
                    }
                }
            }

            $meta = $user->getMeta();
        } elseif ($meta['is_delete'] == 1) {
            $meta = $this->updateUserIntoService($user, ['is_delete' => 0])->getMeta();
        } else {
            $meta['response']['message'] = self::MESSAGES['user']['exist'];
        }

        $user->setMeta($meta);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     */
    public function getUserDataFromService(User $user): User
    {
        $meta = $this->getUserFromUsers_tracked($user)->getMeta();

        if ($user->usersn !== null) {
            $user = $this->getUserDataFromUser_tables($user);
        } else {
            $meta['response']['message'] = self::MESSAGES['user']['not exist'];
            $user->addMeta($meta);
        }

        return $user;
    }

    /**
     * @throws GuzzleException
     */
    public function updateService(): void
    {
        $usersTracked = $this->getListUsersTracked();

        foreach ($usersTracked as $user) {
            $this->updateUserData($user);

            $view = new View();
            $view('Info_user', $user);
            sleep($this->config['instagram']['time_to_sleep']);
        }
    }

    /**
     * @param User $user
     * @param array $fields
     * @return User
     */
    public function updateUserIntoService(User $user, array $fields): User
    {
        $meta = array_merge($this->getUserFromUsers_tracked($user)->getMeta(),
            $this->getUserDataFromService($user)->getMeta());

        if (isset($user->usersn)) {
            foreach ($fields as $field => $value) {
                $meta['response']['set'][$field] = null;
                $sth = null;

                if ($meta[$field] == 0 && $value == 1) {
                    $sth = $this->updateBoolFieldIntoUsers_tracked($meta['username'], $field, 1);
                    $meta['response']['set'][$field] = 1;
                    $meta['response']['message'] = self::MESSAGES['user']['delete'];
                } elseif ($meta[$field] == 1 && $value == 0) {
                    $sth = $this->updateBoolFieldIntoUsers_tracked($meta['username'], $field, 0);
                    $meta['response']['set'][$field] = 0;
                    $meta['response']['message'] = self::MESSAGES['user']['returned'];
                } else {
                    $meta['response']['message'] = self::MESSAGES['user']['nothing changed'];
                }

                $meta['response']['pdo'][$field] = $sth;
            }
        } else {
            $meta['response']['message'] = self::MESSAGES['user']['not exist'];
        }

        $user->setMeta($meta);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     */
    protected function getUserFromUsers_tracked(User $user): User
    {

        $sql = 'SELECT * FROM ' . $this->config['db']['tablesprefix'] . 'tracked WHERE username = :username';
        $sth = $this->pdo->prepare($sql);
        $sth->execute([':username' => $user->username]);
        $data = $sth->fetch(PDO::FETCH_ASSOC);

        $meta['username'] = $user->username;

        if ($data) {
            foreach ($data as $key => $value) {
                $meta[$key] = $value;
            }
        }

        $user->setMeta($meta);
        return $user;
    }

    /**
     * @return array
     */
    public function getListUsersTracked(): array
    {
        $users = [];
        $sql = 'SELECT * FROM ' . $this->config['db']['tablesprefix'] . 'tracked WHERE is_tracked = 1  AND is_delete = 0 ORDER BY usersn';
        $res = $this->pdo->query($sql, PDO::FETCH_ASSOC);
        $usersMeta = $res->fetchAll();

        foreach ($usersMeta as $meta) {
            $user = new User($meta['username']);
            $user->addMeta($meta);
            $users[$user->usersn] = $user;
        }

        return $users;
    }

    /**
     * @param User $user
     * @return User
     */
    protected function insertUserIntoUsers_tracked(User $user): User
    {
        $meta = $user->getMeta();

        $sql = 'INSERT INTO ' . $this->config['db']['tablesprefix'] . 'tracked (username) VALUES (:username)';
        $sth = $this->pdo->prepare($sql);
        $sth = $sth->execute([':username' => $meta['username']]);
        $meta['response']['pdo'] = $sth;
        $meta['usersn'] = $this->pdo->lastInsertId('usersn');
        $meta['response']['message'] = self::MESSAGES['user']['added'];

        $user->addMeta($meta);
        return $user;
    }

    /**
     * @param array $meta
     * @param string $element
     * @return bool|PDOStatement
     */
    protected function insertIntoUsers_element(array $meta, string $element)
    {
        $sql = 'INSERT INTO ' . $this->config['db']['tablesprefix'] . $element . ' (usersn, ' . $element . ') VALUES (:usersn, :value)';
        $sth = $this->pdo->prepare($sql);
        $sth->execute([':usersn' => $meta['usersn'], ':value' => $meta[$element]]);

        return $sth;
    }

    /**
     * @param string $userName
     * @param bool $is_delete
     * @return bool|PDOStatement
     */
    protected function updateBoolFieldIntoUsers_tracked(string $userName, string $field, bool $value)
    {
        $sql = 'UPDATE ' . $this->config['db']['tablesprefix'] . 'tracked SET ' . $field . ' = :' . $field . ' WHERE username = :username';
        $sth = $this->pdo->prepare($sql);
        $sth->execute([':username' => $userName, ':' . $field => $value]);

        return $sth;
    }

    /**
     * @param User $user
     * @return User
     */
    protected function getUserDataFromUser_tables(User $user): User
    {
        $meta = $user->getMeta();

        foreach (array_keys(self::ELEMENTS) as $element) {
            $sql = 'SELECT * FROM ' . $this->config['db']['tablesprefix'] . $element . ' WHERE usersn = :usersn ORDER BY sn DESC LIMIT 2';
            $sth = $this->pdo->prepare($sql);
            $sth->execute([':usersn' => $meta['usersn']]);
            $data = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (!isset($data[0])) {
                $data[0] = null;
            }
            $meta[$element] = $data[0][$element];
        }

        $user->addMeta($meta);

        return $user;
    }

    /**
     * @param User $user
     * @return User
     * @throws GuzzleException
     */
    public function updateUserData(User $user): User
    {
        $meta = $userMetaFromInstagram = $this->getUserDataFromInstagram($user)->getMeta();
        $userMetaFromService = $this->getUserDataFromService($user)->getMeta();

        $elements = self::ELEMENTS;
        $userD = $userI = [];

        foreach (array_keys($elements) as $element) {
            foreach ($userMetaFromInstagram as $key => $value) {
                if ($element === $key) {
                    $userI[$key] = $value;
                }
            }
            foreach ($userMetaFromService as $key => $value) {
                if ($element === $key) {
                    if ($elements[$element][0] === 4) {
                        $userD[$key] = (bool)$value;
                    } else {
                        $userD[$key] = $value;
                    }
                }
            }
        }

        $diff = array_diff_assoc($userI, $userD);

        foreach ($diff as $element => $value) {
            try {

                $meta['response']['pdo'][$element] = $this->insertIntoUsers_element($meta, $element);

                $meta['response']['diff']['now'][$element] = $userI[$element];
                $meta['response']['diff']['before'][$element] = $userD[$element];

                if (is_numeric($userI[$element]) && is_numeric($userD[$element])) {
                    $meta['response']['diff']['diff'][$element] = $userI[$element] - $userD[$element];
                }

            } catch (PDOException $e) {
                echo 'ERROR: ' . $e->getMessage();
            }
        }

        $user->addMeta($meta);
        return $user;
    }
}
