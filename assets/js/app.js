let globalMusicGroups = {};
let caminhoNavegacao = "C:\\Users"; // Declarado apenas uma vez aqui no topo!

document.addEventListener("DOMContentLoaded", () => {
  const caminhoSalvo = localStorage.getItem("caminhoBaseSalvo");
  if (caminhoSalvo) document.getElementById("caminho").value = caminhoSalvo;
  carregarMusicas();

  // Lógica Dinâmica Áudio/Vídeo
  const radiosMidia = document.querySelectorAll('input[name="midia"]');
  radiosMidia.forEach((radio) => {
    radio.addEventListener("change", (e) => {
      const isAudio = e.target.value === "audio";
      document.getElementById("grupo_formato_audio").style.display = isAudio
        ? "inline-flex"
        : "none";
      document.getElementById("grupo_qualidade_audio").style.display = isAudio
        ? "inline-flex"
        : "none";

      document.getElementById("grupo_formato_video").style.display = isAudio
        ? "none"
        : "inline-flex";
      document.getElementById("grupo_qualidade_video").style.display = isAudio
        ? "none"
        : "inline-flex";

      if (isAudio) {
        document.getElementById("fmt_mp3").checked = true;
        document.getElementById("q_best_a").checked = true;
      } else {
        document.getElementById("fmt_mp4").checked = true;
        document.getElementById("q_best_v").checked = true;
      }
    });
  });
});

document
  .getElementById("formDownload")
  .addEventListener("submit", function (e) {
    e.preventDefault();
    const btn = document.getElementById("btnBaixar");
    const divMensagem = document.getElementById("mensagem");

    const midiaType = document.querySelector(
      'input[name="midia"]:checked',
    ).value;
    const formatName = `formato_${midiaType === "audio" ? "a" : "v"}`;
    const qualityName = `qualidade_${midiaType === "audio" ? "a" : "v"}`;

    const dados = {
      url: document.getElementById("url").value,
      tipo: document.querySelector('input[name="tipo"]:checked').value,
      formato: document.querySelector(`input[name="${formatName}"]:checked`)
        .value,
      qualidade: document.querySelector(`input[name="${qualityName}"]:checked`)
        .value,
      caminho: document.getElementById("caminho").value,
      nome_pasta: document.getElementById("nome_pasta").value,
    };

    localStorage.setItem("caminhoBaseSalvo", dados.caminho);
    btn.disabled = true;
    btn.innerText = "Processando...";

    fetch("api/rotas.php?acao=adicionar_fila", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(dados),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.sucesso) {
          divMensagem.style.display = "block";
          divMensagem.className = "sucesso";
          divMensagem.innerText = "Adicionado à fila! Iniciando o motor...";
          document.getElementById("formDownload").reset();
          document.getElementById("caminho").value =
            localStorage.getItem("caminhoBaseSalvo");
          iniciarProcessamentoAoVivo();
        } else {
          divMensagem.style.display = "block";
          divMensagem.className = "erro";
          divMensagem.innerText = "Erro: " + data.erro;
          btn.disabled = false;
          btn.innerText = "Adicionar à Fila de Downloads";
        }
      })
      .catch(() => {
        divMensagem.style.display = "block";
        divMensagem.className = "erro";
        divMensagem.innerText = "Erro de conexão.";
        btn.disabled = false;
        btn.innerText = "Adicionar à Fila de Downloads";
      });
  });

async function iniciarProcessamentoAoVivo() {
  const terminal = document.getElementById("terminalDeProgresso");
  const btn = document.getElementById("btnBaixar");
  terminal.value = "A ligar ao motor de processamento...\n\n";

  try {
    const response = await fetch("api/processador.php");
    const reader = response.body.getReader();
    const decoder = new TextDecoder("utf-8");

    while (true) {
      const { value, done } = await reader.read();
      if (done) break;
      terminal.value += decoder.decode(value, { stream: true });
      terminal.scrollTop = terminal.scrollHeight;
    }
    carregarMusicas();
  } catch (erro) {
    terminal.value += "\n\n[ERRO] A comunicação falhou: " + erro;
  } finally {
    btn.disabled = false;
    btn.innerText = "Adicionar à Fila de Downloads";
    document.getElementById("mensagem").style.display = "none";
  }
}

// === LÓGICA DO HISTÓRICO DE MÚSICAS ===
function carregarMusicas() {
  fetch("api/rotas.php?acao=listar_musicas")
    .then((response) => response.json())
    .then((dados) => {
      const gridMaster = document.getElementById("corpoTabelaMusicas");

      if (dados.length === 0) {
        gridMaster.innerHTML =
          '<div style="padding: 30px; text-align: center; color: var(--text-gray);"><i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>Nenhuma música baixada ainda.</div>';
        gridMaster.className = "";
        return;
      }

      globalMusicGroups = {};
      dados.forEach((musica) => {
        const pasta = musica.caminho_arquivo || "Sem Pasta";
        if (!globalMusicGroups[pasta]) globalMusicGroups[pasta] = [];
        globalMusicGroups[pasta].push(musica);
      });

      renderizarGradePastas();
    })
    .catch((error) => console.error("Erro ao carregar músicas:", error));
}

function renderizarGradePastas() {
  const gridMaster = document.getElementById("corpoTabelaMusicas");
  gridMaster.innerHTML = "";
  gridMaster.className = "windows-folder-grid";

  for (const pasta in globalMusicGroups) {
    const musicas = globalMusicGroups[pasta];
    const nomePastaCurto = pasta.split("\\").pop() || pasta;

    const folderDiv = document.createElement("div");
    folderDiv.className = "win-folder";
    folderDiv.innerHTML = `
            <i class="fa-solid fa-folder"></i>
            <span class="win-folder-name" title="${pasta}">${nomePastaCurto}</span>
            <span class="win-folder-count">${musicas.length} item(s)</span>
        `;
    folderDiv.onclick = () => renderizarConteudoDaPasta(pasta);
    gridMaster.appendChild(folderDiv);
  }
}

function renderizarConteudoDaPasta(pasta) {
  const gridMaster = document.getElementById("corpoTabelaMusicas");
  gridMaster.innerHTML = "";
  gridMaster.className = "";

  const nomePastaCurto = pasta.split("\\").pop() || pasta;

  const backBar = document.createElement("div");
  backBar.className = "folder-header-bar";
  backBar.innerHTML = `
        <button class="btn-back" onclick="renderizarGradePastas()"><i class="fa-solid fa-arrow-left"></i> Voltar</button>
        <h3 style="font-size: 1.1rem; color: var(--text-white); margin: 0;">
            <i class="fa-solid fa-folder-open" style="color: var(--accent-blue);"></i> /${nomePastaCurto}
        </h3>
    `;
  gridMaster.appendChild(backBar);

  const gridCards = document.createElement("div");
  gridCards.className = "library-grid";

  globalMusicGroups[pasta].forEach((musica) => {
    const card = document.createElement("div");
    card.className = "music-card";
    const dataFormatada = new Date(musica.data_download).toLocaleString(
      "pt-BR",
    );
    const miniaturaUrl = `https://img.youtube.com/vi/${musica.youtube_id}/mqdefault.jpg`;
    const idElemento = `titulo_${musica.youtube_id}`;

    card.innerHTML = `
            <img src="${miniaturaUrl}" class="card-thumb" alt="Capa">
            <div class="card-info">
                <a href="https://youtube.com/watch?v=${musica.youtube_id}" target="_blank" id="${idElemento}" class="card-title" title="Abrir no YouTube">A pesquisar nome...</a>
                <div class="card-meta">
                    <span><i class="fa-regular fa-clock"></i> ${dataFormatada}</span>
                </div>
            </div>
        `;
    gridCards.appendChild(card);

    fetch(
      `https://noembed.com/embed?dataType=json&url=https://www.youtube.com/watch?v=${musica.youtube_id}`,
    )
      .then((res) => res.json())
      .then((data) => {
        document.getElementById(idElemento).innerText =
          data.title || "Faixa " + musica.youtube_id;
      })
      .catch(() => {
        document.getElementById(idElemento).innerText =
          "Faixa " + musica.youtube_id;
      });
  });

  gridMaster.appendChild(gridCards);
}

// === LÓGICA DO EXPLORADOR DE PASTAS EMBUTIDO ===
function abrirSeletorPastas() {
  const inputAtual = document.getElementById("caminho").value;
  caminhoNavegacao = inputAtual ? inputAtual : "C:\\Users";

  document.getElementById("modalPastas").style.display = "flex";
  carregarDiretorios(caminhoNavegacao);
}

function fecharSeletorPastas() {
  document.getElementById("modalPastas").style.display = "none";
}

function carregarDiretorios(dir) {
  const container = document.getElementById("listaPastas");
  container.innerHTML =
    '<div style="text-align:center; padding: 40px;"><i class="fa-solid fa-spinner fa-spin fa-2x" style="color: var(--accent-blue);"></i></div>';

  fetch(`api/rotas.php?acao=listar_diretorios&dir=${encodeURIComponent(dir)}`)
    .then((res) => res.json())
    .then((data) => {
      caminhoNavegacao = data.atual;
      document.getElementById("caminhoAtualModal").value = caminhoNavegacao;
      container.innerHTML = "";

      if (data.pastas.length === 0) {
        container.innerHTML =
          '<div style="text-align:center; padding: 20px; color: var(--text-gray);">Nenhuma subpasta encontrada.</div>';
        return;
      }

      data.pastas.forEach((pasta) => {
        const div = document.createElement("div");
        div.className = "folder-item";
        div.innerHTML = `<i class="fa-solid fa-folder"></i> <span>${pasta.nome}</span>`;
        div.onclick = () => carregarDiretorios(pasta.caminho);
        container.appendChild(div);
      });
    })
    .catch(() => {
      container.innerHTML =
        '<div style="text-align:center; padding: 20px; color: #ef4444;">Erro de permissão ou caminho não encontrado.</div>';
    });
}

function navegarPastaAcima() {
  carregarDiretorios(caminhoNavegacao + "\\..");
}

function confirmarPasta() {
  document.getElementById("caminho").value = caminhoNavegacao;
  fecharSeletorPastas();
}
