<?php
require_once __DIR__ . '/includes/env.php';
require_once __DIR__ . '/includes/auth.php';
auth_require();
require_once __DIR__ . '/includes/repo.php';
require_once __DIR__ . '/includes/helpers.php';

$erro = null;
$sucesso = null;
$config = CONFIG_PADRAO;

try {
    $config = repo_ler_config();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $config = [
            'salario_hora' => input_float($_POST['salario_hora'] ?? null),
            'custos_fixos_mensais' => input_float($_POST['custos_fixos_mensais'] ?? null),
            'producao_mensal_horas' => input_float($_POST['producao_mensal_horas'] ?? null),
            'percentual_taxa_cartao' => input_float($_POST['percentual_taxa_cartao'] ?? null),
            'percentual_imposto' => input_float($_POST['percentual_imposto'] ?? null),
            'margem_lucro_padrao' => input_float($_POST['margem_lucro_padrao'] ?? null),
        ];
        gs_bootstrap();
        repo_salvar_config($config);
        $sucesso = 'Configurações salvas na planilha com sucesso!';
    }
} catch (SheetsException $e) {
    $erro = $e->getMessage();
}

$custoHoraFixo = ((float) $config['producao_mensal_horas']) > 0
    ? ((float) $config['custos_fixos_mensais']) / ((float) $config['producao_mensal_horas'])
    : 0;

$pageTitle = 'Configurações';
$activeNav = 'configuracoes';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="topbar">
    <div>
        <h1>Configurações</h1>
        <p>Esses valores são usados para calcular o custo e o preço sugerido de todos os produtos. Ficam salvos na aba "Configuracoes" da sua planilha.</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert error"><?= icon('warning') ?><span>Erro: <?= h($erro) ?></span></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert success"><?= icon('check-circle') ?><span><?= h($sucesso) ?></span></div>
<?php endif; ?>

<form method="post">
    <div class="grid cols-2">
        <div class="card">
            <h3>Mão de obra e custos fixos</h3>
            <div class="field">
                <label for="salario_hora">Valor da sua hora de trabalho</label>
                <input type="number" step="0.01" min="0" id="salario_hora" name="salario_hora" value="<?= h((string) $config['salario_hora']) ?>" required>
                <div class="hint">Quanto você quer ganhar por hora de produção.</div>
            </div>
            <div class="field">
                <label for="custos_fixos_mensais">Custos fixos mensais</label>
                <input type="number" step="0.01" min="0" id="custos_fixos_mensais" name="custos_fixos_mensais" value="<?= h((string) $config['custos_fixos_mensais']) ?>" required>
                <div class="hint">Aluguel, luz, água, gás, internet, embalagens fixas, etc.</div>
            </div>
            <div class="field">
                <label for="producao_mensal_horas">Horas de produção por mês</label>
                <input type="number" step="0.01" min="0.01" id="producao_mensal_horas" name="producao_mensal_horas" value="<?= h((string) $config['producao_mensal_horas']) ?>" required>
                <div class="hint">Quantas horas por mês você efetivamente produz.</div>
            </div>
            <div class="field" style="margin-bottom:0">
                <div class="hint">Rateio de custo fixo calculado: <strong><?= money($custoHoraFixo) ?></strong> por hora de produção.</div>
            </div>
        </div>

        <div class="card">
            <h3>Margem, taxas e impostos</h3>
            <div class="field">
                <label for="margem_lucro_padrao">Margem de lucro padrão (%)</label>
                <input type="number" step="0.01" min="0" max="99" id="margem_lucro_padrao" name="margem_lucro_padrao" value="<?= h((string) $config['margem_lucro_padrao']) ?>" required>
                <div class="hint">Pode ser sobrescrita em cada produto.</div>
            </div>
            <div class="field">
                <label for="percentual_taxa_cartao">Taxa de cartão/maquininha (%)</label>
                <input type="number" step="0.01" min="0" max="99" id="percentual_taxa_cartao" name="percentual_taxa_cartao" value="<?= h((string) $config['percentual_taxa_cartao']) ?>" required>
            </div>
            <div class="field" style="margin-bottom:0">
                <label for="percentual_imposto">Imposto (%)</label>
                <input type="number" step="0.01" min="0" max="99" id="percentual_imposto" name="percentual_imposto" value="<?= h((string) $config['percentual_imposto']) ?>" required>
            </div>
        </div>
    </div>

    <button class="btn" type="submit">Salvar configurações</button>
</form>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
