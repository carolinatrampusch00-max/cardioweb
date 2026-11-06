# Instruções do Projeto - CardioWeb

## Visão Geral
Este projeto é uma aplicação web em PHP para o sistema CardioWeb, com foco em autenticação, painel do usuário, consultas, exames e uma interface de suporte. A aplicação está em desenvolvimento e mistura páginas PHP tradicionais, arquivos de processamento, arquivos de API e uma estrutura de dashboard.

## Stack e Estrutura
- Linguagem: PHP
- Banco: MySQL via PDO
- Sessões: PHP Session
- Front-end: HTML, CSS, JavaScript inline
- Arquivos principais:
  - index.php: página principal de acesso e login
  - auth-process.php: processamento centralizado de autenticação
  - dashboard.php: painel principal após login
  - api/: endpoints para consultas e dados administrativos
  - dashboard-sections/: conteúdo dinâmico do dashboard
  - admin/: área administrativa
  - config/: configuração do banco e ambiente

## Fluxo Principal do Sistema
1. O usuário acessa index.php
2. O login é processado em auth-process.php
3. Em caso de sucesso, a sessão é criada e o usuário é redirecionado para dashboard.php
4. O dashboard carrega conteúdo por página via JavaScript e arquivos PHP auxiliares

## Regras de Desenvolvimento
- Preserve o fluxo de autenticação e sessão.
- Mantenha a linguagem em português, quando possível.
- Use prepared statements sempre que trabalhar com banco de dados.
- Evite quebrar o fluxo atual de login, logout e redirecionamento.
- Prefira alterações pequenas, seguras e compatíveis com o código atual.
- Não remova arquivos legados sem garantir que não haja dependência ativa.
- Ao criar novas telas ou módulos, siga o padrão visual já usado no dashboard.
- Priorize segurança, organização e manutenção.

## Pontos Importantes do Projeto
- O sistema de login já foi consolidado, mas ainda precisa de melhorias reais de segurança e integração.
- O dashboard já possui navegação e conteúdo estático/dinâmico, mas pode ser refatorado para ficar mais modular.
- A área administrativa está separada e pode receber melhorias de usabilidade e validação.
- A API de consultas já existe, mas é importante validar erros e melhorar o tratamento de dados.

## Prioridades do Projeto

### 1. Melhorias Prioritárias
- Melhorar a organização do código para reduzir duplicação e facilitar manutenção.
- Padronizar o uso de funções reutilizáveis para conexão, validação e renderização de conteúdo.
- Melhorar a experiência do usuário no dashboard e no fluxo de login.
- Refinar o design responsivo para mobile e tablets.
- Organizar melhor os arquivos e pastas para deixar o projeto mais profissional.

### 2. Correções Prioritárias
- Corrigir pontos de segurança no processamento de login e sessões.
- Implementar validação real de usuário e senha contra o banco de dados.
- Tratar corretamente erros de formulário, API e conexão com o banco.
- Corrigir inconsistências entre arquivos antigos e novos do sistema de login.
- Revisar e remover trechos de código duplicado ou obsoletos.

### 3. Criações Prioritárias
- Criar um painel administrativo mais completo e funcional.
- Criar uma área de perfil do usuário com edição de dados e senha.
- Criar um módulo de histórico de consultas e exames com leitura real do banco.
- Criar notificações e lembretes para consultas e exames.
- Criar uma API mais robusta para integração com frontend e administração.

## Ordem Recomendada de Trabalho
1. Segurança e autenticação
2. Correção de fluxo principal do sistema
3. Organização estrutural do projeto
4. Melhorias de UX/UI
5. Novos módulos e integrações

## Observação Final
Sempre que fizer alterações, preserve o funcionamento do fluxo principal do projeto e priorize melhorias que aumentem segurança, confiabilidade e escalabilidade.
