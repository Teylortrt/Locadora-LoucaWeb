// Autocomplete reutilizável para busca de cliente/filme (acervo grande).
// Markup esperada:
//   <div class="autocomplete">
//     <input type="hidden" id="alvo" name="id_cliente">
//     <input type="text" class="autocomplete-input" data-ac
//            data-target="alvo" data-url="/clientes"
//            data-min="2" placeholder="..." autocomplete="off">
//     <div class="autocomplete-lista"></div>
//   </div>
// O body precisa ter data-raiz com o caminho base do app (como em filmes.php).
(function () {
    // Combina a raiz definida no body com a rota informada no próprio campo.
    var raiz = String(document.body.dataset.raiz || '').replace(/\/+$/, '');
    var api = raiz + '/public';

    // Os nomes recebidos da API são inseridos no HTML como texto seguro.
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Aguarda a digitação parar antes de enviar outra busca ao servidor.
    function debounce(fn, ms) {
        var t;
        return function () {
            var args = arguments, ctx = this;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms);
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input.autocomplete-input[data-ac]').forEach(function (input) {
            // O campo visível mostra o nome; o campo oculto guarda o ID enviado no formulário.
            var alvo  = document.getElementById(input.dataset.target);
            var url   = api + input.dataset.url;
            var lista = input.closest('.autocomplete').querySelector('.autocomplete-lista');
            var min   = parseInt(input.dataset.min || '2', 10);

            function fechar() {
                lista.innerHTML = '';
                lista.style.display = 'none';
            }

            function mensagem(texto) {
                lista.innerHTML = '';
                var estado = document.createElement('div');
                estado.className = 'autocomplete-item';
                estado.textContent = texto;
                estado.setAttribute('aria-live', 'polite');
                lista.appendChild(estado);
                lista.style.display = 'block';
            }

            function escolher(item) {
                // Só uma opção clicada define um cliente válido para o envio.
                if (alvo) {
                    alvo.value = item.id;
                    alvo.setAttribute('data-label', item.label);
                    alvo.dispatchEvent(new Event('change', { bubbles: true }));
                }
                input.value = item.label;
                fechar();
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            function render(itens) {
                lista.innerHTML = '';
                if (!Array.isArray(itens) || !itens.length) {
                    mensagem('Nenhum cliente encontrado.');
                    return;
                }
                itens.forEach(function (item) {
                    var o = document.createElement('div');
                    o.className = 'autocomplete-item';
                    o.innerHTML = esc(item.label)
                        + (item.sub && String(item.sub).trim() ? ' <small>' + esc(item.sub) + '</small>' : '');
                    o.addEventListener('mousedown', function (e) { e.preventDefault(); escolher(item); });
                    lista.appendChild(o);
                });
                lista.style.display = 'block';
            }

            input.addEventListener('input', function () {
                // Evita enviar o ID anterior se o usuário editar o nome escolhido.
                if (alvo && input.value.trim() !== alvo.getAttribute('data-label')) {
                    alvo.value = '';
                    alvo.removeAttribute('data-label');
                }
            });

            input.addEventListener('input', debounce(function () {
                var termo = input.value.trim();
                if (termo.length < min) { fechar(); return; }

                mensagem('Buscando clientes...');
                fetch(url + '?q=' + encodeURIComponent(termo))
                    .then(function (r) {
                        if (!r.ok) throw new Error('Falha na busca de clientes: ' + r.status);
                        return r.json();
                    })
                    .then(function (itens) {
                        // Uma resposta antiga não deve substituir as sugestões da busca mais recente.
                        if (input.value.trim() === termo) render(itens);
                    })
                    .catch(function () {
                        if (input.value.trim() === termo) mensagem('Não foi possível buscar clientes.');
                    });
            }, 200));

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') fechar();
            });

            document.addEventListener('click', function (e) {
                if (!input.closest('.autocomplete').contains(e.target)) fechar();
            });
        });
    });
})();