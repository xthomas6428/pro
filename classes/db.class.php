<?php

class db
{
    /**
     * The database resource
     *
     * @var PDO
     */
    public $db;

    /**
     * List of queries
     *
     * @var array
     */
    public $queries = [];

    public function __construct($db_host, $db_user, $db_pass, $db_database, $db_port)
    {
        $this->connect($db_host, $db_database, $db_user, $db_pass, $db_port);
    }

    public function connect($hostname, $database, $username, $password, $port = 3306): void
    {
        try {
            $this->db = new \PDO("mysql:dbname=$database;host=$hostname;port=$port;charset=utf8mb4", $username,
                $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            print "MySQL Error:".$e->getMessage()."<br>";
            print "This error is usually caused because your MySQL credentials are incorrect!";
            die('');
        }
    }

    public function execute($query, $parameters = [])
    {
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        // push query to log
        $this->queries[] = ['query' => $query, 'parameters' => $parameters];

        $query = $this->db->prepare($query);
        if (!empty($parameters)) {
            $query->execute($parameters);
        } else {
            $query->execute();
        }

        return $query;
    }

    public function getOne($query, $parameters = [])
    {
        $original_params = $parameters;
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        $q = $this->execute($query, $parameters)->fetch(PDO::FETCH_ASSOC);

        $count = null;
        if (is_array($q)) {
            $count = count($q);
        } elseif (is_object($q)) {
            $count = $q->rowCount();
        }

        if ($count === 1 && !is_array($original_params) && !is_bool($q)) {
            return array_values($q)[0];
        }

        if (is_bool($q)) {
            return $q;
        }

        if (!isset($q['value']) && count($parameters) === 0) {
            return array_values($q)[0];
        }

        return $q;
    }

    public function getAll($query, $parameters = [])
    {
        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        return $this->execute($query, $parameters)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQueryLog(): array
    {
        return $this->queries;
    }
}
