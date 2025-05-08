-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/05/2025 às 17:45
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
-- Estrutura para tabela `tb_dados_loja`
--

CREATE TABLE `tb_dados_loja` (
  `id_loja` int(11) NOT NULL,
  `nome_loja` varchar(150) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `endereco_completo` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `tema` varchar(50) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `google` varchar(255) DEFAULT NULL,
  `preco_base` decimal(10,2) DEFAULT 5.00,
  `preco_km` decimal(10,2) DEFAULT 2.00,
  `limite_entrega` decimal(10,2) DEFAULT NULL,
  `tempo_entrega` int(11) DEFAULT 45,
  `tempo_retirada` int(11) DEFAULT 20
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_dados_loja`
--

INSERT INTO `tb_dados_loja` (`id_loja`, `nome_loja`, `cep`, `endereco_completo`, `logo`, `tema`, `instagram`, `whatsapp`, `google`, `preco_base`, `preco_km`, `limite_entrega`, `tempo_entrega`, `tempo_retirada`) VALUES
(1, 'Bella Massa', '04434150', 'Rua Germano Gottsfritz, 431', NULL, 'light', 'alvesferz', '11961723132', 'AIzaSyDg5xiBHnQKhUvwCSjOY2YJ4SN5L0wEj78', 0.00, 2.00, 5.00, 45, 20);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_endereco`
--

CREATE TABLE `tb_endereco` (
  `id_endereco` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `apelido` varchar(100) NOT NULL,
  `cep` varchar(9) NOT NULL,
  `rua` varchar(255) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `ponto_de_referencia` varchar(255) DEFAULT NULL,
  `endereco_principal` tinyint(1) DEFAULT 0,
  `endereco_ativo` int(11) NOT NULL DEFAULT 1,
  `criado_em` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_endereco`
--

INSERT INTO `tb_endereco` (`id_endereco`, `id_usuario`, `apelido`, `cep`, `rua`, `numero`, `complemento`, `bairro`, `ponto_de_referencia`, `endereco_principal`, `endereco_ativo`, `criado_em`) VALUES
(1, 1, 'Casa', '04434150', 'Rua Germano Gottsfritz', '431', NULL, 'Jardim Uberaba', '', 0, 1, '2025-04-25 20:18:40'),
(2, 1, 'Teste', '04434150', 'Rua Germano Gottsfritz', '431', 'AP 275', 'Jardim Uberaba', '', 0, 1, '2025-04-25 21:16:18'),
(5, 1, 'Cass', '04434100', 'Rua Senador Paulo Guerra', '410', '', 'Jardim Maria Luiza', '', 0, 0, '2025-05-06 16:12:19'),
(6, 1, 'Casa', '04433150', 'Avenida Vinte e Sete', '20', '', 'Jardim Itapura', '', 0, 1, '2025-05-06 17:58:00'),
(7, 1, 'Fac', '04696000', 'Avenida Engenheiro Eusébio Stevaux', '823', '', 'Jurubatuba', '', 1, 1, '2025-05-06 17:58:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_forma_pgto`
--

CREATE TABLE `tb_forma_pgto` (
  `id_forma` int(11) NOT NULL,
  `nome_pgto` varchar(100) NOT NULL,
  `pagamento_ativo` tinyint(1) DEFAULT 1,
  `is_online` tinyint(1) DEFAULT 0,
  `chave` char(32) DEFAULT NULL,
  `chave2` char(32) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tb_forma_pgto`
--

INSERT INTO `tb_forma_pgto` (`id_forma`, `nome_pgto`, `pagamento_ativo`, `is_online`, `chave`, `chave2`, `created_at`, `updated_at`) VALUES
(1, 'Pix', 1, 0, NULL, NULL, '2025-04-25 20:08:35', '2025-04-25 20:09:32'),
(2, 'Cartão de Crédito', 1, 0, NULL, NULL, '2025-04-25 20:08:55', '2025-04-25 20:08:55'),
(3, 'Cartão de Débito', 1, 0, NULL, NULL, '2025-04-25 20:09:15', '2025-04-25 20:09:15'),
(4, 'Online', 1, 1, NULL, NULL, '2025-04-25 20:09:45', '2025-04-25 20:10:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_funcionario`
--

CREATE TABLE `tb_funcionario` (
  `id_funcionario` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `funcao` varchar(100) NOT NULL,
  `forma_pgto` enum('fixo_mensal','por_dia','por_entrega') DEFAULT 'por_dia',
  `valor_pgto` decimal(10,2) DEFAULT 0.00,
  `valor_por_entrega` decimal(10,2) DEFAULT 0.00,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Despejando dados para a tabela `tb_item_adicional`
--

INSERT INTO `tb_item_adicional` (`id_item_adicional`, `id_item_pedido`, `id_adicional`, `nome_adicional`, `valor_adicional`) VALUES
(1, 2, 6, 'Milho', 1.00),
(2, 3, 6, 'Milho', 1.00),
(3, 4, 4, 'Bacon', 2.00),
(4, 5, 4, 'Bacon', 2.00),
(5, 5, 5, 'Frango', 5.00),
(6, 5, 6, 'Milho', 1.00),
(7, 6, 4, 'Bacon', 2.00);

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

--
-- Despejando dados para a tabela `tb_item_pedido`
--

INSERT INTO `tb_item_pedido` (`id_item_pedido`, `id_pedido`, `id_produto`, `id_combo`, `nome_exibicao`, `quantidade`, `valor_unitario`) VALUES
(1, 1, 13, NULL, 'Pizza Três Sabores', 1, 38.00),
(2, 2, 1, NULL, 'Pizza Mussarela', 1, 31.00),
(3, 3, 1, NULL, 'Pizza Mussarela', 1, 31.00),
(4, 4, 1, NULL, 'Pizza Mussarela', 1, 32.00),
(5, 5, 1, NULL, 'Pizza Mussarela', 1, 38.00),
(6, 6, 1, NULL, 'Pizza Mussarela', 1, 32.00),
(7, 7, 1, NULL, 'Pizza Mussarela', 1, 30.00),
(8, 8, 1, NULL, 'Pizza Mussarela', 1, 30.00),
(9, 8, 1, NULL, 'Pizza Mussarela', 1, 30.00),
(10, 9, 2, NULL, 'Pizza Calabresa', 1, 35.00),
(11, 10, 1, NULL, 'Pizza Mussarela', 1, 30.00),
(12, 11, 1, NULL, '', 1, 30.00),
(13, 12, 1, NULL, '', 1, 30.00),
(14, 13, 3, NULL, '', 1, 8.00),
(15, 14, 4, NULL, '', 1, 32.00),
(16, 15, 6, NULL, '', 1, 39.00),
(17, 16, 1, NULL, 'teste', 1, 38.00),
(18, 17, 1, NULL, '', 1, 30.00),
(19, 18, 1, NULL, 'Pizza Mussarela', 1, 36.00),
(20, 19, NULL, NULL, 'Combo Pizza Mussarela + Dolly 2L', 1, 34.00),
(21, 20, 1, NULL, 'Pizza Mussarela', 1, 25.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_item_pedido_sabor`
--

CREATE TABLE `tb_item_pedido_sabor` (
  `id_item_pedido_sabor` int(11) NOT NULL,
  `id_item_pedido` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `proporcao` decimal(5,2) DEFAULT 0.50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `tb_item_pedido_sabor`
--

INSERT INTO `tb_item_pedido_sabor` (`id_item_pedido_sabor`, `id_item_pedido`, `id_produto`, `proporcao`) VALUES
(1, 1, 5, 50.00),
(2, 1, 2, 50.00),
(3, 1, 1, 50.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_pedido`
--

CREATE TABLE `tb_pedido` (
  `id_pedido` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `id_funcionario` int(11) DEFAULT NULL,
  `id_entregador` int(11) DEFAULT NULL,
  `nome_cliente` varchar(100) DEFAULT NULL,
  `telefone_cliente` varchar(20) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `tipo_entrega` enum('retirada','entrega') NOT NULL,
  `forma_pagamento` enum('pix','dinheiro','cartao') NOT NULL,
  `observacoes` text DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_frete` decimal(10,2) NOT NULL,
  `id_cupom` int(11) DEFAULT NULL,
  `desconto_aplicado` decimal(10,2) DEFAULT 0.00,
  `status_pedido` enum('pendente','aceito','em_preparo','em_entrega','finalizado','cancelado') DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelado_em` datetime DEFAULT NULL,
  `motivo_cancelamento` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_pedido`
--

INSERT INTO `tb_pedido` (`id_pedido`, `id_usuario`, `id_funcionario`, `id_entregador`, `nome_cliente`, `telefone_cliente`, `endereco`, `tipo_entrega`, `forma_pagamento`, `observacoes`, `valor_total`, `valor_frete`, `id_cupom`, `desconto_aplicado`, `status_pedido`, `criado_em`, `cancelado_em`, `motivo_cancelamento`) VALUES
(1, 1, NULL, NULL, NULL, NULL, 'Retirada na loja', 'retirada', '', NULL, 38.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-06 22:53:10', '2025-05-07 10:40:37', ''),
(2, 1, NULL, NULL, NULL, NULL, 'Retirada na loja', 'retirada', '', NULL, 32.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 12:18:25', '2025-05-07 10:40:34', ''),
(3, 1, NULL, NULL, NULL, NULL, 'Retirada na loja', 'retirada', '', NULL, 32.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 12:24:33', '2025-05-07 10:40:31', ''),
(4, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 34.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 12:27:12', '2025-05-07 10:40:29', ''),
(5, 1, NULL, NULL, 'Alves', '11961723132', 'Rua Senador Paulo Guerra, 410 - Jardim Maria Luiza', 'entrega', '', NULL, 46.50, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 12:29:44', '2025-05-07 10:40:26', ''),
(6, 1, NULL, NULL, 'Alves', '11961723132', 'Rua Senador Paulo Guerra, 410 - Jardim Maria Luiza', 'entrega', '', NULL, 34.50, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 12:34:04', '2025-05-07 10:40:20', ''),
(7, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 30.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 12:49:58', '2025-05-07 10:40:17', ''),
(8, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 60.00, 0.00, NULL, 0.00, 'em_preparo', '2025-05-07 17:56:03', NULL, NULL),
(9, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 35.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 17:56:58', NULL, NULL),
(10, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 30.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 18:06:57', NULL, NULL),
(11, 1, NULL, NULL, NULL, NULL, 'Retirada na loja', 'retirada', '', NULL, 30.00, 0.00, NULL, 0.00, 'em_entrega', '2025-05-07 18:21:58', NULL, NULL),
(12, 1, NULL, NULL, NULL, NULL, 'Retirada na loja', 'retirada', '', NULL, 30.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 18:22:31', NULL, NULL),
(13, 1, NULL, NULL, NULL, NULL, 'Retirada na loja', 'retirada', '', NULL, 8.00, 0.00, NULL, 0.00, 'em_preparo', '2025-05-07 18:23:57', NULL, NULL),
(14, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 32.00, 0.00, NULL, 0.00, 'em_preparo', '2025-05-07 18:25:24', NULL, NULL),
(15, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 39.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 18:42:34', NULL, NULL),
(16, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 38.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 18:48:55', NULL, NULL),
(17, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 30.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 20:33:32', NULL, NULL),
(18, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 36.00, 0.00, NULL, 0.00, 'cancelado', '2025-05-07 20:37:01', NULL, NULL),
(19, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 34.00, 0.00, NULL, 0.00, 'em_preparo', '2025-05-07 20:38:31', NULL, NULL),
(20, 1, NULL, NULL, 'Alves', '11961723132', 'Retirada na loja', 'retirada', '', NULL, 25.00, 0.00, NULL, 0.00, 'pendente', '2025-05-08 13:31:45', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_pedido_status_log`
--

CREATE TABLE `tb_pedido_status_log` (
  `id_log` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `status_anterior` varchar(50) DEFAULT NULL,
  `status_novo` varchar(50) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `alterado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_pedido_status_log`
--

INSERT INTO `tb_pedido_status_log` (`id_log`, `id_pedido`, `status_anterior`, `status_novo`, `motivo`, `alterado_em`) VALUES
(1, 7, 'pendente', 'cancelado', '', '2025-05-07 10:40:17'),
(2, 6, 'pendente', 'cancelado', '', '2025-05-07 10:40:20'),
(3, 5, 'pendente', 'cancelado', '', '2025-05-07 10:40:26'),
(4, 4, 'pendente', 'cancelado', '', '2025-05-07 10:40:29'),
(5, 3, 'pendente', 'cancelado', '', '2025-05-07 10:40:31'),
(6, 2, 'pendente', 'cancelado', '', '2025-05-07 10:40:34'),
(7, 1, 'pendente', 'cancelado', '', '2025-05-07 10:40:37'),
(8, 8, NULL, 'pendente', NULL, '2025-05-07 14:56:03'),
(9, 9, NULL, 'pendente', NULL, '2025-05-07 14:56:58'),
(10, 10, NULL, 'pendente', NULL, '2025-05-07 15:06:57'),
(11, 11, NULL, 'pendente', NULL, '2025-05-07 15:21:58'),
(12, 11, 'pendente', 'aceito', NULL, '2025-05-07 15:22:09'),
(13, 8, 'pendente', 'aceito', NULL, '2025-05-07 15:22:17'),
(14, 12, NULL, 'pendente', NULL, '2025-05-07 15:22:31'),
(15, 13, NULL, 'pendente', NULL, '2025-05-07 15:23:57'),
(16, 14, NULL, 'pendente', NULL, '2025-05-07 15:25:24'),
(17, 14, 'pendente', 'aceito', NULL, '2025-05-07 15:41:10'),
(18, 13, 'pendente', 'aceito', NULL, '2025-05-07 15:41:14'),
(19, 15, NULL, 'pendente', NULL, '2025-05-07 15:42:34'),
(20, 8, 'aceito', 'em_preparo', NULL, '2025-05-07 15:42:54'),
(21, 16, NULL, 'pendente', NULL, '2025-05-07 15:48:55'),
(22, 17, NULL, 'pendente', NULL, '2025-05-07 17:33:32'),
(23, 18, NULL, 'pendente', NULL, '2025-05-07 17:37:01'),
(24, 19, NULL, 'pendente', NULL, '2025-05-07 17:38:31'),
(25, 19, 'pendente', 'aceito', NULL, '2025-05-07 17:39:18'),
(26, 18, 'pendente', 'cancelado', NULL, '2025-05-07 20:58:59'),
(27, 17, 'pendente', 'cancelado', NULL, '2025-05-07 20:59:05'),
(28, 16, 'pendente', 'cancelado', NULL, '2025-05-07 20:59:09'),
(29, 15, 'pendente', 'cancelado', NULL, '2025-05-07 20:59:11'),
(30, 12, 'pendente', 'cancelado', NULL, '2025-05-07 20:59:14'),
(31, 10, 'pendente', 'cancelado', NULL, '2025-05-07 20:59:16'),
(32, 9, 'pendente', 'cancelado', NULL, '2025-05-07 20:59:18'),
(33, 19, 'aceito', 'em_preparo', NULL, '2025-05-07 20:59:24'),
(34, 14, 'aceito', 'em_preparo', NULL, '2025-05-07 20:59:28'),
(35, 13, 'aceito', 'em_preparo', NULL, '2025-05-07 20:59:30'),
(36, 11, 'aceito', 'em_entrega', NULL, '2025-05-07 20:59:34'),
(37, 20, NULL, 'pendente', NULL, '2025-05-08 10:31:45');

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
  `qtd_produto` int(11) DEFAULT NULL,
  `tipo_calculo_preco` enum('maior','media') DEFAULT 'maior',
  `qtd_sabores` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_produto`
--

INSERT INTO `tb_produto` (`id_produto`, `id_categoria`, `nome_produto`, `slug_produto`, `valor_produto`, `imagem_produto`, `descricao_produto`, `produto_ativo`, `qtd_produto`, `tipo_calculo_preco`, `qtd_sabores`) VALUES
(1, 1, 'Pizza Mussarela', 'pizza-mussarela', 30.00, NULL, NULL, 1, NULL, 'maior', 1),
(2, 1, 'Pizza Calabresa', 'pizza-calabresa', 35.00, NULL, NULL, 1, NULL, 'maior', 1),
(3, 2, 'Refrigerante Dolly 2L', 'refri-dolly', 8.00, NULL, NULL, 1, NULL, 'maior', 1),
(4, 2, 'Refrigerante Sukita 2L', 'refri-sukita', 8.00, NULL, NULL, 1, NULL, 'maior', 1),
(5, 1, 'Pizza Quatro Queijos', 'pizza-quatro-queijos', 38.00, NULL, 'Mussarela, provolone, gorgonzola e parmesão.', 1, NULL, 'maior', 1),
(6, 1, 'Pizza Frango com Catupiry', 'pizza-frango-catupiry', 39.00, NULL, 'Frango desfiado com catupiry cremoso.', 1, NULL, 'maior', 1),
(7, 2, 'Refrigerante Dolly 2L', 'refri-dolly', 6.00, NULL, 'Dolly Guaraná 2L.', 1, NULL, 'maior', 1),
(8, 2, 'Refrigerante Pepsi 2L', 'refri-pepsi', 8.00, NULL, 'Pepsi 2L.', 1, NULL, 'maior', 1),
(11, 5, 'Açaí Simples 300ml', 'acai-simples', 10.00, NULL, 'Açaí natural, puro e saudável.', 1, NULL, 'maior', 1),
(12, 5, 'Açaí Especial 500ml', 'acai-especial', 18.00, NULL, 'Açaí com leite condensado, banana e granola.', 1, NULL, 'maior', 1),
(13, 1, 'Pizza Três Sabores', 'pizza-tres-sabores', 0.00, NULL, 'Escolha 3 sabores.', 1, NULL, 'maior', 3);

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
(1, 2, 4),
(2, 12, 7);

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
(4, 5, 1, 1, 0),
(5, 5, 2, 0, 0),
(6, 6, 1, 1, 1),
(7, 6, 2, 0, 2),
(12, 11, 4, 0, 0),
(13, 12, 3, 1, 1),
(14, 12, 4, 0, 3),
(15, 1, 1, 1, 1),
(16, 2, 1, 1, 1),
(21, 13, 1, 1, 1),
(23, 2, 2, 0, 3),
(28, 13, 2, 0, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_regras_frete`
--

CREATE TABLE `tb_regras_frete` (
  `id_regra` int(11) NOT NULL,
  `nome_regra` varchar(100) NOT NULL,
  `tipo_regra` enum('frete_gratis','desconto_valor','desconto_porcentagem') NOT NULL,
  `valor_minimo` decimal(10,2) DEFAULT NULL,
  `distancia_maxima` decimal(10,2) DEFAULT NULL,
  `valor_desconto` decimal(10,2) DEFAULT NULL,
  `dia_semana` enum('segunda','terça','quarta','quinta','sexta','sábado','domingo') DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_regras_frete`
--

INSERT INTO `tb_regras_frete` (`id_regra`, `nome_regra`, `tipo_regra`, `valor_minimo`, `distancia_maxima`, `valor_desconto`, `dia_semana`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Frete Grátis Acima de 32 reais', 'frete_gratis', 32.00, 100.00, NULL, 'terça', 1, '2025-04-26 19:58:19', '2025-05-06 17:59:54');

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
(3, 'Vegana', 'padrão', 1),
(4, 'Pizza com mais de 1 sabor', 'padrão', 1);

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
(5, 5, 2),
(6, 1, 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_subcategoria_produto`
--

CREATE TABLE `tb_subcategoria_produto` (
  `id_subcategoria_produto` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_subcategoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_subcategoria_produto`
--

INSERT INTO `tb_subcategoria_produto` (`id_subcategoria_produto`, `id_produto`, `id_subcategoria`) VALUES
(1, 1, 1),
(2, 1, 3),
(3, 2, 1),
(4, 5, 2),
(5, 5, 3),
(6, 6, 1),
(7, 6, 2),
(11, 11, 1),
(12, 12, 2),
(13, 13, 4);

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_usuario`
--

CREATE TABLE `tb_usuario` (
  `id_usuario` int(11) NOT NULL,
  `nome_usuario` varchar(100) NOT NULL,
  `telefone_usuario` varchar(20) NOT NULL,
  `senha_usuario` varchar(255) NOT NULL,
  `tipo_usuario` enum('cliente','funcionario','admin') DEFAULT 'cliente',
  `usuario_ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_usuario`
--

INSERT INTO `tb_usuario` (`id_usuario`, `nome_usuario`, `telefone_usuario`, `senha_usuario`, `tipo_usuario`, `usuario_ativo`, `criado_em`) VALUES
(1, 'Alves', '11961723132', '$2y$10$napjD/xTpHYZDrdkWqUTVe8oUP87g.Pf7ZVF4LuRwgxEFl3arUs7K', 'admin', 1, '2025-04-25 19:39:28'),
(2, 'testrew', '11111111111', '$2y$10$kLmniRToEnjRHEFGQk2iO.AkYVRiyvi39Mmxu1Oo92.sSU1ZDFlbe', 'cliente', 1, '2025-04-26 15:57:33'),
(3, 'Zina', '22222222222', '$2y$10$jHa9vmsuKPMK6EKlfSabJO9Jmppi7mgVsAUgWC3GQ8sXUT2FPxhUS', 'cliente', 1, '2025-04-26 16:02:33');

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
  ADD PRIMARY KEY (`id_campanha_brinde`),
  ADD KEY `fk_campanha_brinde_prod` (`id_produto_brinde`);

--
-- Índices de tabela `tb_campanha_produto_dia`
--
ALTER TABLE `tb_campanha_produto_dia`
  ADD PRIMARY KEY (`id_campanha`),
  ADD KEY `fk_camp_pd_prod` (`id_produto`);

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
-- Índices de tabela `tb_dados_loja`
--
ALTER TABLE `tb_dados_loja`
  ADD PRIMARY KEY (`id_loja`);

--
-- Índices de tabela `tb_endereco`
--
ALTER TABLE `tb_endereco`
  ADD PRIMARY KEY (`id_endereco`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `tb_forma_pgto`
--
ALTER TABLE `tb_forma_pgto`
  ADD PRIMARY KEY (`id_forma`);

--
-- Índices de tabela `tb_funcionario`
--
ALTER TABLE `tb_funcionario`
  ADD PRIMARY KEY (`id_funcionario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Índices de tabela `tb_item_adicional`
--
ALTER TABLE `tb_item_adicional`
  ADD PRIMARY KEY (`id_item_adicional`),
  ADD KEY `fk_item_adicional_pedido` (`id_item_pedido`),
  ADD KEY `fk_item_adicional_adicional` (`id_adicional`);

--
-- Índices de tabela `tb_item_pedido`
--
ALTER TABLE `tb_item_pedido`
  ADD PRIMARY KEY (`id_item_pedido`),
  ADD KEY `fk_itempedido_pedido` (`id_pedido`),
  ADD KEY `fk_itempedido_produto` (`id_produto`),
  ADD KEY `fk_itempedido_combo` (`id_combo`);

--
-- Índices de tabela `tb_item_pedido_sabor`
--
ALTER TABLE `tb_item_pedido_sabor`
  ADD PRIMARY KEY (`id_item_pedido_sabor`),
  ADD KEY `id_item_pedido` (`id_item_pedido`),
  ADD KEY `id_produto` (`id_produto`);

--
-- Índices de tabela `tb_pedido`
--
ALTER TABLE `tb_pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedido_cupom` (`id_cupom`),
  ADD KEY `fk_pedido_usuario` (`id_usuario`),
  ADD KEY `fk_pedido_funcionario` (`id_funcionario`),
  ADD KEY `fk_pedido_entregador` (`id_entregador`);

--
-- Índices de tabela `tb_pedido_status_log`
--
ALTER TABLE `tb_pedido_status_log`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_pedido` (`id_pedido`);

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
-- Índices de tabela `tb_regras_frete`
--
ALTER TABLE `tb_regras_frete`
  ADD PRIMARY KEY (`id_regra`);

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
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_subcategoria` (`id_subcategoria`);

--
-- Índices de tabela `tb_subcategoria_produto`
--
ALTER TABLE `tb_subcategoria_produto`
  ADD PRIMARY KEY (`id_subcategoria_produto`),
  ADD KEY `id_produto` (`id_produto`),
  ADD KEY `id_subcategoria` (`id_subcategoria`);

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
-- Índices de tabela `tb_usuario`
--
ALTER TABLE `tb_usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `telefone_usuario` (`telefone_usuario`);

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
-- AUTO_INCREMENT de tabela `tb_dados_loja`
--
ALTER TABLE `tb_dados_loja`
  MODIFY `id_loja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tb_endereco`
--
ALTER TABLE `tb_endereco`
  MODIFY `id_endereco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `tb_forma_pgto`
--
ALTER TABLE `tb_forma_pgto`
  MODIFY `id_forma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `tb_funcionario`
--
ALTER TABLE `tb_funcionario`
  MODIFY `id_funcionario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_item_adicional`
--
ALTER TABLE `tb_item_adicional`
  MODIFY `id_item_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `tb_item_pedido`
--
ALTER TABLE `tb_item_pedido`
  MODIFY `id_item_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `tb_item_pedido_sabor`
--
ALTER TABLE `tb_item_pedido_sabor`
  MODIFY `id_item_pedido_sabor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `tb_pedido`
--
ALTER TABLE `tb_pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `tb_pedido_status_log`
--
ALTER TABLE `tb_pedido_status_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de tabela `tb_produto`
--
ALTER TABLE `tb_produto`
  MODIFY `id_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `tb_produto_adicional_incluso`
--
ALTER TABLE `tb_produto_adicional_incluso`
  MODIFY `id_produto_adicional_incluso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `tb_produto_tipo_adicional`
--
ALTER TABLE `tb_produto_tipo_adicional`
  MODIFY `id_produto_tipo_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `tb_regras_frete`
--
ALTER TABLE `tb_regras_frete`
  MODIFY `id_regra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria`
--
ALTER TABLE `tb_subcategoria`
  MODIFY `id_subcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria_categoria`
--
ALTER TABLE `tb_subcategoria_categoria`
  MODIFY `id_subcategoria_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria_produto`
--
ALTER TABLE `tb_subcategoria_produto`
  MODIFY `id_subcategoria_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- AUTO_INCREMENT de tabela `tb_usuario`
--
ALTER TABLE `tb_usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `tb_adicional`
--
ALTER TABLE `tb_adicional`
  ADD CONSTRAINT `fk_adicional_tipo` FOREIGN KEY (`id_tipo_adicional`) REFERENCES `tb_tipo_adicional` (`id_tipo_adicional`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_campanha_brinde`
--
ALTER TABLE `tb_campanha_brinde`
  ADD CONSTRAINT `fk_campanha_brinde_prod` FOREIGN KEY (`id_produto_brinde`) REFERENCES `tb_produto` (`id_produto`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_campanha_produto_dia`
--
ALTER TABLE `tb_campanha_produto_dia`
  ADD CONSTRAINT `fk_camp_pd_prod` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_combo_item`
--
ALTER TABLE `tb_combo_item`
  ADD CONSTRAINT `fk_comboitem_combo` FOREIGN KEY (`id_combo`) REFERENCES `tb_combo_promocional` (`id_combo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comboitem_prod` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_endereco`
--
ALTER TABLE `tb_endereco`
  ADD CONSTRAINT `fk_endereco_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_endereco_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tb_funcionario`
--
ALTER TABLE `tb_funcionario`
  ADD CONSTRAINT `fk_funcionario_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuario` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_funcionario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tb_item_adicional`
--
ALTER TABLE `tb_item_adicional`
  ADD CONSTRAINT `fk_item_adicional_adicional` FOREIGN KEY (`id_adicional`) REFERENCES `tb_adicional` (`id_adicional`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_item_adicional_pedido` FOREIGN KEY (`id_item_pedido`) REFERENCES `tb_item_pedido` (`id_item_pedido`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_item_pedido`
--
ALTER TABLE `tb_item_pedido`
  ADD CONSTRAINT `fk_itempedido_combo` FOREIGN KEY (`id_combo`) REFERENCES `tb_combo_promocional` (`id_combo`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itempedido_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `tb_pedido` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itempedido_produto` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_item_pedido_sabor`
--
ALTER TABLE `tb_item_pedido_sabor`
  ADD CONSTRAINT `fk_sabor_itempedido` FOREIGN KEY (`id_item_pedido`) REFERENCES `tb_item_pedido` (`id_item_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sabor_produto` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_item_pedido_sabor_ibfk_1` FOREIGN KEY (`id_item_pedido`) REFERENCES `tb_item_pedido` (`id_item_pedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_item_pedido_sabor_ibfk_2` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tb_pedido`
--
ALTER TABLE `tb_pedido`
  ADD CONSTRAINT `fk_pedido_entregador` FOREIGN KEY (`id_entregador`) REFERENCES `tb_funcionario` (`id_funcionario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedido_funcionario` FOREIGN KEY (`id_funcionario`) REFERENCES `tb_funcionario` (`id_funcionario`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tb_usuario` (`id_usuario`) ON DELETE SET NULL;

--
-- Restrições para tabelas `tb_pedido_status_log`
--
ALTER TABLE `tb_pedido_status_log`
  ADD CONSTRAINT `tb_pedido_status_log_ibfk_1` FOREIGN KEY (`id_pedido`) REFERENCES `tb_pedido` (`id_pedido`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tb_produto`
--
ALTER TABLE `tb_produto`
  ADD CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categoria` (`id_categoria`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_produto_adicional_incluso`
--
ALTER TABLE `tb_produto_adicional_incluso`
  ADD CONSTRAINT `fk_prodinc_adicional` FOREIGN KEY (`id_adicional`) REFERENCES `tb_adicional` (`id_adicional`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prodinc_produto` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_produto_tipo_adicional`
--
ALTER TABLE `tb_produto_tipo_adicional`
  ADD CONSTRAINT `fk_prodtipo_produto` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prodtipo_tipo` FOREIGN KEY (`id_tipo_adicional`) REFERENCES `tb_tipo_adicional` (`id_tipo_adicional`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `tb_subcategoria_categoria`
--
ALTER TABLE `tb_subcategoria_categoria`
  ADD CONSTRAINT `fk_subcatcat_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categoria` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subcatcat_subcategoria` FOREIGN KEY (`id_subcategoria`) REFERENCES `tb_subcategoria` (`id_subcategoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_subcategoria_categoria_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categoria` (`id_categoria`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_subcategoria_categoria_ibfk_2` FOREIGN KEY (`id_subcategoria`) REFERENCES `tb_subcategoria` (`id_subcategoria`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tb_subcategoria_produto`
--
ALTER TABLE `tb_subcategoria_produto`
  ADD CONSTRAINT `fk_subcatprod_produto` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subcatprod_subcategoria` FOREIGN KEY (`id_subcategoria`) REFERENCES `tb_subcategoria` (`id_subcategoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_subcategoria_produto_ibfk_1` FOREIGN KEY (`id_produto`) REFERENCES `tb_produto` (`id_produto`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_subcategoria_produto_ibfk_2` FOREIGN KEY (`id_subcategoria`) REFERENCES `tb_subcategoria` (`id_subcategoria`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tb_tipo_adicional_categoria`
--
ALTER TABLE `tb_tipo_adicional_categoria`
  ADD CONSTRAINT `fk_tipocat_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `tb_categoria` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tipocat_tipo` FOREIGN KEY (`id_tipo_adicional`) REFERENCES `tb_tipo_adicional` (`id_tipo_adicional`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
