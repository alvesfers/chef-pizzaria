-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/05/2025 às 23:06
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
(1, 1, 'Cheddar', 2.00, 1),
(2, 1, 'Catupiry', 2.00, 1),
(3, 1, 'Alho Frito', 5.00, 1),
(4, 2, 'Leite Condensado', 1.00, 1),
(5, 2, 'Paçoca', 1.00, 1),
(6, 2, 'Confete', 1.00, 1),
(7, 3, 'Morango', 2.00, 1),
(8, 3, 'Banana', 2.00, 1),
(9, 3, 'Kiwi', 2.00, 0),
(10, 4, 'Nuttela', 5.00, 1),
(11, 5, 'Frango', 3.00, 1),
(12, 5, 'Catupiry', 2.00, 1),
(13, 6, 'Adicional 1', 1.00, 1),
(14, 6, 'Adicional 2', 5.00, 1);

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
(2, 'Açais', 0, 1, 0),
(3, 'Bebidas', 0, 1, 0),
(4, 'Fogazzas', 0, 1, 0),
(5, 'Categoria de exemplo', 1, 1, 0),
(6, 'Brotos', 1, 1, 0),
(7, 'Calzones', 1, 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tb_categoria_relacionada`
--

CREATE TABLE `tb_categoria_relacionada` (
  `id_categoria` int(11) NOT NULL,
  `id_categoria_relacionada` int(11) NOT NULL,
  `label_relacionada` varchar(50) NOT NULL,
  `obrigatorio` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_categoria_relacionada`
--

INSERT INTO `tb_categoria_relacionada` (`id_categoria`, `id_categoria_relacionada`, `label_relacionada`, `obrigatorio`) VALUES
(1, 4, 'Fogazza', 0),
(1, 6, 'Broto', 0),
(1, 7, 'Calzone', 0),
(5, 1, 'Pizza', 0);

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
(1, 'CUPAOZAO', '10% de desconto acima de R$30', 'porcentagem', 10.00, 0, NULL, 30.00, '2025-04-24', NULL, 1);

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
  `tempo_retirada` int(11) DEFAULT 20,
  `usar_horarios` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_dados_loja`
--

INSERT INTO `tb_dados_loja` (`id_loja`, `nome_loja`, `cep`, `endereco_completo`, `logo`, `tema`, `instagram`, `whatsapp`, `google`, `preco_base`, `preco_km`, `limite_entrega`, `tempo_entrega`, `tempo_retirada`, `usar_horarios`) VALUES
(1, 'Fernando Pizzas', '04434150', 'Rua Germano Gottsfritz, 431', NULL, 'light', 'alvesferz', '11961723132', 'AIzaSyDg5xiBHnQKhUvwCSjOY2YJ4SN5L0wEj78', 1.00, 1.00, 5.00, 45, 20, 1);

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
(11, 1, 'sadas', '04430150', 'Avenida Pio XI', '100', '', 'Vila Missionária', '', 0, 1, '2025-05-15 19:59:14'),
(12, 1, 'sdasd', '04434150', 'Rua Germano Gottsfritz', '50', '', 'Jardim Uberaba', '', 0, 1, '2025-05-15 20:00:55'),
(13, 1, 'zxsa', '04434150', 'Rua Germano Gottsfritz', '20', '', 'Jardim Uberaba', '', 0, 1, '2025-05-15 20:02:19'),
(14, 1, 'ASDSas', '04334150', 'Rua Hildebrando Siqueira', '10', '', 'Vila Fachini', '', 1, 1, '2025-05-15 20:04:33'),
(18, 8, 'Barbearia', '04674225', 'Avenida Sargento Geraldo Sant\'Ana', '563', '', 'Jardim Taquaral', '', 0, 1, '2025-05-23 15:37:23'),
(19, 8, 'ss', '04434150', 'Rua Germano Gottsfritz', '422', '', 'Jardim Uberaba', '', 1, 1, '2025-05-23 16:54:00');

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
(5, 'Dinheiro', 1, 0, NULL, NULL, '2025-05-23 15:40:27', '2025-05-23 15:40:27');

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
-- Estrutura para tabela `tb_horario_atendimento`
--

CREATE TABLE `tb_horario_atendimento` (
  `id_horario` int(11) NOT NULL,
  `dia_semana` enum('segunda','terça','quarta','quinta','sexta','sábado','domingo') NOT NULL,
  `hora_abertura` time NOT NULL,
  `hora_fechamento` time NOT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_horario_atendimento`
--

INSERT INTO `tb_horario_atendimento` (`id_horario`, `dia_semana`, `hora_abertura`, `hora_fechamento`, `ativo`) VALUES
(1, 'segunda', '18:00:00', '23:59:59', 1),
(2, 'terça', '18:00:00', '23:59:59', 1),
(3, 'quarta', '18:00:00', '23:59:59', 1),
(4, 'quinta', '08:00:00', '00:59:59', 1),
(5, 'sexta', '08:00:00', '23:59:59', 1),
(6, 'sábado', '18:00:00', '23:59:59', 1),
(7, 'domingo', '00:00:00', '00:00:00', 0);

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
(1, 4, 13, 'Adicional 1', 1.00),
(2, 4, 14, 'Adicional 2', 5.00),
(3, 5, 13, 'Adicional 1', 1.00),
(5, 15, 14, 'Adicional 2 — R$5.00', 5.00);

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
-- Estrutura para tabela `tb_item_pedido_sabor`
--

CREATE TABLE `tb_item_pedido_sabor` (
  `id_item_pedido_sabor` int(11) NOT NULL,
  `id_item_pedido` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `proporcao` decimal(5,2) DEFAULT 0.50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `origem` varchar(100) NOT NULL,
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
  `qtd_produto` int(11) DEFAULT -1,
  `tipo_calculo_preco` enum('maior','media') DEFAULT 'maior',
  `qtd_sabores` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tb_produto`
--

INSERT INTO `tb_produto` (`id_produto`, `id_categoria`, `nome_produto`, `slug_produto`, `valor_produto`, `imagem_produto`, `descricao_produto`, `produto_ativo`, `qtd_produto`, `tipo_calculo_preco`, `qtd_sabores`) VALUES
(26, 5, 'Produto 1', NULL, 15.00, NULL, 'Produto comum com controle de estoque', 1, 41, 'maior', 1),
(27, 5, 'Produto 2', NULL, 10.00, NULL, 'Produto com mais de um sabor (pega as opções que tem na categoria e NÃO estão na subcategoria Mais sabores)', 1, -1, 'maior', 2),
(28, 5, 'Produto 3', NULL, 20.00, NULL, 'Produto com adicionais inclusos (por tipo de adicional) a escolha do usuario', 1, -1, 'maior', 1),
(29, 5, 'Produto 4', NULL, 20.00, NULL, 'Produto com adicionais inclusos (por tipo de adicional) pré definido pelo cadastro', 1, -1, 'maior', 1);

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
(9, 29, 13);

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
(67, 26, 6, 0, 0),
(69, 27, 6, 0, 0),
(70, 28, 6, 0, 1),
(71, 29, 6, 0, 1);

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
(1, 'Frete Grátis Acima de 32 reais', 'frete_gratis', 32.00, 100.00, NULL, 'sexta', 1, '2025-04-26 19:58:19', '2025-05-15 20:24:12');

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
(1, 'Salgada', '', 1),
(2, 'Doce', '', 1),
(3, 'Especial', '', 1),
(4, 'Vegana', '', 1),
(5, 'Vegetariana', '', 1),
(6, 'Simples', '', 1),
(7, 'Alcóolicas', '', 1),
(8, 'Não Alcóolicas', '', 1),
(9, 'Trufados', '', 1),
(10, 'Mais sabores', '', 1),
(11, 'Subcategoria de exemplo 1', '', 1),
(12, 'Subcategoria de exemplo 2', '', 1);

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
(9, 3, 7),
(10, 3, 8),
(11, 2, 3),
(12, 2, 6),
(13, 2, 9),
(14, 2, 2),
(39, 4, 2),
(40, 4, 3),
(41, 4, 10),
(42, 4, 1),
(43, 4, 4),
(44, 4, 5),
(47, 5, 10),
(48, 5, 11),
(49, 5, 12),
(50, 6, 2),
(51, 6, 3),
(52, 6, 10),
(53, 6, 1),
(54, 6, 6),
(55, 6, 4),
(56, 6, 5),
(57, 7, 2),
(58, 7, 3),
(59, 7, 10),
(60, 7, 1),
(61, 7, 6),
(62, 7, 4),
(63, 7, 5),
(85, 1, 2),
(86, 1, 3),
(87, 1, 10),
(88, 1, 1),
(89, 1, 6),
(90, 1, 4),
(91, 1, 5);

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
(97, 27, 10),
(98, 27, 11),
(99, 28, 12),
(100, 29, 12),
(103, 26, 11);

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
(1, 'Bordas', 0, 0, 0, 1),
(2, 'Complemento', 0, 1, 0, 1),
(3, 'Fruta', 0, 0, 0, 1),
(4, 'Creme', 0, 0, 0, 1),
(5, 'Recheio Extra', 0, 0, 0, 1),
(6, 'Tipo Adicional Exemplo 1', 0, 0, 0, 1);

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
(6, 2, 2, 0),
(7, 2, 4, 0),
(8, 2, 3, 0),
(17, 4, 1, 0),
(18, 4, 5, 0),
(20, 5, 6, 0),
(21, 6, 1, 0),
(22, 6, 5, 0),
(23, 7, 1, 0),
(24, 7, 5, 0),
(31, 1, 1, 0),
(32, 1, 5, 0);

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
(7, 'Pedro', '11972332906', '$2y$10$gGZou9G0NYbR/bE0s83LgeFyOFWWghl0BcWT0Qx97QmIreg6poLIm', 'cliente', 1, '2025-05-17 00:01:19'),
(8, 'Fernando', '11222222222', '$2y$10$X3EZwDsEv.VGin7RnR7sKukacj3907m3ndwBcgBWaK2W7xLP.gCV6', 'cliente', 1, '2025-05-23 15:35:50');

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
-- Índices de tabela `tb_categoria_relacionada`
--
ALTER TABLE `tb_categoria_relacionada`
  ADD PRIMARY KEY (`id_categoria`,`id_categoria_relacionada`);

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
-- Índices de tabela `tb_horario_atendimento`
--
ALTER TABLE `tb_horario_atendimento`
  ADD PRIMARY KEY (`id_horario`);

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
  MODIFY `id_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `tb_campanha_brinde`
--
ALTER TABLE `tb_campanha_brinde`
  MODIFY `id_campanha_brinde` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_campanha_produto_dia`
--
ALTER TABLE `tb_campanha_produto_dia`
  MODIFY `id_campanha` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_categoria`
--
ALTER TABLE `tb_categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id_endereco` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `tb_forma_pgto`
--
ALTER TABLE `tb_forma_pgto`
  MODIFY `id_forma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tb_funcionario`
--
ALTER TABLE `tb_funcionario`
  MODIFY `id_funcionario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_horario_atendimento`
--
ALTER TABLE `tb_horario_atendimento`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `tb_item_adicional`
--
ALTER TABLE `tb_item_adicional`
  MODIFY `id_item_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tb_item_pedido`
--
ALTER TABLE `tb_item_pedido`
  MODIFY `id_item_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_item_pedido_sabor`
--
ALTER TABLE `tb_item_pedido_sabor`
  MODIFY `id_item_pedido_sabor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_pedido`
--
ALTER TABLE `tb_pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_pedido_status_log`
--
ALTER TABLE `tb_pedido_status_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tb_produto`
--
ALTER TABLE `tb_produto`
  MODIFY `id_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de tabela `tb_produto_adicional_incluso`
--
ALTER TABLE `tb_produto_adicional_incluso`
  MODIFY `id_produto_adicional_incluso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `tb_produto_tipo_adicional`
--
ALTER TABLE `tb_produto_tipo_adicional`
  MODIFY `id_produto_tipo_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT de tabela `tb_regras_frete`
--
ALTER TABLE `tb_regras_frete`
  MODIFY `id_regra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria`
--
ALTER TABLE `tb_subcategoria`
  MODIFY `id_subcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria_categoria`
--
ALTER TABLE `tb_subcategoria_categoria`
  MODIFY `id_subcategoria_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT de tabela `tb_subcategoria_produto`
--
ALTER TABLE `tb_subcategoria_produto`
  MODIFY `id_subcategoria_produto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT de tabela `tb_tipo_adicional`
--
ALTER TABLE `tb_tipo_adicional`
  MODIFY `id_tipo_adicional` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tb_tipo_adicional_categoria`
--
ALTER TABLE `tb_tipo_adicional_categoria`
  MODIFY `id_tipo_adicional_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `tb_usuario`
--
ALTER TABLE `tb_usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
