// Catálogo de filmes — modal de detalhes da US13
(function () {
    // Pega a raiz do projeto a partir do atributo data-raiz do body.
    // Isso evita que o código quebre em ambientes com subpastas, como /Locadora-LoucaWeb.
    var raiz = String(document.body.dataset.raiz || '').replace(/\/+$/, '');

    // Escapa caracteres HTML para evitar injeção de conteúdo na renderização do modal.
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Monta o HTML do modal com os dados do filme.
    function ficha(f) {
        // Formata o valor para moeda brasileira.
        var valor = 'R$ ' + parseFloat(f.valor).toFixed(2).replace('.', ',');

        // Exibe badge de disponibilidade conforme a quantidade de cópias.
        var badge = f.disponibilidade > 0
            ? '<span class="badge disponivel">Disponível &middot; ' + esc(f.disponibilidade) + ' cópia(s)</span>'
            : '<span class="badge indisponivel">Indisponível</span>';

        // Gera a lista de atores se existir elenco; caso contrário, mostra mensagem.
        var atores = (f.atores && f.atores.length)
            ? '<ul class="elenco">' + f.atores.map(function (a) {
                  return '<li>' + esc(a.nome) + (a.personagem ? ' — <em>' + esc(a.personagem) + '</em>' : '') + '</li>';
              }).join('') + '</ul>'
            : '<p><em>Nenhum ator cadastrado.</em></p>';

        // Mostra a sinopse do filme ou uma mensagem padrão.
        var sinopse = f.sinopse ? '<p>' + esc(f.sinopse) + '</p>' : '<p><em>Sinopse indisponível.</em></p>';

        // Retorna o HTML completo do modal.
        return ''
            + (f.poster_url ? '<img class="modal-poster" src="' + esc(f.poster_url) + '" alt="Poster de ' + esc(f.titulo) + '">'
                            : '<div class="poster-falso">Sem Imagem</div>')
            + '<div class="modal-infos">'
            + '<h2>' + esc(f.titulo) + '</h2>'
            + '<p class="modal-genero">Gênero: ' + esc(f.genero) + '</p>'
            + '<p class="modal-valor">' + valor + '</p>' + badge + '<hr>' + '<br/>'
            + '<div class="twelve columns">'
            + '<h3>Sinopse</h3>' + sinopse
            + '</div>'
            + '<h3>Elenco</h3>' + atores
            + '</div>';
    }

    // Pega os elementos do modal da página.
    var modal  = document.getElementById('modal-filme');
    var corpo  = document.getElementById('modal-corpo');
    var fechar = document.getElementById('modal-fechar');

    // Se o modal não existir, para a execução.
    if (!modal || !corpo || !fechar) return;

    // Adiciona o evento de clique em todos os links de filme da página.
    document.querySelectorAll('a.link-filme').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Pega o ID do filme do atributo data-filme-id, codifica para URL.
            var id = encodeURIComponent(this.dataset.filmeId);
            var url = window.location.origin + raiz + '/public/filmes/' + id;

            // Abre o modal e mostra mensagem de carregamento.
            modal.hidden = false;
            corpo.innerHTML = '<p>Carregando...</p>';

            // Faz a requisição para buscar os detalhes do filme.
            fetch(url)
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (filme) {
                    // Insere o HTML montado com os dados do filme no corpo do modal.
                    corpo.innerHTML = ficha(filme);
                })
                .catch(function () {
                    // Caso a API falhe, mostra mensagem de erro e link alternativo.
                    corpo.innerHTML = '<p>Não foi possível carregar os detalhes.</p>'
                                    + '<a href="detalharFilme.php?id=' + esc(id) + '">Abrir página dedicada</a>';
                });
        });
    });

    // Fecha o modal ao clicar no botão de fechar.
    fechar.addEventListener('click', function () { modal.hidden = true; });

    // Fecha o modal ao clicar fora da área do conteúdo.
    modal.addEventListener('click', function (e) {
        if (e.target === modal) modal.hidden = true;
    });

    // Fecha o modal quando a tecla Escape for pressionada.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) modal.hidden = true;
    });
})();