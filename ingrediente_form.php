<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$linha = isset($_GET['linha']) ? (int) $_GET['linha'] : null;
$erro = null;
$ingrediente = [
    'nome' => '',
    'unidade' => 'g',
    'preco_pacote' => '',
    'quantidade_pacote' => '',
];

try {
    if ($linha) {
        $existente = repo_buscar_ingrediente($linha);
        if ($existente) {
            $ingrediente = $existente;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'unidade' => $_POST['unidade'] ?? 'g',
            'preco_pacote' => input_float($_POST['preco_pacote'] ?? null),
            'quantidade_pacote' => input_float($_POST['quantidade_pacote'] ?? null),
        ];

        if ($dados['nome'] === '') {
            $erro = 'Informe o nome do ingrediente.';
        } elseif ($dados['quantidade_pacote'] <= 0) {
            $erro = 'A quantidade da embalagem deve ser maior que zero.';
        } else {
            gs_bootstrap();
            repo_salvar_ingrediente($linha, $dados);
            header('Location: ingredientes.php');
            exit;
        }
        $ingrediente = array_merge($ingrediente, $dados);
    }
} catch (SheetsException $e) {
    $erro = $e->getMessage();
}

$unidades = ['g', 'kg', 'ml', 'L', 'unidade'];

$pageTitle = $linha ? 'Editar ingrediente' : 'Novo ingrediente';
$activeNav = 'ingredientes';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <h1><?= $linha ? 'Editar ingrediente' : 'Novo ingrediente' ?></h1>
        <p>O custo unitário é calculado automaticamente: preço da embalagem ÷ quantidade.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert error"><?= icon('warning') ?><span><?= h($erro) ?></span></div>
<?php endif; ?>

<div class="card" style="max-width:560px">
    <form method="post">
        <div class="field">
            <label for="nome">Nome do ingrediente</label>
            <input type="text" id="nome" name="nome" value="<?= h($ingrediente['nome']) ?>" placeholder="Ex: Leite condensado" required autofocus>
        </div>

        <div class="row-inline">
            <div class="field">
                <label for="preco_pacote">Preço pago na embalagem</label>
                <input type="number" step="0.01" min="0" id="preco_pacote" name="preco_pacote" value="<?= h((string) $ingrediente['preco_pacote']) ?>" required>
            </div>
            <div class="field">
                <label for="quantidade_pacote">Quantidade na embalagem</label>
                <input type="number" step="0.001" min="0.001" id="quantidade_pacote" name="quantidade_pacote" value="<?= h((string) $ingrediente['quantidade_pacote']) ?>" required>
            </div>
            <div class="field">
                <label for="unidade">Unidade</label>
                <select id="unidade" name="unidade">
                    <?php foreach ($unidades as $u): ?>
                        <option value="<?= h($u) ?>" <?= $ingrediente['unidade'] === $u ? 'selected' : '' ?>><?= h($u) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="hint" style="margin-bottom:18px">Dica: cadastre a quantidade na mesma unidade que você vai usar nas receitas (ex: pacote de 395g de leite condensado → unidade "g", quantidade 395).</div>

        <button class="btn" type="submit">Salvar</button>
        <a class="btn secondary" href="ingredientes.php">Cancelar</a>
    </form>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
