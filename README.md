# Zabeths — Sistema de Precificação

Sistema simples para precificar produtos de confeitaria: cadastra ingredientes com preço de embalagem (o custo por grama/ml/unidade é calculado sozinho), monta receitas e calcula o custo total e o preço de venda sugerido considerando mão de obra, custos fixos, embalagem, margem de lucro, taxa de cartão e imposto.

**Não usa nenhum banco de dados** — tudo é lido e salvo direto na sua [Google Planilha](https://docs.google.com/spreadsheets/d/1aD5hs7l8yF8obKnAMjgETrIbhVgDAywH7FBTvvFGlYA/edit). O sistema cria sozinho, na primeira vez que rodar, três abas na planilha: `Configuracoes`, `Ingredientes` e `Produtos` (histórico de precificações salvas).

## Como funciona o cálculo

```
Custo total = ingredientes + mão de obra + rateio de custos fixos + embalagem
Preço de venda = Custo total / (1 - (margem% + taxa de cartão% + imposto%) / 100)
```

- **Mão de obra**: tempo de preparo (min) × valor da sua hora.
- **Rateio de custos fixos**: (custos fixos mensais ÷ horas de produção no mês) × tempo de preparo (em horas).
- Tudo isso é configurado em **Configurações** e pode ser ajustado por produto (margem de lucro).
- Ao clicar em **"💾 Salvar na planilha"**, o resultado completo daquela precificação vira uma nova linha na aba `Produtos` — funciona como um histórico, então repricificar o mesmo produto no futuro não sobrescreve o registro anterior.

## 1. Criar a conta de serviço do Google (uma vez só)

O app acessa sua planilha via a API do Google Sheets, autenticando como uma "conta de serviço" (sem precisar de login/senha do Google a cada acesso).

1. Acesse o [Google Cloud Console](https://console.cloud.google.com/) e crie um projeto (ou use um existente).
2. Em **APIs e Serviços > Biblioteca**, ative a **Google Sheets API**.
3. Em **APIs e Serviços > Credenciais > Criar credenciais > Conta de serviço**, crie uma conta de serviço (pode dar qualquer nome, ex: `zabeths-app`).
4. Abra a conta de serviço criada > aba **Chaves > Adicionar chave > Criar nova chave > JSON**. Isso baixa um arquivo `.json` — guarde-o com cuidado, ele dá acesso à planilha.
5. Copie o **e-mail** da conta de serviço (algo como `zabeths-app@SEU-PROJETO.iam.gserviceaccount.com`).
6. Abra sua [planilha](https://docs.google.com/spreadsheets/d/1aD5hs7l8yF8obKnAMjgETrIbhVgDAywH7FBTvvFGlYA/edit), clique em **Compartilhar** e adicione esse e-mail como **Editor**.

## 2. Converter a chave JSON para uma variável de ambiente

O sistema espera a chave inteira em uma variável `GOOGLE_SERVICE_ACCOUNT_B64` (o JSON convertido para base64, em uma linha só — evita problema com quebras de linha em variáveis de ambiente).

**No PowerShell (Windows):**
```powershell
[Convert]::ToBase64String([IO.File]::ReadAllBytes("C:\caminho\para\sua-chave.json")) | Set-Clipboard
```
Isso já copia o valor para a área de transferência — é só colar.

**No macOS/Linux:**
```bash
base64 -i sua-chave.json | tr -d '\n' | pbcopy   # macOS (copia para a área de transferência)
base64 -w0 sua-chave.json                        # Linux (imprime no terminal)
```

## 3. Rodar localmente

Requer PHP 8+ com as extensões `curl` e `openssl` (vêm habilitadas por padrão na maioria das instalações).

```bash
cp .env.example .env
# edite o .env: cole o valor em GOOGLE_SERVICE_ACCOUNT_B64 e ajuste GOOGLE_SHEETS_ID se usar outra planilha
php -S localhost:8000
```

Acesse `http://localhost:8000`. Na primeira vez que abrir uma página que usa a planilha, o sistema cria as abas `Configuracoes`, `Ingredientes` e `Produtos` automaticamente.

## 4. Deploy na Vercel

1. Suba este projeto para um repositório Git (GitHub/GitLab/Bitbucket).
2. Na Vercel, importe o repositório (o `vercel.json` já configura o runtime PHP).
3. Em **Settings > Environment Variables**, adicione:
   - `GOOGLE_SHEETS_ID` — o ID da planilha (já vem preenchido por padrão com a sua, mas pode sobrescrever)
   - `GOOGLE_SERVICE_ACCOUNT_B64` — o valor gerado no passo 2
   - `APP_PASSWORD` — senha de acesso ao sistema
   - `APP_SECRET` — qualquer texto aleatório longo, usado para assinar o cookie de login
4. Deploy.

## Estrutura

- `index.php` — dashboard com o histórico recente
- `ingredientes.php`, `ingrediente_form.php` — cadastro de ingredientes (aba `Ingredientes`)
- `produto_form.php` — monta a receita e calcula o preço em tempo real; botão salva na aba `Produtos`
- `produtos.php`, `produto_view.php` — histórico de precificações salvas
- `configuracoes.php` — mão de obra, custos fixos, margem, taxas (aba `Configuracoes`)
- `includes/sheets.php` — cliente da API do Google Sheets (autenticação JWT + REST, sem dependências externas)
- `includes/repo.php` — leitura/escrita das abas Configuracoes/Ingredientes/Produtos
- `includes/helpers.php` — fórmula de precificação

## Segurança

- A chave da conta de serviço (`GOOGLE_SERVICE_ACCOUNT_B64`) só é usada no servidor PHP, nunca é enviada ao navegador.
- Defina `APP_PASSWORD` para proteger o acesso ao sistema (sem isso, qualquer pessoa com o link consegue ver e editar seus dados).
- O arquivo `.env` está no `.gitignore` — nunca o suba para o Git.
