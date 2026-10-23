<?php
require_once '../config/Database.php';

$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
header('Content-Type: application/json');

if ($acao == 'adicionar_fila') {
    $dados = json_decode(file_get_contents("php://input"));

    if (!empty($dados->url) && !empty($dados->caminho)) {

        $caminho_final = $dados->caminho;

        if (isset($dados->nome_pasta) && trim($dados->nome_pasta) !== '') {
            $caminho_final = rtrim($caminho_final, '\\/') . DIRECTORY_SEPARATOR . trim($dados->nome_pasta);
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
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar no banco.']);
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
// ==========================================
// BLOCO 3: LISTAR PASTAS (EXPLORADOR)
// ==========================================
if ($acao == 'listar_diretorios') {
    // Inicia na pasta do utilizador atual ou no C:\ raiz
    $dir = isset($_GET['dir']) && !empty($_GET['dir']) ? $_GET['dir'] : 'C:\\Users';
    $dir = realpath($dir) ?: $dir;

    $pastas = [];

    if (is_dir($dir)) {
        $itens = scandir($dir);
        foreach ($itens as $item) {

            // 1. Ignorar ocultos e sistema (. e $)
            if (substr($item, 0, 1) === '.' || substr($item, 0, 1) === '$') {
                continue;
            }

            $caminho_completo = $dir . DIRECTORY_SEPARATOR . $item;

            // 2. is_dir() confirma que é pasta, !is_link() esconde atalhos fantasma
            if (is_dir($caminho_completo) && !is_link($caminho_completo)) {

                // 3. Lista negra expandida com as pastas invisíveis do C:\Users
                $pastas_sistema = [
                    'AppData',
                    'ProgramData',
                    'System Volume Information',
                    'Recovery',
                    'PerfLogs',
                    'Ambiente de Impressão',
                    'Ambiente de Rede',
                    'Configurações Locais',
                    'Cookies',
                    'Dados de Aplicativos',
                    'Menu Iniciar',
                    'Modelos',
                    'Recent',
                    'SendTo',
                    'Histórico',
                    'NetHood',
                    'PrintHood',
                    'Local Settings',
                    'Application Data',
                    'My Documents',
                    'Default',
                    'Default User',
                    'Usuário Padrão',
                    'WDAGUtilityAccount',
                    'All Users'
                ];

                if (in_array($item, $pastas_sistema)) {
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
