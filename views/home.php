<div class="dashboard-grid">
    <section class="panel-card glass">
        <div class="panel-header">
            <h2>Novo Download</h2>
        </div>

        <form id="formDownload" class="interactive-form">
            <div class="form-group full-width">
                <label for="url">Link do YouTube</label>
                <div class="input-with-icon">
                    <input type="text" id="url" name="url" placeholder="https://www.youtube.com/..." required>
                    <i class="fa-solid fa-wand-magic-sparkles" style="position: absolute; right: 14px; left: auto; color: var(--accent-blue);"></i>
                </div>
            </div>

            <div class="form-row-two">
                <div class="form-group">
                    <label>Mídia</label>
                    <div class="radio-group">
                        <input type="radio" id="midia_audio" name="midia" value="audio" checked> <label for="midia_audio">Áudio</label>
                        <input type="radio" id="midia_video" name="midia" value="video"> <label for="midia_video">Vídeo</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <div class="radio-group">
                        <input type="radio" id="tipo_single" name="tipo" value="single" checked> <label for="tipo_single">Single</label>
                        <input type="radio" id="tipo_playlist" name="tipo" value="playlist"> <label for="tipo_playlist">Playlist</label>
                    </div>
                </div>
            </div>

            <div class="form-row-two">
                <div class="form-group">
                    <label>Formato</label>
                    <div class="radio-group" id="grupo_formato_audio">
                        <input type="radio" id="fmt_mp3" name="formato_a" value="mp3" checked> <label for="fmt_mp3">MP3</label>
                        <input type="radio" id="fmt_m4a" name="formato_a" value="m4a"> <label for="fmt_m4a">M4A</label>
                        <input type="radio" id="fmt_flac" name="formato_a" value="flac"> <label for="fmt_flac">FLAC</label>
                    </div>
                    <div class="radio-group" id="grupo_formato_video" style="display: none;">
                        <input type="radio" id="fmt_mp4" name="formato_v" value="mp4" checked> <label for="fmt_mp4">MP4</label>
                        <input type="radio" id="fmt_mkv" name="formato_v" value="mkv"> <label for="fmt_mkv">MKV</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Qualidade</label>
                    <div class="radio-group" id="grupo_qualidade_audio">
                        <input type="radio" id="q_best_a" name="qualidade_a" value="best" checked> <label for="q_best_a">Máxima</label>
                        <input type="radio" id="q_320" name="qualidade_a" value="320k"> <label for="q_320">320k</label>
                        <input type="radio" id="q_192" name="qualidade_a" value="192k"> <label for="q_192">192k</label>
                    </div>
                    <div class="radio-group" id="grupo_qualidade_video" style="display: none;">
                        <input type="radio" id="q_best_v" name="qualidade_v" value="best" checked> <label for="q_best_v">Máxima</label>
                        <input type="radio" id="q_1080" name="qualidade_v" value="1080"> <label for="q_1080">1080p</label>
                        <input type="radio" id="q_720" name="qualidade_v" value="720"> <label for="q_720">720p</label>
                    </div>
                </div>
            </div>

            <div class="form-row-two">
                <div class="form-group">
                    <label for="caminho">Caminho Base</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="caminho" name="caminho" placeholder="Ex: C:\Downloads" required style="flex-grow: 1;">
                        <button type="button" onclick="abrirSeletorPastas()" class="btn-search-folder">
                            <i class="fa-solid fa-folder-tree"></i> Explorar
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="nome_pasta">Nova Pasta (Opcional)</label>
                    <input type="text" id="nome_pasta" name="nome_pasta" placeholder="Ex: Músicas Novas">
                </div>
            </div>

            <button type="submit" id="btnBaixar" class="btn-primary-gradient">
                <i class="fa-solid fa-download"></i> Adicionar à Fila de Downloads
            </button>
        </form>
        <div id="mensagem" class="alert"></div>
    </section>

    <!-- Terminal -->
    <section class="panel-card glass">
        <div class="panel-header">
            <h2>Terminal de Processamento</h2>
        </div>
        <div class="terminal-wrapper">
            <textarea id="terminalDeProgresso" readonly>Aguardando novos downloads...</textarea>
        </div>
    </section>
</div>

<!-- Histórico Visual -->
<section class="panel-card glass library-section" style="margin-top: 25px;">
    <div class="panel-header">
        <h2>Histórico de Músicas Baixadas</h2>
    </div>
    <div class="library-grid-wrapper">
        <div id="corpoTabelaMusicas">
            <!-- As pastas ou os cards serão gerados aqui pelo JS -->
        </div>
    </div>
</section>

<!-- Modal do Explorador de Pastas -->
<div id="modalPastas" class="modal-overlay" style="display: none;">
    <div class="modal-content glass">
        <div class="modal-header">
            <h3><i class="fa-solid fa-folder-open" style="color: var(--accent-blue);"></i> Selecionar Pasta Destino</h3>
            <button type="button" onclick="fecharSeletorPastas()" class="btn-close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="current-path-bar">
                <button onclick="navegarPastaAcima()" class="btn-up" title="Subir uma pasta"><i class="fa-solid fa-level-up-alt"></i></button>
                <input type="text" id="caminhoAtualModal" readonly>
            </div>
            <div id="listaPastas" class="folder-list"></div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="confirmarPasta()" class="btn-primary-gradient" style="margin: 0; padding: 10px 25px; width: auto;">
                <i class="fa-solid fa-check"></i> Selecionar Esta Pasta
            </button>
        </div>
    </div>
</div>