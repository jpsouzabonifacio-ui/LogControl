-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 20/11/2026 às 22:58
-- Versão do servidor: 10.11.19-MariaDB
-- Versão do PHP: 8.3.37

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `logistica_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

CREATE TABLE `enderecos` (
  `id` int(11) NOT NULL,
  `identificacao` varchar(100) NOT NULL,
  `destinatario` varchar(150) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `rua` varchar(150) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `referencia` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `enderecos`
--

INSERT INTO `enderecos` (`id`, `identificacao`, `destinatario`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `referencia`, `criado_em`) VALUES
(1, 'DEPOSITO AMERICA', 'EMPRESA LOGCONTROL', '87206424', 'RUA ALECRIM', '157', 'GALPAO 02', 'RESIDENCIAL TESTE', 'CIANORTE', 'PR', 'PROXIMO A IGREJA MATRIZ', '2026-11-20 19:35:50'),
(3, 'CENTRO DE DISTRIBUICAO', 'LOGCONTROL', '211222121', 'RUA TESTE02', '123', 'EMPRESA', 'RESIDENCIAL TESTE02', 'TESTELANDIA', 'PR', 'NO COMECO DA AVENIDA PRINCIPAL', '2026-11-20 20:43:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fretes`
--

CREATE TABLE `fretes` (
  `id` int(11) NOT NULL,
  `transportador_id` int(11) NOT NULL,
  `origem_id` int(11) NOT NULL,
  `destino_id` int(11) NOT NULL,
  `data_frete` date NOT NULL,
  `previsao_entrega` date DEFAULT NULL,
  `status` enum('Pendente','Em trânsito','Entregue','Cancelado') DEFAULT 'Pendente',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `fretes`
--

INSERT INTO `fretes` (`id`, `transportador_id`, `origem_id`, `destino_id`, `data_frete`, `previsao_entrega`, `status`, `observacoes`, `criado_em`) VALUES
(2, 1, 1, 3, '2026-11-20', '2026-12-03', 'Em trânsito', 'lote de caixas', '2026-11-20 21:14:07'),
(3, 1, 1, 3, '2026-11-20', '2026-11-30', 'Em trânsito', '', '2026-11-20 21:37:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `frete_produtos`
--

CREATE TABLE `frete_produtos` (
  `id` int(11) NOT NULL,
  `frete_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `frete_produtos`
--

INSERT INTO `frete_produtos` (`id`, `frete_id`, `produto_id`, `quantidade`) VALUES
(3, 2, 1, 1000),
(5, 3, 1, 3000);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `peso_kg` decimal(10,2) NOT NULL,
  `unidade` varchar(20) DEFAULT 'UN',
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `descricao`, `codigo`, `peso_kg`, `unidade`, `ativo`, `criado_em`) VALUES
(1, 'caixa', 'caixa de papelao', '1', 2.00, 'Unidade', 1, '2026-11-20 19:18:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `transportadores`
--

CREATE TABLE `transportadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `tipo` enum('PF','PJ') DEFAULT 'PF',
  `documento` varchar(20) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `tipo_veiculo` varchar(100) DEFAULT NULL,
  `placa` varchar(10) DEFAULT NULL,
  `capacidade_kg` decimal(10,2) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `transportadores`
--

INSERT INTO `transportadores` (`id`, `nome`, `tipo`, `documento`, `telefone`, `email`, `tipo_veiculo`, `placa`, `capacidade_kg`, `ativo`, `criado_em`) VALUES
(1, 'TESTE TRANSPORTE', 'PF', '35464545453', '40028922', 'teset@gmail.com', 'CAMINHÃO', 'TES3T01', 15000.00, 1, '2026-11-20 20:17:29');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fretes`
--
ALTER TABLE `fretes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transportador_id` (`transportador_id`),
  ADD KEY `origem_id` (`origem_id`),
  ADD KEY `destino_id` (`destino_id`);

--
-- Índices de tabela `frete_produtos`
--
ALTER TABLE `frete_produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `frete_id` (`frete_id`,`produto_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `transportadores`
--
ALTER TABLE `transportadores`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `fretes`
--
ALTER TABLE `fretes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `frete_produtos`
--
ALTER TABLE `frete_produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `transportadores`
--
ALTER TABLE `transportadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `fretes`
--
ALTER TABLE `fretes`
  ADD CONSTRAINT `fretes_ibfk_1` FOREIGN KEY (`transportador_id`) REFERENCES `transportadores` (`id`),
  ADD CONSTRAINT `fretes_ibfk_2` FOREIGN KEY (`origem_id`) REFERENCES `enderecos` (`id`),
  ADD CONSTRAINT `fretes_ibfk_3` FOREIGN KEY (`destino_id`) REFERENCES `enderecos` (`id`);

--
-- Restrições para tabelas `frete_produtos`
--
ALTER TABLE `frete_produtos`
  ADD CONSTRAINT `frete_produtos_ibfk_1` FOREIGN KEY (`frete_id`) REFERENCES `fretes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `frete_produtos_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
