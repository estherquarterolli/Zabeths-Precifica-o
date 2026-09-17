<?php

function money(?float $value): string
{
    $value = $value ?? 0;
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function num(?float $value, int $decimals = 2): string
{
    $value = $value ?? 0;
    return number_format($value, $decimals, ',', '.');
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function input_float($value, float $default = 0): float
{
    if ($value === null || $value === '') {
        return $default;
    }
    // aceita tanto "12.5" quanto "12,5"
    $value = str_replace(',', '.', (string) $value);
    return (float) $value;
}

/**
 * Calcula o custo e o preço sugerido de um produto.
 *
 * $produto: dados do produto (nome, tempo_preparo_minutos, custo_embalagem, rendimento_qtd, margem_lucro)
 * $itens: lista de ingredientes usados na receita, cada um ['nome', 'unidade', 'custo_unitario', 'quantidade']
 * $config: configurações (salario_hora, custos_fixos_mensais, producao_mensal_horas, percentual_taxa_cartao, percentual_imposto, margem_lucro_padrao)
 */
function calcular_precificacao(array $produto, array $itens, array $config): array
{
    $custoIngredientes = 0.0;
    foreach ($itens as $item) {
        $custoUnitario = (float) ($item['custo_unitario'] ?? 0);
        $quantidade = (float) ($item['quantidade'] ?? 0);
        $custoIngredientes += $custoUnitario * $quantidade;
    }

    $tempoHoras = ((float) ($produto['tempo_preparo_minutos'] ?? 0)) / 60;
    $salarioHora = (float) ($config['salario_hora'] ?? 0);
    $custoMaoObra = $tempoHoras * $salarioHora;

    $custoFixoHora = 0.0;
    $producaoMensalHoras = (float) ($config['producao_mensal_horas'] ?? 0);
    if ($producaoMensalHoras > 0) {
        $custoFixoHora = ((float) ($config['custos_fixos_mensais'] ?? 0)) / $producaoMensalHoras;
    }
    $custoFixoRateado = $custoFixoHora * $tempoHoras;

    $custoEmbalagem = (float) ($produto['custo_embalagem'] ?? 0);

    $custoDiretoTotal = $custoIngredientes + $custoMaoObra + $custoFixoRateado + $custoEmbalagem;

    $margem = $produto['margem_lucro'] !== null && $produto['margem_lucro'] !== ''
        ? (float) $produto['margem_lucro']
        : (float) ($config['margem_lucro_padrao'] ?? 0);
    $taxaCartao = (float) ($config['percentual_taxa_cartao'] ?? 0);
    $imposto = (float) ($config['percentual_imposto'] ?? 0);

    $percentualTotal = $margem + $taxaCartao + $imposto;
    $divisor = 1 - ($percentualTotal / 100);

    $alerta = null;
    if ($divisor <= 0) {
        $alerta = 'A soma de margem + taxa de cartão + imposto é maior ou igual a 100%. Ajuste os percentuais em Configurações ou no produto.';
        $precoVendaTotal = $custoDiretoTotal; // fallback: sem markup, evita divisão inválida
    } else {
        $precoVendaTotal = $custoDiretoTotal / $divisor;
    }

    $rendimento = (float) ($produto['rendimento_qtd'] ?? 1);
    if ($rendimento <= 0) {
        $rendimento = 1;
    }
    $precoVendaUnitario = $precoVendaTotal / $rendimento;

    $valorTaxasImpostos = $precoVendaTotal * (($taxaCartao + $imposto) / 100);
    $lucroLiquido = $precoVendaTotal - $custoDiretoTotal - $valorTaxasImpostos;

    return [
        'custo_ingredientes' => $custoIngredientes,
        'custo_mao_obra' => $custoMaoObra,
        'custo_fixo_rateado' => $custoFixoRateado,
        'custo_embalagem' => $custoEmbalagem,
        'custo_direto_total' => $custoDiretoTotal,
        'margem' => $margem,
        'taxa_cartao' => $taxaCartao,
        'imposto' => $imposto,
        'preco_venda_total' => $precoVendaTotal,
        'preco_venda_unitario' => $precoVendaUnitario,
        'lucro_liquido' => $lucroLiquido,
        'alerta' => $alerta,
    ];
}

$UNIDADES_MEDIDA = ['g', 'kg', 'ml', 'L', 'unidade'];
