<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$erro = null;
$historico = [];

try {
    $historico = repo_ler_produtos();
} catch (SheetsException $e) {
    $erro = $e->getMessage();
}

$pageTitle = 'Produtos';
$activeNav = 'produtos';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <h1>Histórico de precificações</h1>
        <p>Cada linha é um cálculo salvo na aba "Produtos" da sua planilha. Reprecificar um produto cria um novo registro, então você acompanha a evolução do preço ao longo do tempo.</p>
    </div>
    <a class="btn" href="produto_form.php"><?= icon('plus', 16) ?> Nova precificação</a>
</div>

<?php if ($erro): ?>
    <div class="alert error"><?= icon('warning') ?><span>Não foi possível carregar o histórico: <?= h($erro) ?></span></div>
<?php elseif (isset($_GET['excluido'])): ?>
    <div class="alert success"><?= icon('check-circle') ?><span>Registro excluído com sucesso.</span></div>
<?php endif; ?>

<?php if (!$erro): ?>
<div class="card">
    <?php if (count($historico) === 0): ?>
        <div class="empty-state">
            <?= icon('cupcake', 48) ?>
            <p>Nenhuma precificação salva ainda.</p>
            <a class="btn" href="produto_form.php">Fazer a primeira precificação</a>
        </div>
    <?php else: ?>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Produto</th>
                <th>Rendimento</th>
                <th>Custo total</th>
                <th>Preço/unid.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($historico as $p): ?>
            <tr>
                <td><?= h($p['data_hora']) ?></td>
                <td><strong><?= h($p['nome']) ?></strong><?php if ($p['categoria']): ?><br><span class="badge"><?= h($p['categoria']) ?></span><?php endif; ?></td>
                <td><?= num($p['rendimento_qtd'], 0) ?> <?= h($p['rendimento_unidade']) ?></td>
                <td><?= money($p['custo_total']) ?></td>
                <td><strong><?= money($p['preco_unitario']) ?></strong></td>
                <td class="actions-cell">
                    <a class="btn small secondary" href="produto_view.php?linha=<?= (int) $p['linha'] ?>"><?= icon('eye', 15) ?> Ver</a>
                    <form method="post" action="produto_delete.php" onsubmit="return confirm('Excluir este registro do histórico?');">
                        <input type="hidden" name="linha" value="<?= (int) $p['linha'] ?>">
                        <button class="btn small danger" type="submit"><?= icon('trash', 15) ?> Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
