-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24/04/2025 às 17:21
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
-- Banco de dados: `pizza`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_adicional`
--

CREATE TABLE `tb_adicional` (
  `id_adicional` int(11) NOT NULL,
  `id_tipo_adicional` int(11) NOT NULL,
  `nome_adicional` varchar(100) NOT NULL,
  `valor_adicional` decimal(10,2) DEFAULT 0.00,
  `adicional_ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_adicional`
--

INSERT INTO `tb_adicional` (`id_adicional`, `id_tipo_adicional`, `nome_adicional`, `valor_adicional`, `adicional_ativo`) VALUES
(1, 1, 'Sem borda recheada', 0.00, 1),
(2, 1, 'Borda Catupiry', 2.00, 1),
(3, 1, 'Borda Cheddar', 3.00, 1),
(4, 2, 'Bacon', 2.00, 1),
(5, 2, 'Frango', 5.00, 1),
(6, 2, 'Milho', 1.00, 1),
(7, 3, 'Leite condensado', 2.00, 1),
(8, 3, 'Mel', 1.50, 1),
(9, 3, 'Calda de chocolate', 1.50, 1),
(10, 4, 'Granola', 2.00, 1),
(11, 4, 'Banana', 2.50, 1),
(12, 4, 'Leite em pó', 1.50, 1),
(13, 4, 'Paçoca', 2.00, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_campanha_brinde`
--

CREATE TABLE `tb_campanha_brinde` (
  `id_campanha_brinde` int(11) NOT NULL,
  `nome_campanha` varchar(100) NOT NULL,
  `dia_semana` enum('segunda','terça','quarta','quinta','sexta','sábado','domingo') DEFAULT NULL,
  `quantidade_min_produtos` int(11) NOT NULL,
  `id_produto_brinde` int(11) NOT NULL,
  `descricao_brinde` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_inicio` date DEFAULT curdate(),
  `data_fim` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_campanha_brinde`
--

INSERT INTO `tb_campanha_brinde` (`id_campanha_brinde`, `nome_campanha`, `dia_semana`, `quantidade_min_produtos`, `id_produto_brinde`, `descricao_brinde`, `ativo`, `data_inicio`, `data_fim`) VALUES
(1, 'Quinta da Pizza', 'quinta', 2, 3, 'Ganhe 1 refrigerante Dolly 2L', 1, '2025-04-24', NULL),
(2, 'Sábado em Dobro', 'sábado', 2, 7, 'Ganhe uma Dolly 2L nas compras de 2 pizzas.', 1, '2025-04-24', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_campanha_produto_dia`
--

CREATE TABLE `tb_campanha_produto_dia` (
  `id_campanha` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `dia_semana` enum('segunda','terça','quarta','quinta','sexta','sábado','domingo') NOT NULL,
  `valor_promocional` decimal(10,2) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_inicio` date DEFAULT curdate(),
  `data_fim` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_campanha_produto_dia`
--

INSERT INTO `tb_campanha_produto_dia` (`id_campanha`, `id_produto`, `dia_semana`, `valor_promocional`, `ativo`, `data_inicio`, `data_fim`) VALUES
(1, 1, 'segunda', 25.00, 1, '2025-04-24', NULL),
(2, 1, 'terça', 25.00, 1, '2025-04-24', NULL),
(3, 1, 'quinta', 25.00, 1, '2025-04-24', NULL),
(4, 4, 'quarta', 32.00, 1, '2025-04-24', NULL),
(5, 5, 'sexta', 34.00, 1, '2025-04-24', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_categoria`
--

CREATE TABLE `tb_categoria` (
  `id_categoria` int(11) NOT NULL,
  `nome_categoria` varchar(100) NOT NULL,
  `tem_qtd` tinyint(1) DEFAULT 0,
  `categoria_ativa` tinyint(1) DEFAULT 1,
  `ordem_exibicao` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_categoria`
--

INSERT INTO `tb_categoria` (`id_categoria`, `nome_categoria`, `tem_qtd`, `categoria_ativa`, `ordem_exibicao`) VALUES
(1, 'Pizzas', 0, 1, 0),
(2, 'Bebidas', 0, 1, 0),
(5, 'Açaís', 0, 1, 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_combo_item`
--

CREATE TABLE `tb_combo_item` (
  `id_combo_item` int(11) NOT NULL,
  `id_combo` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `quantidade` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_combo_promocional`
--

CREATE TABLE `tb_combo_promocional` (
  `id_combo` int(11) NOT NULL,
  `nome_combo` varchar(150) NOT NULL,
  `descricao_combo` text DEFAULT NULL,
  `valor_combo` decimal(10,2) NOT NULL,
  `imagem_combo` varchar(255) DEFAULT NULL,
  `tipo_combo` enum('fixo','personalizável') DEFAULT 'fixo',
  `combo_ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_cupom`
--

CREATE TABLE `tb_cupom` (
  `id_cupom` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `tipo` enum('porcentagem','fixo') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `uso_unico` tinyint(1) DEFAULT 0,
  `quantidade_usos` int(11) DEFAULT NULL,
  `minimo_pedido` decimal(10,2) DEFAULT 0.00,
  `valido_de` date DEFAULT curdate(),
  `valido_ate` date DEFAULT NULL,
  `cupom_ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_cupom`
--

INSERT INTO `tb_cupom` (`id_cupom`, `codigo`, `descricao`, `tipo`, `valor`, `uso_unico`, `quantidade_usos`, `minimo_pedido`, `valido_de`, `valido_ate`, `cupom_ativo`) VALUES
(1, 'FRETE10', '10% de desconto acima de R$30', 'porcentagem', 10.00, 0, NULL, 30.00, '2025-04-24', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_item_adicional`
--

CREATE TABLE `tb_item_adicional` (
  `id_item_adicional` int(11) NOT NULL,
  `id_item_pedido` int(11) NOT NULL,
  `id_adicional` int(11) NOT NULL,
  `nome_adicional` varchar(100) DEFAULT NULL,
  `valor_adicional` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_item_pedido`
--

CREATE TABLE `tb_item_pedido` (
  `id_item_pedido` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_produto` int(11) DEFAULT NULL,
  `id_combo` int(11) DEFAULT NULL,
  `nome_exibicao` varchar(150) DEFAULT NULL,
  `quantidade` int(11) DEFAULT 1,
  `valor_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_pedido`
--

CREATE TABLE `tb_pedido` (
  `id_pedido` int(11) NOT NULL,
  `nome_cliente` varchar(100) DEFAULT NULL,
  `telefone_cliente` varchar(20) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `tipo_entrega` enum('retirada','entrega') NOT NULL,
  `forma_pagamento` enum('pix','dinheiro','cartao') NOT NULL,
  `observacoes` text DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `id_cupom` int(11) DEFAULT NULL,
  `desconto_aplicado` decimal(10,2) DEFAULT 0.00,
  `status_pedido` enum('pendente','preparando','pronto','finalizado','cancelado') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_produto`
--

CREATE TABLE `tb_produto` (
  `id_produto` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `nome_produto` varchar(150) NOT NULL,
  `slug_produto` varchar(150) DEFAULT NULL,
  `valor_produto` decimal(10,2) NOT NULL,
  `imagem_produto` varchar(255) DEFAULT NULL,
  `descricao_produto` text DEFAULT NULL,
  `produto_ativo` tinyint(1) DEFAULT 1,
  `qtd_produto` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_produto`
--

INSERT INTO `tb_produto` (`id_produto`, `id_categoria`, `nome_produto`, `slug_produto`, `valor_produto`, `imagem_produto`, `descricao_produto`, `produto_ativo`, `qtd_produto`) VALUES
(1, 1, 'Pizza Mussarela', 'pizza-mussarela', 30.00, NULL, NULL, 1, NULL),
(2, 1, 'Pizza Calabresa', 'pizza-calabresa', 35.00, NULL, NULL, 1, NULL),
(3, 2, 'Refrigerante Dolly 2L', 'refri-dolly', 8.00, NULL, NULL, 1, NULL),
(4, 2, 'Refrigerante Sukita 2L', 'refri-sukita', 8.00, NULL, NULL, 1, NULL),
(5, 1, 'Pizza Quatro Queijos', 'pizza-quatro-queijos', 38.00, NULL, 'Mussarela, provolone, gorgonzola e parmesão.', 1, NULL),
(6, 1, 'Pizza Frango com Catupiry', 'pizza-frango-catupiry', 39.00, NULL, 'Frango desfiado com catupiry cremoso.', 1, NULL),
(7, 2, 'Refrigerante Dolly 2L', 'refri-dolly', 6.00, NULL, 'Dolly Guaraná 2L.', 1, NULL),
(8, 2, 'Refrigerante Pepsi 2L', 'refri-pepsi', 8.00, NULL, 'Pepsi 2L.', 1, NULL),
(9, 1, 'Combo Pizza Mussarela + Dolly 2L', 'combo-mussarela-dolly', 32.00, NULL, 'Pizza Mussarela + Dolly 2L com desconto.', 1, NULL),
(10, 1, 'Combo 2 Pizzas Tradicionais', 'combo-2tradicionais', 60.00, NULL, 'Duas pizzas tradicionais por preço especial.', 1, NULL),
(11, 5, 'Açaí Simples 300ml', 'acai-simples', 10.00, NULL, 'Açaí natural, puro e saudável.', 1, NULL),
(12, 5, 'Açaí Especial 500ml', 'acai-especial', 18.00, NULL, 'Açaí com leite condensado, banana e granola.', 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_produto_adicional_incluso`
--

CREATE TABLE `tb_produto_adicional_incluso` (
  `id_produto_adicional_incluso` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_adicional` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_produto_adicional_incluso`
--

INSERT INTO `tb_produto_adicional_incluso` (`id_produto_adicional_incluso`, `id_produto`, `id_adicional`) VALUES
(1, 2, 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_produto_tipo_adicional`
--

CREATE TABLE `tb_produto_tipo_adicional` (
  `id_produto_tipo_adicional` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_tipo_adicional` int(11) NOT NULL,
  `obrigatorio` tinyint(1) DEFAULT 0,
  `max_inclusos` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_produto_tipo_adicional`
--

INSERT INTO `tb_produto_tipo_adicional` (`id_produto_tipo_adicional`, `id_produto`, `id_tipo_adicional`, `obrigatorio`, `max_inclusos`) VALUES
(1, 1, 2, 0, 2),
(2, 4, 1, 1, 1),
(3, 4, 2, 0, 2),
(4, 5, 1, 1, 1),
(5, 5, 2, 0, 2),
(6, 6, 1, 1, 1),
(7, 6, 2, 0, 2),
(8, 9, 1, 1, 1),
(9, 9, 2, 0, 2),
(10, 10, 1, 1, 1),
(11, 10, 2, 0, 2),
(12, 11, 4, 0, 3),
(13, 12, 3, 1, 1),
(14, 12, 4, 0, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_subcategoria`
--

CREATE TABLE `tb_subcategoria` (
  `id_subcategoria` int(11) NOT NULL,
  `nome_subcategoria` varchar(100) NOT NULL,
  `tipo_subcategoria` enum('padrão','tamanho','massa','bebida') DEFAULT 'padrão',
  `subcategoria_ativa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_subcategoria`
--

INSERT INTO `tb_subcategoria` (`id_subcategoria`, `nome_subcategoria`, `tipo_subcategoria`, `subcategoria_ativa`) VALUES
(1, 'Tradicional', 'padrão', 1),
(2, 'Especial', 'padrão', 1),
(3, 'Vegana', 'padrão', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_subcategoria_categoria`
--

CREATE TABLE `tb_subcategoria_categoria` (
  `id_subcategoria_categoria` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_subcategoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_subcategoria_categoria`
--

INSERT INTO `tb_subcategoria_categoria` (`id_subcategoria_categoria`, `id_categoria`, `id_subcategoria`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 5, 1),
(5, 5, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_tipo_adicional`
--

CREATE TABLE `tb_tipo_adicional` (
  `id_tipo_adicional` int(11) NOT NULL,
  `nome_tipo_adicional` varchar(100) NOT NULL,
  `obrigatorio` tinyint(1) DEFAULT 0,
  `multipla_escolha` tinyint(1) DEFAULT 1,
  `max_escolha` int(11) DEFAULT NULL,
  `tipo_ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_tipo_adicional`
--

INSERT INTO `tb_tipo_adicional` (`id_tipo_adicional`, `nome_tipo_adicional`, `obrigatorio`, `multipla_escolha`, `max_escolha`, `tipo_ativo`) VALUES
(1, 'Borda', 1, 0, 1, 1),
(2, 'Recheio extra', 0, 1, 3, 1),
(3, 'Cobertura', 1, 0, 1, 1),
(4, 'Complementos', 0, 1, 5, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_tipo_adicional_categoria`
--

CREATE TABLE `tb_tipo_adicional_categoria` (
  `id_tipo_adicional_categoria` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_tipo_adicional` int(11) NOT NULL,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_tipo_adicional_categoria`
--

INSERT INTO `tb_tipo_adicional_categoria` (`id_tipo_adicional_categoria`, `id_categoria`, `id_tipo_adicional`, `ordem`) VALUES
(1, 1, 1, 0),
(2, 1, 2, 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `tb_adicional`
--
ALTER TABLE `tb_adicional`
  ADD PRIMARY KEY (`id_adicional`),
  ADD KEY `fk_adicional_tipo` (`id_tipo_adicional`);

--
-- Índices de tabela `tb_campanha_brinde`
--
ALTER TABLE `tb_campanha_brinde`
  ADD PRIMARY KEY (`id_campanha_brinde`);

--
-- Índices de tabela `tb_campanha_produto_dia`
--
ALTER TABLE `tb_campanha_produto_dia`
  ADD PRIMARY KEY (`id_campanha`);

--
-- Índices de tabela `tb_categoria`
--
ALTER TABLE `tb_categoria`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Índices de tabela `tb_combo_item`
--
ALTER TABLE `tb_combo_item`
  ADD PRIMARY KEY (`id_combo_item`),
  ADD KEY `fk_comboitem_combo` (`id_combo`),
  ADD KEY `fk_comboitem_prod` (`id_produto`);

--
-- Índices de tabela `tb_combo_promocional`
--
ALTER TABLE `tb_combo_promocional`
  ADD PRIMARY KEY (`id_combo`);

--
-- Índices de tabela `tb_cupom`
--
ALTER TABLE `tb_cupom`
  ADD PRIMARY KEY (`id_cupom`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `tb_item_adicional`
--
ALTER TABLE `tb_item_adicional`
  ADD PRIMARY KEY (`id_item_adicional`);

--
-- Índices de tabela `tb_item_pedido`
--
ALTER TABLE `tb_item_pedido`
  ADD PRIMARY KEY (`id_item_pedido`),
  ADD KEY `fk_itempedido_pedido` (`id_pedido`),
  ADD KEY `fk_itempedido_produto` (`id_produto`),
  ADD KEY `fk_itempedido_combo` (`id_combo`);

--
-- Índices de tabela `tb_pedido`
--
ALTER TABLE `tb_pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedido_cupom` (`id_cupom`);

--
-- Índices de tabela `tb_produto`
--
ALTER TABLE `tb_produto`
  ADD PRIMARY KEY (`id_produto`),
  ADD KEY `fk_produto_categoria` (`id_categoria`);

--
-- Índices de tabela `tb_produto_adicional_incluso`
--
ALTER TABLE `tb_produto_adicional_incluso`
  ADD PRIMARY KEY (`id_produto_adicional_incluso`),
  ADD KEY `fk_prod_inc_prod` (`id_produto`),
  ADD KEY `fk_prod_inc_adicional` (`id_adicional`);

--
-- Índices de tabela `tb_produto_tipo_adicional`
--
ALTER TABLE `tb_produto_tipo_adicional`
  ADD PRIMARY KEY (`id_produto_tipo_adicional`),
  ADD KEY `fk_prod_tipo_prod` (`id_produto`),
  ADD KEY `fk_prod_tipo_tipo` (`id_tipo_adicional`);

--
-- Índices de tabela `tb_subcategoria`
--
ALTER TABLE `tb_subcategoria`
  ADD PRIMARY KEY (`id_subcategoria`);

--
-- Índices de tabela `tb_subcategoria_categoria`
--
ALTER TABLE `tb_subcategoria_categoria`
  ADD PRIMARY KEY (`id_subcategoria_categoria`),
  ADD KEY `fk_subcat_categoria` (`id_categoria`),
  ADD KEY `fk_subcat_subcategoria` (`id_subcategoria`);

--
-- Índices de tabela `tb_tipo_adicional`
--
ALTER TABLE `tb_tipo_adicional`
  ADD PRIMARY KEY (`id_tipo_adicional`);

--
-- Índices de tabela `tb_tipo_adicional_categoria`
--
ALTER TABLE `tb_tipo_adicional_categoria`
  ADD PRIMARY KEY (`id_tipo_adicional_categoria`),
  ADD KEY `fk_tipo_cat_categoria` (`id_categoria`),
  ADD KEY `fk_tipo_cat_tipo` (`id_tipo_adicional`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `tb_adicional`
--
ALTER TABLE `tb_adicional`
  MODIFY `id_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `tb_campanha_brinde`
--
ALTER TABLE `tb_campanha_brinde`
  MODIFY `id_campanha_brinde` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `tb_campanha_produto_dia`
--
ALTER TABLE `tb_campanha_produto_dia`
  MODIFY `id_campanha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tb_categoria`
--
ALTER TABLE `tb_categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tb_combo_item`
--
ALTER TABLE `tb_combo_item`
  MODIFY `id_combo_item` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_combo_promocional`
--
ALTER TABLE `tb_combo_promocional`
  MODIFY `id_combo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_cupom`
--
ALTER TABLE `tb_cupom`
  MODIFY `id_cupom` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tb_item_adicional`
--
ALTER TABLE `tb_item_adicional`
  MODIFY `id_item_adicional` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_item_pedido`
--
ALTER TABLE `tb_item_pedido`
  MODIFY `id_item_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_pedido`
--
ALTER TABLE `tb_pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_produto`
--
ALTER TABLE `tb_produto`
  MODIFY `id_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `tb_produto_adicional_incluso`
--
ALTER TABLE `tb_produto_adicional_incluso`
  MODIFY `id_produto_adicional_incluso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tb_produto_tipo_adicional`
--
ALTER TABLE `tb_produto_tipo_adicional`
  MODIFY `id_produto_tipo_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria`
--
ALTER TABLE `tb_subcategoria`
  MODIFY `id_subcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria_categoria`
--
ALTER TABLE `tb_subcategoria_categoria`
  MODIFY `id_subcategoria_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tb_tipo_adicional`
--
ALTER TABLE `tb_tipo_adicional`
  MODIFY `id_tipo_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `tb_tipo_adicional_categoria`
--
ALTER TABLE `tb_tipo_adicional_categoria`
  MODIFY `id_tipo_adicional_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--
