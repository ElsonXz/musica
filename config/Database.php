<?php
class Database
{
    private $host = "localhost";
    private $db_name = "musica_db";
    private $username = "root";
    private $password = "E917284e*"; // A sua palavra-passe do MariaDB
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Erro de ligação: " . $exception->getMessage();
        }
        return $this->conn;
    }
} // <-- ESTA É A CHAVETA QUE FALTAVA PARA FECHAR A CLASSE!