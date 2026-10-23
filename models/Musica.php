<?php
class Musica {
    private $conn;
    private $table_name = "musicas";

    public $id;
    public $titulo;
    public $artista;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para buscar os dados no banco
    public function listar() {
        $query = "SELECT id, titulo, artista FROM " . $this->table_name . " ORDER BY titulo ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>