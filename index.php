<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$erro = null;
$historico = [];
$totalIngredientes = 0;

try {
    $historico = repo_ler_produtos();
    $totalIngredientes = count(repo_ler_ingredientes());
} catch (SheetsException $e) {
    $erro = $e->getMessage();
}

$totalProdutos = count($historico);
$recentes = array_slice($historico, 0, 10);
$margemMedia = $totalProdutos > 0
    ? array_sum(array_map(fn($p) => $p['margem'], $historico)) / $totalProdutos
    : 0;
$lucroTotalEstimado = array_sum(array_map(fn($p) => $p['lucro_liquido'], $historico));

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <h1>Olá! 👋</h1>
        <p>Aqui está um resumo da precificação da Zabeths.</p>
    </div>
    <a class="btn" href="produto_form.php">+ Nova precificação</a>
</div>

<?php if ($erro): ?>
    <div class="alert error">Não foi possível carregar os dados da planilha: <?= h($erro) ?><br>
    Verifique <code>GOOGLE_SHEETS_ID</code> e <code>GOOGLE_SERVICE_ACCOUNT_B64</code>, e se a planilha foi compartilhada com o e-mail da conta de serviço (veja o README).</div>
<?php else: ?>

<div class="grid cols-4">
    <div class="stat">
        <div class="label">Precificações salvas</div>
        <div class="value"><?= $totalProdutos ?></div>
    </div>
    <div class="stat">
        <div class="label">Ingredientes cadastrados</div>
        <div class="value"><?= $totalIngredientes ?></div>
    </div>
    <div class="stat">
        <div class="label">Margem média aplicada</div>
        <div class="value"><?= num($margemMedia, 1) ?>%</div>
    </div>
    <div class="stat">
        <div class="label">Lucro líquido estimado (total)</div>
        <div class="value"><?= money($lucroTotalEstimado) ?></div>
    </div>
</div>

<div class="card">
    <h2>Últimas precificações salvas</h2>
    <?php if ($totalProdutos === 0): ?>
        <div class="empty-state">
            <div class="icon">🧁</div>
            <p>Você ainda não salvou nenhuma precificação na planilha.</p>
            <a class="btn" href="produto_form.php">Fazer a primeira precificação</a>
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Produto</th>
                <th>Rendimento</th>
                <th>Custo total</th>
                <th>Preço sugerido (unidade)</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recentes as $p): ?>
            <tr>
                <td><?= h($p['data_hora']) ?></td>
                <td><strong><?= h($p['nome']) ?></strong><?php if ($p['categoria']): ?><br><span class="badge"><?= h($p['categoria']) ?></span><?php endif; ?></td>
                <td><?= num($p['rendimento_qtd'], 0) ?> <?= h($p['rendimento_unidade']) ?></td>
                <td><?= money($p['custo_total']) ?></td>
                <td><strong><?= money($p['preco_unitario']) ?></strong></td>
                <td><a class="btn small secondary" href="produto_view.php?linha=<?= (int) $p['linha'] ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="hint"><a href="produtos.php">Ver histórico completo →</a></p>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
