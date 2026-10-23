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

    // --- INÍCIO DA CONFIGURAÇÃO DO COMANDO (COM NUMERAÇÃO INTELIGENTE) ---
    $caminhoBin = "C:\\xampp\\htdocs\\musica\\bin\\";
    $ytdlp = $caminhoBin . "yt-dlp.exe";
    $ffmpeg = "C:\\xampp\\htdocs\\musica\\bin";

    if (!is_dir($caminho)) {
        mkdir($caminho, 0777, true);
    }

    $arquivoHistorico = $caminho . "\\historico_downloads.txt";

    
    $comando = "\"$ytdlp\" --newline --ffmpeg-location \"$ffmpeg\" --add-metadata --download-archive \"$arquivoHistorico\" ";

    $formatosAudio = ['mp3', 'm4a', 'flac', 'wav'];

    if (in_array($formato, $formatosAudio)) {
        $comando .= "-f bestaudio --extract-audio --audio-format $formato ";
        if ($qualidade === '320k' || $qualidade === '192k') {
            $comando .= "--audio-quality $qualidade ";
        } else {
            $comando .= "--audio-quality 0 ";
        }
    } else {
        if ($qualidade === '1080' || $qualidade === '720') {
            $comando .= "-f \"bestvideo[height<=$qualidade]+bestaudio/best\" --merge-output-format $formato ";
        } else {
            $comando .= "-f \"bestvideo+bestaudio/best\" --merge-output-format $formato ";
        }
    }

    if ($tipo === 'playlist') {
        $comando .= "--yes-playlist -o \"$caminho\\%(playlist_index)s - %(title)s.%(ext)s\" ";
    } else {
        $comando .= "--no-playlist -o \"$caminho\\%(title)s.%(ext)s\" ";
    }

    $comando .= "\"$url\"";
    // --- FIM DA CONFIGURAÇÃO DO COMANDO ---

    echo "A iniciar o processamento...\n";
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
