<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/Database.php';
set_time_limit(0);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache');

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM filas_download WHERE status = 'pendente' ORDER BY id ASC LIMIT 1";
$stmt = $db->query($query);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($item) {
    $filaId = $item['id'];
    $url = $item['url'];
    $tipo = $item['tipo'];
    $caminho = $item['caminho_salvamento'];
    $formato = $item['formato'];
    $qualidade = $item['qualidade'];

    $update = "UPDATE filas_download SET status = 'baixando' WHERE id = :id";
    $stmtUpdate = $db->prepare($update);
    $stmtUpdate->execute([':id' => $filaId]);

    if (!is_dir($caminho)) {
        mkdir($caminho, 0777, true);
    }

    $arquivoHistorico = rtrim($caminho, '/') . "/historico_downloads.txt";

    $ytdlp = "yt-dlp";

    $caminhoCookies = __DIR__ . '/../cookies.txt'; 

    // CORREÇÃO: Usar apenas o cliente mweb (compatível com cookies) e garantir suporte ao Node.js
    $comando = "$ytdlp --cookies " . escapeshellarg($caminhoCookies) . " --extractor-args \"youtube:player_client=mweb\" --newline --add-metadata --download-archive " . escapeshellarg($arquivoHistorico) . " ";

    $formatosAudio = ['mp3', 'm4a', 'flac', 'wav'];
    if (in_array($formato, $formatosAudio)) {
        $comando .= "-f bestaudio --extract-audio --audio-format " . escapeshellarg($formato) . " ";
        if ($qualidade === '320k' || $qualidade === '192k') {
            $comando .= "--audio-quality " . escapeshellarg($qualidade) . " ";
        } else {
            $comando .= "--audio-quality 0 ";
        }
    } else {
        if ($qualidade === '1080' || $qualidade === '720') {
            $comando .= "-f \"bestvideo[height<=$qualidade]+bestaudio/best\" --merge-output-format " . escapeshellarg($formato) . " ";
        } else {
            $comando .= "-f \"bestvideo+bestaudio/best\" --merge-output-format " . escapeshellarg($formato) . " ";
        }
    }

    $caminhoFormatado = escapeshellarg(rtrim($caminho, '/') . "/%(title)s [%(id)s].%(ext)s");
    $caminhoPlaylist = escapeshellarg(rtrim($caminho, '/') . "/%(playlist_index)s - %(title)s [%(id)s].%(ext)s");

    if ($tipo === 'playlist') {
        $comando .= "--yes-playlist -o $caminhoPlaylist ";
    } else {
        $comando .= "--no-playlist -o $caminhoFormatado ";
    }

    $comando .= escapeshellarg($url);

    echo "A iniciar o processamento utilizando pacotes do sistema...\n";
    flush();

    $handle = popen($comando . " 2>&1", "r");
    if ($handle) {
        while (!feof($handle)) {
            $buffer = fread($handle, 256);
            if ($buffer !== false) {
                echo mb_convert_encoding($buffer, 'UTF-8', 'auto');
                flush();
            }
        }
        pclose($handle);
    }

    $updateFim = "UPDATE filas_download SET status = 'concluido' WHERE id = :id";
    $stmtFim = $db->prepare($updateFim);
    $stmtFim->execute([':id' => $filaId]);

    if (file_exists($arquivoHistorico)) {
        $linhasHistorico = file($arquivoHistorico, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($linhasHistorico as $linha) {
            $partes = explode(' ', trim($linha));
            if (count($partes) >= 2) {
                $youtubeId = $partes[1];
                $checaMusica = $db->prepare("SELECT id FROM musicas WHERE youtube_id = :yid AND caminho_arquivo = :cam");
                $checaMusica->execute([':yid' => $youtubeId, ':cam' => $caminho]);
                if ($checaMusica->rowCount() == 0) {
                    $insereMusica = $db->prepare("INSERT INTO musicas (youtube_id, fila_id, titulo, caminho_arquivo) VALUES (:yid, :fid, :titulo, :caminho)");
                    $insereMusica->execute([
                        ':yid' => $youtubeId,
                        ':fid' => $filaId,
                        ':titulo' => 'Música Baixada',
                        ':caminho' => $caminho
                    ]);
                }
            }
        }
    }

    echo "\n----------------------------------------\n[SISTEMA] Processo concluído!\n";
    flush();
} else {
    echo "[SISTEMA] Nenhum download pendente na fila.\n";
    flush();
}
