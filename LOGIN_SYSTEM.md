# Autenticação CardioWeb

## Contas e senha

- O modal de cadastro em `index.php` cria contas na tabela `usuarios`; a senha é salva com `password_hash`. `register.php` redireciona para esse modal.
- `index.php` envia o login por e-mail/senha para `process_login.php` e `auth-process.php`.
- Contas novas de Google ou Apple são gravadas em `usuarios` e vinculadas em `auth_identities`. No primeiro acesso, a pessoa define uma senha para também entrar pelo formulário normal.
- Contas criadas pelo primeiro acesso via SMS informam e-mail e senha antes da verificação do telefone; somente após a aprovação do código o cadastro é gravado.

## Demonstração local do TCC

Em `localhost`/loopback, Google abre um seletor com duas contas fictícias (`Ana Silva` e `Lucas Martins`) e cria/vincula os usuários no banco. A senha inicial dessas duas contas para o login normal é `CardioWeb@2026`. Apple abre um formulário demonstrativo para criar ou acessar uma conta fictícia usando e-mail e senha.

Esses fluxos são bloqueados fora de `localhost` e **não autenticam contas Google ou Apple reais**. Em outros ambientes, os botões usam os fluxos OAuth configurados abaixo.

## Configuração dos provedores

Copie os nomes de variáveis de `.env.example` para o `.env` local e preencha com credenciais obtidas nos respectivos painéis. Não publique nem versione o `.env`.

### Google

Crie um cliente OAuth do tipo Web e autorize exatamente a URL de callback configurada em `GOOGLE_REDIRECT_URI`, por exemplo `https://seu-dominio/TCC/oauth.php?provider=google`. Configure o ID e o segredo em `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET`.

### Apple

Configure Sign in with Apple, um Services ID, Team ID, Key ID e a chave `.p8`. O callback precisa ser HTTPS e coincidir exatamente com `APPLE_REDIRECT_URI`. Mantenha a chave privada fora da pasta pública do site.

### SMS / Twilio Verify

Configure `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN` e o SID de um serviço Verify em `TWILIO_VERIFY_SERVICE_SID`. Os telefones devem ser digitados em formato internacional, como `+5511999999999`.

As configurações do `.env` são lidas por requisição. Sem essas credenciais, os provedores reais retornam uma mensagem explícita de configuração pendente. A demonstração local acima só funciona em `localhost`; o cadastro de e-mail/senha continua disponível sem integração externa.
