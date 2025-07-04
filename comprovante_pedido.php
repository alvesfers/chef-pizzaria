<?php
require_once 'assets/conexao.php';
$idPedido = $_GET['id'] ?? null;

if (!$idPedido) {
    exit('Pedido não encontrado.');
}

$stmt = $pdo->prepare("SELECT * FROM tb_pedido WHERE id_pedido = ?");
$stmt->execute([$idPedido]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) exit('Pedido inválido.');

$stmtItens = $pdo->prepare("SELECT * FROM tb_item_pedido WHERE id_pedido = ?");
$stmtItens->execute([$idPedido]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

$dadosLoja = $pdo->query("SELECT * FROM tb_dados_loja LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$totalItens = 0;
foreach ($itens as $i) $totalItens += $i['quantidade'] * $i['valor_unitario'];

$frete = floatval($pedido['valor_frete'] ?? 0);
$desconto = floatval($pedido['desconto_aplicado'] ?? 0);
$total = max(0, $totalItens + $frete - $desconto);

$idEndereco = $pedido['endereco'] ?? null;
$enderecoCompleto = '';

if ($idEndereco) {
    $stmtEndereco = $pdo->prepare("SELECT * FROM tb_endereco WHERE id_endereco = ?");
    $stmtEndereco->execute([$idEndereco]);
    $endereco = $stmtEndereco->fetch(PDO::FETCH_ASSOC);

    if ($endereco) {
        $enderecoCompleto = "{$endereco['rua']}, {$endereco['numero']}";
        if (!empty($endereco['complemento'])) {
            $enderecoCompleto .= " ({$endereco['complemento']})";
        }
        $enderecoCompleto .= " — {$endereco['bairro']}";
        if (!empty($endereco['cep'])) {
            $cepFormatado = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $endereco['cep']);
            $enderecoCompleto .= " — CEP: $cepFormatado";
        }
    }
}

$stmtPagamento = $pdo->prepare("SELECT * FROM tb_forma_pgto WHERE id_forma = ?");
$stmtPagamento->execute([$pedido['forma_pagamento']]);
$pagamento = $stmtPagamento->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Comprovante Pedido #<?= $pedido['id_pedido'] ?></title>
    <style>
        * {
            font-family: monospace;
            font-size: 12px;
        }

        body {
            margin: 0;
            padding: 0 5px;
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        img.logo {
            max-height: 60px;
            margin: 0 auto 5px;
            display: block;
        }

        ul {
            padding-left: 15px;
            margin: 4px 0;
        }

        .item {
            margin-bottom: 10px;
        }

        @media print {
            button {
                display: none;
            }
        }

        @page {
            size: 80mm auto;
            margin: 3px 8px;
        }
    </style>
</head>

<body onload="window.print()">
    <div class="center">
        <strong><?= strtoupper($dadosLoja['nome_loja'] ?? 'PIZZARIA') ?></strong><br>
        <strong><?= strtoupper($dadosLoja['whatsapp']) ?></strong><br>
        <?= $dadosLoja['endereco_completo'] ?? '' ?>
    </div>

    <div class="line"></div>

    <p>
        Pedido: #<?= $pedido['id_pedido'] ?><br>
        Data: <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?><br>
        Cliente: <?= htmlspecialchars($pedido['nome_cliente']) ?><br>
        Tipo Entrega: <?= ucfirst($pedido['tipo_entrega']) ?><br>
        <?php if ($pedido['tipo_entrega'] === 'entrega' && !empty($enderecoCompleto)): ?>
            Endereço: <?= htmlspecialchars($enderecoCompleto) ?><br>
        <?php endif; ?>

    </p>

    <div class="line"></div>

    <strong>ITENS:</strong><br>
    <?php foreach ($itens as $item): ?>
        <div class="item">
            <?= $item['quantidade'] ?>x <?= htmlspecialchars($item['nome_exibicao']) ?>
            R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?><br>

            <?php
            $stmtSabores = $pdo->prepare("SELECT ps.*, p.nome_produto FROM tb_item_pedido_sabor ps JOIN tb_produto p ON ps.id_produto = p.id_produto WHERE id_item_pedido = ?");
            $stmtSabores->execute([$item['id_item_pedido']]);
            $sabores = $stmtSabores->fetchAll(PDO::FETCH_ASSOC);

            $stmtAdd = $pdo->prepare("SELECT * FROM tb_item_adicional WHERE id_item_pedido = ?");
            $stmtAdd->execute([$item['id_item_pedido']]);
            $adicionais = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if ($sabores): ?>
                Sabores:
                <ul>
                    <?php foreach ($sabores as $sabor): ?>
                        <li><?= htmlspecialchars($sabor['nome_produto']) ?> (<?= $sabor['proporcao'] ?>%)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($adicionais): ?>
                Adicionais:
                <ul>
                    <?php foreach ($adicionais as $add): ?>
                        <li><?= htmlspecialchars($add['nome_adicional']) ?>
                            <?php if ($add['valor_adicional'] > 0): ?>
                                - R$ <?= number_format($add['valor_adicional'], 2, ',', '.') ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="line"></div>

    Subtotal: R$ <?= number_format($totalItens, 2, ',', '.') ?><br>
    Frete: R$ <?= number_format($frete, 2, ',', '.') ?><br>
    Desconto: R$ <?= number_format($desconto, 2, ',', '.') ?><br>
    <strong>TOTAL: R$ <?= number_format($total, 2, ',', '.') ?></strong><br>

    <div class="line"></div>
    <p class="center">Forma de pagamento:<br><?= htmlspecialchars($pagamento['nome_pgto']) ?></p>
    <div class="line"></div>
    <div class="center">
        <img src="assets/images/logo.png" class="logo" alt="Logo" style="filter: contrast(200%) brightness(0%);">
    </div>
    <div class="line"></div>

    <script>
        window.onafterprint = () => window.close();
    </script>
</body>

</body>

</html>