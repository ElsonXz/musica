<?php
require_once '../config/Database.php';

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
header('Content-Type: application/json');

if ($acao == 'adicionar_fila') {
    $dados = json_decode(file_get_contents("php://input"));

    if (!empty($dados->url) && !empty($dados->caminho)) {
        $caminho_final = $dados->caminho;
        if (isset($dados->nome_pasta) && trim($dados->nome_pasta) !== '') {
            $caminho_final = rtrim($caminho_final, '/') . '/' . trim($dados->nome_pasta);
        }

        $database = new Database();
        $db = $database->getConnection();

        $query = "INSERT INTO filas_download (url, tipo, caminho_salvamento, formato, qualidade) 
                  VALUES (:url, :tipo, :caminho, :formato, :qualidade)";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':url', $dados->url);
        $stmt->bindParam(':tipo', $dados->tipo);
        $stmt->bindParam(':caminho', $caminho_final);
        $stmt->bindParam(':formato', $dados->formato);
        $stmt->bindParam(':qualidade', $dados->qualidade);

        if ($stmt->execute()) {
            echo json_encode(['sucesso' => true]);
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao guardar na base de dados.']);
        }
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
    }
}

if ($acao == 'listar_musicas') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        if (!$db) {
            echo json_encode([]);
            exit;
        }

        $query = "SELECT youtube_id, titulo, caminho_arquivo, data_download FROM musicas ORDER BY data_download DESC LIMIT 200";
        $stmt = $db->query($query);

        if ($stmt) {
            $musicas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($musicas);
        } else {
            echo json_encode([]);
        }
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

if ($acao == 'listar_diretorios') {
    // No Linux, /home é um bom ponto de partida padrão para utilizadores
    $dir = isset($_GET['dir']) && !empty($_GET['dir']) ? $_GET['dir'] : '/home';
    $dir = realpath($dir) ?: $dir;
    $pastas = [];

    if (is_dir($dir)) {
        $itens = scandir($dir);
        foreach ($itens as $item) {
            if (substr($item, 0, 1) === '.') {
                continue;
            }
            $caminho_completo = rtrim($dir, '/') . '/' . $item;

            if (is_dir($caminho_completo) && !is_link($caminho_completo)) {
                // Ocultar pastas de sistema críticas do Linux por segurança
                $pastas_sistema = [
                    'bin',
                    'boot',
                    'dev',
                    'etc',
                    'lib',
                    'lib32',
                    'lib64',
                    'libx32',
                    'lost+found',
                    'media',
                    'mnt',
                    'proc',
                    'root',
                    'run',
                    'sbin',
                    'snap',
                    'srv',
                    'sys',
                    'usr'
                ];

                // Se a pasta atual for a raiz (/) e for uma pasta de sistema, ocultamos
                if ($dir === '/' && in_array($item, $pastas_sistema)) {
                    continue;
                }

                $pastas[] = [
                    'caminho' => $caminho_completo,
                    'nome' => $item
                ];
            }
        }
    }

    echo json_encode([
        'atual' => $dir,
        'pastas' => $pastas
    ]);
    exit;
}
