<?php
require_once __DIR__ . '/sheets.php';

const CONFIG_PADRAO = [
    'salario_hora' => 15,
    'custos_fixos_mensais' => 800,
    'producao_mensal_horas' => 160,
    'percentual_taxa_cartao' => 4.99,
    'percentual_imposto' => 6,
    'margem_lucro_padrao' => 40,
];

function repo_ler_config(): array
{
    $linhas = gs_values_get('Configuracoes!A2:F2');
    if (empty($linhas)) {
        return CONFIG_PADRAO;
    }
    $l = $linhas[0];
    return [
        'salario_hora' => (float) ($l[0] ?? 0),
        'custos_fixos_mensais' => (float) ($l[1] ?? 0),
        'producao_mensal_horas' => (float) ($l[2] ?? 1),
        'percentual_taxa_cartao' => (float) ($l[3] ?? 0),
        'percentual_imposto' => (float) ($l[4] ?? 0),
        'margem_lucro_padrao' => (float) ($l[5] ?? 0),
    ];
}

function repo_salvar_config(array $c): void
{
    gs_values_update('Configuracoes!A2', [
        $c['salario_hora'],
        $c['custos_fixos_mensais'],
        $c['producao_mensal_horas'],
        $c['percentual_taxa_cartao'],
        $c['percentual_imposto'],
        $c['margem_lucro_padrao'],
    ]);
}

/** @return array<int, array{linha:int,nome:string,unidade:string,preco_pacote:float,quantidade_pacote:float,custo_unitario:float}> */
function repo_ler_ingredientes(): array
{
    $linhas = gs_values_get('Ingredientes!A2:E');
    $out = [];
    foreach ($linhas as $i => $l) {
        if (($l[0] ?? '') === '') {
            continue;
        }
        $precoPacote = (float) ($l[2] ?? 0);
        $qtdPacote = (float) ($l[3] ?? 0);
        $out[] = [
            'linha' => $i + 2,
            'nome' => $l[0] ?? '',
            'unidade' => $l[1] ?? 'g',
            'preco_pacote' => $precoPacote,
            'quantidade_pacote' => $qtdPacote,
            'custo_unitario' => $qtdPacote > 0 ? $precoPacote / $qtdPacote : 0,
        ];
    }
    usort($out, fn($a, $b) => strcasecmp($a['nome'], $b['nome']));
    return $out;
}

function repo_buscar_ingrediente(int $linha): ?array
{
    foreach (repo_ler_ingredientes() as $ing) {
        if ($ing['linha'] === $linha) {
            return $ing;
        }
    }
    return null;
}

function repo_salvar_ingrediente(?int $linha, array $dados): void
{
    $custoUnitario = $dados['quantidade_pacote'] > 0 ? $dados['preco_pacote'] / $dados['quantidade_pacote'] : 0;
    $linhaValores = [
        $dados['nome'],
        $dados['unidade'],
        $dados['preco_pacote'],
        $dados['quantidade_pacote'],
        $custoUnitario,
    ];

    if ($linha) {
        gs_values_update('Ingredientes!A' . $linha, $linhaValores);
    } else {
        gs_values_append('Ingredientes!A:E', $linhaValores);
    }
}

function repo_excluir_ingrediente(int $linha): void
{
    gs_delete_row('Ingredientes', $linha);
}

/** @return array<int, array> histórico de precificações salvas, mais recentes primeiro */
function repo_ler_produtos(): array
{
    $linhas = gs_values_get('Produtos!A2:R');
    $out = [];
    foreach ($linhas as $i => $l) {
        if (($l[1] ?? '') === '') {
            continue;
        }
        $out[] = repo_mapear_produto($i + 2, $l);
    }
    usort($out, fn($a, $b) => $b['linha'] <=> $a['linha']);
    return $out;
}

function repo_buscar_produto(int $linha): ?array
{
    $l = gs_values_get('Produtos!A' . $linha . ':R' . $linha);
    if (empty($l)) {
        return null;
    }
    return repo_mapear_produto($linha, $l[0]);
}

function repo_mapear_produto(int $linha, array $l): array
{
    return [
        'linha' => $linha,
        'data_hora' => $l[0] ?? '',
        'nome' => $l[1] ?? '',
        'categoria' => $l[2] ?? '',
        'rendimento_qtd' => (float) ($l[3] ?? 1),
        'rendimento_unidade' => $l[4] ?? 'unidade(s)',
        'tempo_preparo_minutos' => (float) ($l[5] ?? 0),
        'ingredientes_detalhe' => $l[6] ?? '',
        'custo_ingredientes' => (float) ($l[7] ?? 0),
        'custo_mao_obra' => (float) ($l[8] ?? 0),
        'custo_fixo_rateado' => (float) ($l[9] ?? 0),
        'custo_embalagem' => (float) ($l[10] ?? 0),
        'custo_total' => (float) ($l[11] ?? 0),
        'margem' => (float) ($l[12] ?? 0),
        'taxa_cartao' => (float) ($l[13] ?? 0),
        'imposto' => (float) ($l[14] ?? 0),
        'preco_unitario' => (float) ($l[15] ?? 0),
        'preco_total' => (float) ($l[16] ?? 0),
        'lucro_liquido' => (float) ($l[17] ?? 0),
    ];
}

/** Salva o resultado final de uma precificação como uma nova linha no histórico. Retorna o número da linha criada. */
function repo_salvar_produto(array $produto, array $itens, array $calculo): int
{
    $detalhe = implode('; ', array_map(
        fn($it) => $it['nome'] . ' (' . num($it['quantidade'], 3) . ' ' . $it['unidade'] . ')',
        $itens
    ));

    return gs_values_append('Produtos!A:R', [
        date('d/m/Y H:i'),
        $produto['nome'],
        $produto['categoria'] ?? '',
        $produto['rendimento_qtd'],
        $produto['rendimento_unidade'],
        $produto['tempo_preparo_minutos'],
        $detalhe,
        round($calculo['custo_ingredientes'], 2),
        round($calculo['custo_mao_obra'], 2),
        round($calculo['custo_fixo_rateado'], 2),
        round($calculo['custo_embalagem'], 2),
        round($calculo['custo_direto_total'], 2),
        $calculo['margem'],
        $calculo['taxa_cartao'],
        $calculo['imposto'],
        round($calculo['preco_venda_unitario'], 2),
        round($calculo['preco_venda_total'], 2),
        round($calculo['lucro_liquido'], 2),
    ]);
}

function repo_excluir_produto(int $linha): void
{
    gs_delete_row('Produtos', $linha);
}
