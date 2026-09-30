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
    var raiz = String(document.body.dataset.raiz || '').replace(/\/+$/, '');
    var api = raiz + '/public';

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

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
            var alvo  = document.getElementById(input.dataset.target);
            var url   = api + input.dataset.url;
            var lista = input.closest('.autocomplete').querySelector('.autocomplete-lista');
            var min   = parseInt(input.dataset.min || '2', 10);

            function fechar() {
                lista.innerHTML = '';
                lista.style.display = 'none';
            }

            function escolher(item) {
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
                    fechar();
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

            input.addEventListener('input', debounce(function () {
                var termo = input.value.trim();
                if (termo.length < min) { fechar(); return; }

                fetch(url + '?q=' + encodeURIComponent(termo))
                    .then(function (r) { return r.json(); })
                    .then(render)
                    .catch(fechar);
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