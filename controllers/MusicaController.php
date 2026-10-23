<?php
require_once '../config/Database.php';
require_once '../models/Musica.php';

class MusicaController {
    public function obterMusicas() {
        $database = new Database();
        $db = $database->getConnection();
        
        $musica = new Musica($db);
        $stmt = $musica->listar();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>