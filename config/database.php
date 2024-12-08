<?php

class Database
{
    private $host = 'localhost';
    private $port = '1521';
    private $sid = 'xe';
    private $username = 'DVF';
    private $password = 'DVF';

    private $pdo;
    private $stmt;

    public function __construct()
    {
        $dsn = "oci:dbname=//{$this->host}:{$this->port}/{$this->sid}";
        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function query($sql)
    {
        $this->stmt = $this->pdo->prepare($sql);
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function executeProcedureWithCursor($procedureName)
    {
        try {
            // Establish a direct connection using oci_connect
            $conn = oci_connect($this->username, $this->password, "//{$this->host}:{$this->port}/{$this->sid}");
            if (!$conn) {
                $e = oci_error();
                throw new Exception("Connection failed: " . $e['message']);
            }

            // Prepare the PL/SQL block to execute the procedure
            $sql = "BEGIN $procedureName(:p_cursor); END;";
            $stmt = oci_parse($conn, $sql);

            // Bind a cursor to the output parameter
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":p_cursor", $cursor, -1, OCI_B_CURSOR);

            // Execute the PL/SQL block
            if (!oci_execute($stmt)) {
                $e = oci_error($stmt);
                throw new Exception("Error executing procedure: " . $e['message']);
            }

            // Execute the cursor to fetch data
            oci_execute($cursor, OCI_DEFAULT);

            // Fetch the data from the cursor
            $results = [];
            while (($row = oci_fetch_assoc($cursor)) !== false) {
                $results[] = $row;
            }

            // Free resources
            oci_free_statement($stmt);
            oci_free_statement($cursor);
            oci_close($conn);

            return $results;
        } catch (Exception $e) {
            throw new Exception("Error: " . $e->getMessage());
        }
    }

    public function executeProcedureWithCursorAndParam($procedureName, $paramName, $paramValue)
    {
        try {
            $conn = oci_connect($this->username, $this->password, "//{$this->host}:{$this->port}/{$this->sid}");
            if (!$conn) {
                $e = oci_error();
                throw new Exception("Connection failed: " . $e['message']);
            }

            $sql = "BEGIN $procedureName(:$paramName, :p_cursor); END;";
            $stmt = oci_parse($conn, $sql);

            // Bind the input parameter
            oci_bind_by_name($stmt, ":$paramName", $paramValue);

            // Bind the output cursor
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":p_cursor", $cursor, -1, OCI_B_CURSOR);

            // Execute the statement
            if (!oci_execute($stmt)) {
                $e = oci_error($stmt);
                throw new Exception("Error executing procedure: " . $e['message']);
            }

            // Execute the cursor to fetch the data
            oci_execute($cursor, OCI_DEFAULT);

            // Fetch data from the cursor
            $result = [];
            while (($row = oci_fetch_assoc($cursor)) !== false) {
                $result[] = $row;
            }

            // Free resources
            oci_free_statement($stmt);
            oci_free_statement($cursor);
            oci_close($conn);

            return $result;
        } catch (Exception $e) {
            throw new Exception("Error: " . $e->getMessage());
        }
    }

    public function execute()
    {
        return $this->stmt->execute();
    }

    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function single()
    {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function commit()
    {
        return $this->pdo->commit();
    }

    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    public function timestampFormat($dateInput)
    {
        return str_replace('T', ' ', $dateInput) . ':00';
    }
}
