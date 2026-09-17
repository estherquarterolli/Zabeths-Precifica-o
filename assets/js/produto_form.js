(function () {
    const lista = document.getElementById('ingredientes-lista');
    const template = document.getElementById('ingrediente-row-template');
    const btnAdd = document.getElementById('btn-add-ingrediente');
    const config = window.ZABETHS_CONFIG || {};

    function formatMoney(value) {
        if (!isFinite(value)) value = 0;
        return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function addRow(ingredienteId, quantidade) {
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.ingredient-row');
        const select = row.querySelector('.sel-ingrediente');
        const qtdInput = row.querySelector('.input-quantidade');
        const removeBtn = row.querySelector('.remove-row');

        if (ingredienteId) select.value = String(ingredienteId);
        if (quantidade !== undefined && quantidade !== null) qtdInput.value = quantidade;

        select.addEventListener('change', recalcular);
        qtdInput.addEventListener('input', recalcular);
        removeBtn.addEventListener('click', function () {
            row.remove();
            recalcular();
        });

        lista.appendChild(row);
    }

    function custoIngredientes() {
        let total = 0;
        lista.querySelectorAll('.ingredient-row').forEach(function (row) {
            const select = row.querySelector('.sel-ingrediente');
            const qtdInput = row.querySelector('.input-quantidade');
            const linhaCusto = row.querySelector('.linha-custo');
            const option = select.options[select.selectedIndex];
            const custoUnitario = option ? parseFloat(option.getAttribute('data-custo') || '0') : 0;
            const quantidade = parseFloat(qtdInput.value || '0');
            const custoLinha = (isFinite(custoUnitario) ? custoUnitario : 0) * (isFinite(quantidade) ? quantidade : 0);
            linhaCusto.textContent = formatMoney(custoLinha);
            total += custoLinha;
        });
        return total;
    }

    function val(id) {
        const el = document.getElementById(id);
        if (!el) return 0;
        const v = parseFloat((el.value || '0').replace(',', '.'));
        return isFinite(v) ? v : 0;
    }

    function recalcular() {
        const custoIng = custoIngredientes();

        const tempoMin = val('tempo_preparo_minutos');
        const tempoHoras = tempoMin / 60;
        const custoMaoObra = tempoHoras * (config.salario_hora || 0);

        const custoFixoHora = (config.producao_mensal_horas > 0)
            ? (config.custos_fixos_mensais || 0) / config.producao_mensal_horas
            : 0;
        const custoFixoRateado = custoFixoHora * tempoHoras;

        const custoEmbalagem = val('custo_embalagem');

        const custoTotal = custoIng + custoMaoObra + custoFixoRateado + custoEmbalagem;

        const margemInput = document.getElementById('margem_lucro').value;
        const margem = margemInput !== '' ? parseFloat(margemInput.replace(',', '.')) : (config.margem_lucro_padrao || 0);
        const taxaCartao = config.percentual_taxa_cartao || 0;
        const imposto = config.percentual_imposto || 0;
        const percentualTotal = margem + taxaCartao + imposto;
        const divisor = 1 - (percentualTotal / 100);

        const alertaEl = document.getElementById('r-alerta');
        const alertaTexto = document.getElementById('r-alerta-texto');
        let precoTotal;
        if (divisor <= 0) {
            precoTotal = custoTotal;
            alertaEl.style.display = 'flex';
            alertaTexto.textContent = 'A soma de margem + taxa de cartão + imposto está maior ou igual a 100%. Ajuste os percentuais.';
        } else {
            precoTotal = custoTotal / divisor;
            alertaEl.style.display = 'none';
        }

        const rendimento = val('rendimento_qtd') || 1;
        const precoUnitario = precoTotal / rendimento;

        document.getElementById('r-ingredientes').textContent = formatMoney(custoIng);
        document.getElementById('r-mao-obra').textContent = formatMoney(custoMaoObra);
        document.getElementById('r-fixo').textContent = formatMoney(custoFixoRateado);
        document.getElementById('r-embalagem').textContent = formatMoney(custoEmbalagem);
        document.getElementById('r-custo-total').textContent = formatMoney(custoTotal);
        document.getElementById('r-preco-unitario').textContent = formatMoney(precoUnitario);
        document.getElementById('r-preco-lote').textContent = 'Lote: ' + formatMoney(precoTotal);
    }

    btnAdd.addEventListener('click', function () {
        addRow();
        recalcular();
    });

    ['tempo_preparo_minutos', 'custo_embalagem', 'margem_lucro', 'rendimento_qtd'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', recalcular);
    });

    const iniciais = window.ZABETHS_ITENS_INICIAIS || [];
    if (iniciais.length > 0) {
        iniciais.forEach(function (item) {
            addRow(item.ingrediente_id, item.quantidade);
        });
    } else {
        addRow();
    }

    recalcular();
})();
