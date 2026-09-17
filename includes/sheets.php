<?php
require_once __DIR__ . '/env.php';

class SheetsException extends Exception {}

function gs_spreadsheet_id(): string
{
    return env('GOOGLE_SHEETS_ID', '1aD5hs7l8yF8obKnAMjgETrIbhVgDAywH7FBTvvFGlYA');
}

function gs_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function gs_service_account(): array
{
    $b64 = env('GOOGLE_SERVICE_ACCOUNT_B64', '');
    if ($b64 === '') {
        throw new SheetsException('Configure GOOGLE_SERVICE_ACCOUNT_B64 com a chave da conta de serviço do Google (veja o README).');
    }

    $json = base64_decode($b64, true);
    $data = $json !== false ? json_decode($json, true) : null;

    if (!$data || empty($data['client_email']) || empty($data['private_key'])) {
        throw new SheetsException('GOOGLE_SERVICE_ACCOUNT_B64 inválido: não foi possível ler client_email/private_key.');
    }

    return $data;
}

function gs_access_token(): string
{
    static $cached = null;
    if ($cached !== null && $cached['exp'] > time() + 30) {
        return $cached['token'];
    }

    $account = gs_service_account();
    $now = time();

    $segments = [
        gs_base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
        gs_base64url_encode(json_encode([
            'iss' => $account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ])),
    ];
    $signingInput = implode('.', $segments);

    $signature = '';
    $ok = openssl_sign($signingInput, $signature, $account['private_key'], 'sha256WithRSAEncryption');
    if (!$ok) {
        throw new SheetsException('Não foi possível assinar o JWT com a chave privada do Google. Verifique GOOGLE_SERVICE_ACCOUNT_B64.');
    }
    $segments[] = gs_base64url_encode($signature);
    $jwt = implode('.', $segments);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new SheetsException('Falha ao autenticar com o Google: ' . $err);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($status >= 400 || empty($decoded['access_token'])) {
        $msg = $decoded['error_description'] ?? $decoded['error'] ?? $response;
        throw new SheetsException('Falha ao autenticar com o Google: ' . $msg);
    }

    $cached = ['token' => $decoded['access_token'], 'exp' => $now + (int) ($decoded['expires_in'] ?? 3600)];
    return $cached['token'];
}

function gs_request(string $method, string $path, ?array $body = null)
{
    $token = gs_access_token();
    $url = 'https://sheets.googleapis.com/v4/spreadsheets/' . gs_spreadsheet_id() . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new SheetsException('Erro de conexão com o Google Sheets: ' . $err);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = $response !== '' ? json_decode($response, true) : null;
    if ($status >= 400) {
        $message = $decoded['error']['message'] ?? ('Erro HTTP ' . $status);
        throw new SheetsException($message);
    }

    return $decoded;
}

function gs_values_get(string $range): array
{
    try {
        $result = gs_request('GET', '/values/' . rawurlencode($range));
    } catch (SheetsException $e) {
        if (str_contains($e->getMessage(), 'Unable to parse range')) {
            return [];
        }
        throw $e;
    }
    return $result['values'] ?? [];
}

/** Faz o append e devolve o número da linha (1-based) onde os dados caíram. */
function gs_values_append(string $range, array $row): int
{
    $result = gs_request(
        'POST',
        '/values/' . rawurlencode($range) . ':append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS',
        ['values' => [$row]]
    );
    $updatedRange = $result['updates']['updatedRange'] ?? '';
    if (preg_match('/![A-Z]+(\d+):/', $updatedRange, $m)) {
        return (int) $m[1];
    }
    return -1;
}

function gs_values_update(string $range, array $row): void
{
    gs_request('PUT', '/values/' . rawurlencode($range) . '?valueInputOption=USER_ENTERED', [
        'values' => [$row],
    ]);
}

function gs_sheet_titles(): array
{
    $meta = gs_request('GET', '?fields=' . rawurlencode('sheets.properties'));
    $titles = [];
    foreach ($meta['sheets'] ?? [] as $sheet) {
        $titles[$sheet['properties']['title']] = $sheet['properties']['sheetId'];
    }
    return $titles;
}

function gs_delete_row(string $sheetName, int $rowNumberOneBased): void
{
    $titles = gs_sheet_titles();
    if (!isset($titles[$sheetName])) {
        throw new SheetsException("Aba \"$sheetName\" não encontrada na planilha.");
    }
    gs_request('POST', ':batchUpdate', [
        'requests' => [[
            'deleteDimension' => [
                'range' => [
                    'sheetId' => $titles[$sheetName],
                    'dimension' => 'ROWS',
                    'startIndex' => $rowNumberOneBased - 1,
                    'endIndex' => $rowNumberOneBased,
                ],
            ],
        ]],
    ]);
}

/** Estrutura das abas que o sistema espera. Chamado no topo de cada página que usa a planilha. */
function gs_bootstrap(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $needed = [
        'Configuracoes' => ['SalarioHora', 'CustosFixosMensais', 'ProducaoMensalHoras', 'TaxaCartao', 'Imposto', 'MargemPadrao'],
        'Ingredientes' => ['Nome', 'Unidade', 'PrecoEmbalagem', 'QuantidadeEmbalagem', 'CustoUnitario'],
        'Produtos' => [
            'DataHora', 'Produto', 'Categoria', 'Rendimento', 'UnidadeRendimento', 'TempoPreparoMin',
            'IngredientesDetalhe', 'CustoIngredientes', 'CustoMaoObra', 'CustoFixoRateado', 'CustoEmbalagem',
            'CustoTotal', 'Margem', 'TaxaCartao', 'Imposto', 'PrecoUnitario', 'PrecoTotal', 'LucroLiquido',
        ],
    ];

    $existing = gs_sheet_titles();

    $toCreate = [];
    foreach ($needed as $name => $headers) {
        if (!isset($existing[$name])) {
            $toCreate[] = ['addSheet' => ['properties' => ['title' => $name]]];
        }
    }

    if (empty($toCreate)) {
        return;
    }

    gs_request('POST', ':batchUpdate', ['requests' => $toCreate]);

    foreach ($needed as $name => $headers) {
        if (!isset($existing[$name])) {
            gs_values_update($name . '!A1', $headers);
        }
    }
}
