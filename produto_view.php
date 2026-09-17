<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$linha = isset($_GET['linha']) ? (int) $_GET['linha'] : null;
$erro = null;
$p = null;

try {
    gs_bootstrap();
    if (!$linha) {
        throw new SheetsException('Registro não informado.');
    }
    $p = repo_buscar_produto($linha);
    if (!$p) {
        throw new SheetsException('Registro não encontrado na planilha (pode ter sido excluído ou a linha mudou).');
    }
} catch (SheetsException $e) {
    $erro = $e->getMessage();
}

$pageTitle = $p['nome'] ?? 'Precificação';
$activeNav = 'produtos';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <h1><?= h($pageTitle) ?></h1>
        <?php if ($p): ?>
            <?php if ($p['categoria']): ?><span class="badge"><?= h($p['categoria']) ?></span><?php endif; ?>
            <p>Salvo em <?= h($p['data_hora']) ?></p>
        <?php endif; ?>
    </div>
    <div style="display:flex; gap:8px">
        <a class="btn secondary" href="produto_form.php">Nova precificação</a>
        <a class="btn secondary" href="produtos.php">Voltar</a>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert error"><?= h($erro) ?></div>
<?php else: ?>

<div class="grid cols-2" style="align-items:start">
    <div>
        <div class="card">
            <h3>Ingredientes usados</h3>
            <p><?= h($p['ingredientes_detalhe']) ?: '—' ?></p>
        </div>

        <div class="card">
            <h3>Detalhes</h3>
            <div class="breakdown-line"><span>Rendimento</span><span><?= num($p['rendimento_qtd'], 0) ?> <?= h($p['rendimento_unidade']) ?></span></div>
            <div class="breakdown-line"><span>Tempo de preparo</span><span><?= num($p['tempo_preparo_minutos'], 0) ?> min</span></div>
            <div class="breakdown-line"><span>Margem aplicada</span><span><?= num($p['margem'], 1) ?>%</span></div>
            <div class="breakdown-line"><span>Taxa de cartão</span><span><?= num($p['taxa_cartao'], 2) ?>%</span></div>
            <div class="breakdown-line"><span>Imposto</span><span><?= num($p['imposto'], 2) ?>%</span></div>
        </div>
    </div>

    <div class="card">
        <h3>Composição do custo</h3>
        <div class="breakdown-line"><span>Ingredientes</span><span><?= money($p['custo_ingredientes']) ?></span></div>
        <div class="breakdown-line"><span>Mão de obra</span><span><?= money($p['custo_mao_obra']) ?></span></div>
        <div class="breakdown-line"><span>Custos fixos (rateio)</span><span><?= money($p['custo_fixo_rateado']) ?></span></div>
        <div class="breakdown-line"><span>Embalagem</span><span><?= money($p['custo_embalagem']) ?></span></div>
        <div class="breakdown-line total"><span>Custo total (lote)</span><span><?= money($p['custo_total']) ?></span></div>

        <div class="price-highlight" style="margin-top:18px">
            <div class="label">Preço sugerido por unidade</div>
            <div class="value"><?= money($p['preco_unitario']) ?></div>
            <div class="sub">Lote completo: <?= money($p['preco_total']) ?></div>
        </div>

        <div class="breakdown-line" style="margin-top:16px"><span>Lucro líquido estimado (lote)</span><span><strong><?= money($p['lucro_liquido']) ?></strong></span></div>
    </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
