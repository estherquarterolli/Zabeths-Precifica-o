<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$erro = null;
$sucesso = null;
$linhaSalva = null;

$produto = [
    'nome' => '',
    'categoria' => '',
    'rendimento_qtd' => 1,
    'rendimento_unidade' => 'unidade(s)',
    'tempo_preparo_minutos' => 30,
    'custo_embalagem' => 0,
    'margem_lucro' => '',
];
$itensPost = [];
$todosIngredientes = [];
$config = CONFIG_PADRAO;

try {
    gs_bootstrap();
    $todosIngredientes = repo_ler_ingredientes();
    $config = repo_ler_config();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'categoria' => trim($_POST['categoria'] ?? ''),
            'rendimento_qtd' => input_float($_POST['rendimento_qtd'] ?? null, 1),
            'rendimento_unidade' => trim($_POST['rendimento_unidade'] ?? '') ?: 'unidade(s)',
            'tempo_preparo_minutos' => input_float($_POST['tempo_preparo_minutos'] ?? null),
            'custo_embalagem' => input_float($_POST['custo_embalagem'] ?? null),
            'margem_lucro' => ($_POST['margem_lucro'] ?? '') !== '' ? input_float($_POST['margem_lucro']) : null,
        ];

        $linhasIngrediente = $_POST['ingrediente_linha'] ?? [];
        $quantidades = $_POST['quantidade'] ?? [];

        $porLinha = [];
        foreach ($todosIngredientes as $ing) {
            $porLinha[$ing['linha']] = $ing;
        }

        $itens = [];
        foreach ($linhasIngrediente as $i => $ingLinha) {
            $qtd = input_float($quantidades[$i] ?? null);
            if ($ingLinha !== '' && $qtd > 0 && isset($porLinha[(int) $ingLinha])) {
                $ing = $porLinha[(int) $ingLinha];
                $itens[] = [
                    'nome' => $ing['nome'],
                    'unidade' => $ing['unidade'],
                    'custo_unitario' => $ing['custo_unitario'],
                    'quantidade' => $qtd,
                ];
            }
        }

        if ($dados['nome'] === '') {
            $erro = 'Informe o nome do produto.';
        } elseif ($dados['rendimento_qtd'] <= 0) {
            $erro = 'O rendimento deve ser maior que zero.';
        } elseif (empty($itens)) {
            $erro = 'Adicione pelo menos um ingrediente com quantidade.';
        } else {
            $calculo = calcular_precificacao($dados, $itens, $config);
            $linhaSalva = repo_salvar_produto($dados, $itens, $calculo);
            $sucesso = 'Precificação salva na planilha com sucesso!';
        }

        $produto = $dados;
        $itensPost = [];
        foreach ($linhasIngrediente as $i => $ingLinha) {
            if ($ingLinha !== '') {
                $itensPost[] = ['linha' => (int) $ingLinha, 'quantidade' => input_float($quantidades[$i] ?? null)];
            }
        }
    }
} catch (SheetsException $e) {
    $erro = $e->getMessage();
}

$pageTitle = 'Nova precificação';
$activeNav = 'produtos';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <h1>Nova precificação</h1>
        <p>Monte a receita, veja o custo e o preço sugerido em tempo real e salve o resultado na sua planilha.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert error"><?= h($erro) ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alert success">
        <?= h($sucesso) ?>
        <?php if ($linhaSalva): ?> <a href="produto_view.php?linha=<?= (int) $linhaSalva ?>">Ver detalhes salvos →</a><?php endif; ?>
        · <a href="produtos.php">Ver histórico</a>
    </div>
<?php endif; ?>

<?php if (empty($todosIngredientes)): ?>
    <div class="alert warning">Cadastre pelo menos um ingrediente antes de precificar um produto. <a href="ingrediente_form.php">Cadastrar ingrediente</a></div>
<?php endif; ?>

<form method="post" id="produto-form">
    <div class="grid cols-2" style="align-items:start">
        <div>
            <div class="card">
                <h3>Dados do produto</h3>
                <div class="field">
                    <label for="nome">Nome do produto</label>
                    <input type="text" id="nome" name="nome" value="<?= h($produto['nome']) ?>" placeholder="Ex: Brigadeiro gourmet" required autofocus>
                </div>
                <div class="row-inline">
                    <div class="field">
                        <label for="categoria">Categoria (opcional)</label>
                        <input type="text" id="categoria" name="categoria" value="<?= h($produto['categoria'] ?? '') ?>" placeholder="Ex: Doces">
                    </div>
                    <div class="field">
                        <label for="tempo_preparo_minutos">Tempo de preparo (minutos)</label>
                        <input type="number" step="1" min="0" id="tempo_preparo_minutos" name="tempo_preparo_minutos" value="<?= h((string) $produto['tempo_preparo_minutos']) ?>" required>
                    </div>
                </div>
                <div class="row-inline">
                    <div class="field">
                        <label for="rendimento_qtd">Rendimento (quantidade)</label>
                        <input type="number" step="0.01" min="0.01" id="rendimento_qtd" name="rendimento_qtd" value="<?= h((string) $produto['rendimento_qtd']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="rendimento_unidade">Unidade do rendimento</label>
                        <input type="text" id="rendimento_unidade" name="rendimento_unidade" value="<?= h($produto['rendimento_unidade']) ?>" placeholder="unidades, kg, potes...">
                    </div>
                </div>
                <div class="row-inline">
                    <div class="field">
                        <label for="custo_embalagem">Custo de embalagem (por lote)</label>
                        <input type="number" step="0.01" min="0" id="custo_embalagem" name="custo_embalagem" value="<?= h((string) $produto['custo_embalagem']) ?>">
                    </div>
                    <div class="field" style="margin-bottom:0">
                        <label for="margem_lucro">Margem de lucro (%) — opcional</label>
                        <input type="number" step="0.01" min="0" max="99" id="margem_lucro" name="margem_lucro" value="<?= h((string) ($produto['margem_lucro'] ?? '')) ?>" placeholder="Padrão: <?= h((string) ($config['margem_lucro_padrao'] ?? 0)) ?>%">
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Ingredientes da receita</h3>
                <div id="ingredientes-lista"></div>
                <button type="button" class="btn secondary small" id="btn-add-ingrediente">+ Adicionar ingrediente</button>
            </div>
        </div>

        <div>
            <div class="card" id="resumo-card" style="position:sticky; top:20px">
                <h3>Resumo do cálculo</h3>
                <div class="breakdown-line"><span>Ingredientes</span><span id="r-ingredientes">R$ 0,00</span></div>
                <div class="breakdown-line"><span>Mão de obra</span><span id="r-mao-obra">R$ 0,00</span></div>
                <div class="breakdown-line"><span>Custos fixos (rateio)</span><span id="r-fixo">R$ 0,00</span></div>
                <div class="breakdown-line"><span>Embalagem</span><span id="r-embalagem">R$ 0,00</span></div>
                <div class="breakdown-line total"><span>Custo total (lote)</span><span id="r-custo-total">R$ 0,00</span></div>

                <div class="price-highlight" style="margin-top:18px">
                    <div class="label">Preço sugerido por unidade</div>
                    <div class="value" id="r-preco-unitario">R$ 0,00</div>
                    <div class="sub" id="r-preco-lote">Lote: R$ 0,00</div>
                </div>
                <div id="r-alerta" class="alert warning" style="display:none; margin-top:14px"></div>

                <button class="btn" type="submit" style="width:100%; justify-content:center; margin-top:18px">💾 Salvar na planilha</button>
            </div>
        </div>
    </div>
</form>

<template id="ingrediente-row-template">
    <div class="ingredient-row">
        <select name="ingrediente_linha[]" class="sel-ingrediente" required>
            <option value="">Selecione...</option>
            <?php foreach ($todosIngredientes as $ing): ?>
                <option value="<?= (int) $ing['linha'] ?>" data-custo="<?= h((string) $ing['custo_unitario']) ?>" data-unidade="<?= h($ing['unidade']) ?>">
                    <?= h($ing['nome']) ?> (<?= money($ing['custo_unitario']) ?>/<?= h($ing['unidade']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" step="0.001" min="0" name="quantidade[]" class="input-quantidade" placeholder="Qtd." required>
        <div class="linha-custo" style="font-size:0.85rem; color:var(--ink-soft); text-align:right">R$ 0,00</div>
        <button type="button" class="remove-row" title="Remover">✕</button>
    </div>
</template>

<script>
    window.ZABETHS_CONFIG = <?= json_encode([
        'salario_hora' => (float) ($config['salario_hora'] ?? 0),
        'custos_fixos_mensais' => (float) ($config['custos_fixos_mensais'] ?? 0),
        'producao_mensal_horas' => (float) ($config['producao_mensal_horas'] ?? 1),
        'percentual_taxa_cartao' => (float) ($config['percentual_taxa_cartao'] ?? 0),
        'percentual_imposto' => (float) ($config['percentual_imposto'] ?? 0),
        'margem_lucro_padrao' => (float) ($config['margem_lucro_padrao'] ?? 0),
    ]) ?>;
    window.ZABETHS_ITENS_INICIAIS = <?= json_encode(array_map(fn($it) => ['ingrediente_id' => $it['linha'], 'quantidade' => $it['quantidade']], $itensPost)) ?>;
</script>
<script src="assets/js/produto_form.js"></script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
