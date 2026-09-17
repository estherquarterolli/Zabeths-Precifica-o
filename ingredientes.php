<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$erro = null;
$ingredientes = [];

try {
    gs_bootstrap();
    $ingredientes = repo_ler_ingredientes();
} catch (SheetsException $e) {
    $erro = $e->getMessage();
}

$pageTitle = 'Ingredientes';
$activeNav = 'ingredientes';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <h1>Ingredientes</h1>
        <p>Cadastre os ingredientes com o preço da embalagem para calcular o custo unitário automaticamente. Fica salvo na aba "Ingredientes" da sua planilha.</p>
    </div>
    <a class="btn" href="ingrediente_form.php">+ Novo ingrediente</a>
</div>

<?php if ($erro): ?>
    <div class="alert error">Não foi possível carregar os ingredientes: <?= h($erro) ?></div>
<?php elseif (isset($_GET['excluido'])): ?>
    <div class="alert success">Ingrediente excluído com sucesso.</div>
<?php endif; ?>

<div class="card">
    <?php if (!$erro && count($ingredientes) === 0): ?>
        <div class="empty-state">
            <div class="icon">🧺</div>
            <p>Nenhum ingrediente cadastrado ainda.</p>
            <a class="btn" href="ingrediente_form.php">Cadastrar o primeiro ingrediente</a>
        </div>
    <?php elseif (!$erro): ?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Unidade</th>
                <th>Preço da embalagem</th>
                <th>Qtd. da embalagem</th>
                <th>Custo unitário</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($ingredientes as $ing): ?>
            <tr>
                <td><strong><?= h($ing['nome']) ?></strong></td>
                <td><?= h($ing['unidade']) ?></td>
                <td><?= money($ing['preco_pacote']) ?></td>
                <td><?= num($ing['quantidade_pacote'], 3) ?> <?= h($ing['unidade']) ?></td>
                <td><?= money($ing['custo_unitario']) ?> / <?= h($ing['unidade']) ?></td>
                <td class="actions-cell">
                    <a class="btn small secondary" href="ingrediente_form.php?linha=<?= (int) $ing['linha'] ?>">Editar</a>
                    <form method="post" action="ingrediente_delete.php" onsubmit="return confirm('Excluir este ingrediente?');" style="display:inline">
                        <input type="hidden" name="linha" value="<?= (int) $ing['linha'] ?>">
                        <button class="btn small danger" type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
