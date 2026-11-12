-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 11/10/2026 às 21:27
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cardioweb`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `artigos`
--

CREATE TABLE `artigos` (
  `id` int(11) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `resumo` text DEFAULT NULL,
  `conteudo` longtext DEFAULT NULL,
  `imagem` varchar(300) DEFAULT NULL,
  `autor` varchar(150) DEFAULT 'Equipe CardioWeb',
  `publicado` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `artigos`
--

INSERT INTO `artigos` (`id`, `slug`, `titulo`, `resumo`, `conteudo`, `imagem`, `autor`, `publicado`, `criado_em`) VALUES
(1, 'hipertensao', 'Hipertensão arterial: o que ela faz com o corpo', 'A pressão alta aumenta o esforço do coração e pode danificar artérias, rins, cérebro e olhos quando não é controlada.', '<p>A hipertensão arterial é a elevação persistente da pressão do sangue dentro das artérias...</p>', 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=1200', 'Equipe CardioWeb', 1, '2026-10-10 21:28:13'),
(2, 'insuficiencia', 'Insuficiência cardíaca: o que acontece com o coração', 'O coração continua funcionando, mas não consegue suprir a demanda do corpo com a mesma eficiência.', '<p>Insuficiência cardíaca não significa que o coração parou...</p>', 'https://images.unsplash.com/photo-1538108149393-fbbd81895907?w=1200', 'Equipe CardioWeb', 1, '2026-10-10 21:28:13'),
(3, 'arritmia', 'Arritmias: quando o ritmo do coração muda', 'A alteração no ritmo pode causar palpitações, tonturas e sensação de coração acelerado.', '<p>Arritmias são alterações na formação ou na condução do impulso elétrico...</p>', 'https://images.unsplash.com/photo-1516549655169-df83a0774514?w=1200', 'Equipe CardioWeb', 1, '2026-10-10 21:28:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `conversas`
--

CREATE TABLE `conversas` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT 'Nova conversa',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_conversas`
--

CREATE TABLE `historico_conversas` (
  `id` int(11) NOT NULL,
  `id_usuario` varchar(100) NOT NULL,
  `mensagem_usuario` text NOT NULL,
  `resposta_ia` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `id_conversa` int(11) NOT NULL,
  `remetente` enum('user','ia') NOT NULL,
  `conteudo` text NOT NULL,
  `arquivo_path` varchar(300) DEFAULT NULL,
  `arquivo_tipo` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `role` enum('paciente','medico','admin') DEFAULT 'paciente',
  `especialidade` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `telefone`, `role`, `especialidade`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(33, 'Ana Silva', 'ana@email.com', 'ana.1', '(11) 98888-1111', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(34, 'Roberto Mendes', 'roberto@email.com', 'roberto.2', '(11) 98888-2222', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(35, 'Carlos Souza', 'carlos@email.com', 'carlos.3', '(11) 98888-3333', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(36, 'Beatriz Ramos', 'beatriz@email.com', 'beatriz.4', '(11) 98888-4444', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(37, 'Daniela Costa', 'daniela@email.com', 'daniela.5', '(11) 98888-5555', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(38, 'Eduardo Lima', 'eduardo@email.com', 'eduardo.6', '(11) 98888-6666', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(39, 'Fernanda Alves', 'fernanda@email.com', 'fernanda.7', '(11) 98888-7777', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(40, 'Gabriel Rocha', 'gabriel@email.com', 'gabriel.8', '(11) 98888-8888', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(41, 'Helena Martins', 'helena@email.com', 'helena.9', '(11) 98888-9999', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(42, 'Igor Ferreira', 'igor@email.com', 'igor.10', '(11) 97777-1111', 'paciente', NULL, 1, '2026-10-10 21:38:49', '2026-10-10 21:38:49'),
(43, 'João Pedro', 'joao@email.com', 'joao.11', '(11) 97777-2222', 'paciente', NULL, 1, '2026-10-10 21:39:12', '2026-10-10 21:39:12'),
(44, 'Karen Oliveira', 'karen@email.com', 'karen.12', '(11) 97777-3333', 'paciente', NULL, 1, '2026-10-10 21:39:12', '2026-10-10 21:39:12'),
(45, 'Lucas Santos', 'lucas@email.com', 'lucas.13', '(11) 97777-4444', 'paciente', NULL, 1, '2026-10-10 21:39:12', '2026-10-10 21:39:12');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `artigos`
--
ALTER TABLE `artigos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Índices de tabela `conversas`
--
ALTER TABLE `conversas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`id_usuario`);

--
-- Índices de tabela `historico_conversas`
--
ALTER TABLE `historico_conversas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`id_usuario`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversa` (`id_conversa`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `artigos`
--
ALTER TABLE `artigos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `conversas`
--
ALTER TABLE `conversas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_conversas`
--
ALTER TABLE `historico_conversas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `conversas`
--
ALTER TABLE `conversas`
  ADD CONSTRAINT `conversas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`id_conversa`) REFERENCES `conversas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
