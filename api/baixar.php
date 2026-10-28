<?php
require_once '../config/Database.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $yid = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id']); // Proteção básica

    $database = new Database();
    $db = $database->getConnection();

    // Procura a pasta onde a música foi guardada
    $stmt = $db->prepare("SELECT caminho_arquivo FROM musicas WHERE youtube_id = :yid LIMIT 1");
    $stmt->execute([':yid' => $yid]);
    $musica = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($musica) {
        $pasta = rtrim($musica['caminho_arquivo'], '/');
        // Procura qualquer ficheiro na pasta que contenha o ID do YouTube no nome
        $ficheiros = glob($pasta . "/*\[" . $yid . "\].*");

        if (!empty($ficheiros)) {
            $ficheiro = $ficheiros[0];

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($ficheiro) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($ficheiro));

            readfile($ficheiro);
            exit;
        }
    }

    echo "Ficheiro não encontrado no servidor.";
} else {
    echo "ID inválido.";
}
