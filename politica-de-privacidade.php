<?php
// politica-de-privacidade.php

// inclui cabeçalho com <head>, menu, links CSS/JS etc.
include_once 'assets/header.php';
?>

<main class="container mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold mb-4">Política de Privacidade</h1>

    <p>
        Esta Política de Privacidade descreve como coletamos, usamos, armazenamos e protegemos os dados pessoais dos usuários do nosso sistema de pedidos online.
        Estamos comprometidos com a transparência e com o cumprimento da Lei Geral de Proteção de Dados (Lei nº 13.709/2018 – LGPD).
    </p>

    <h2 class="text-xl font-semibold mt-6 mb-2">1. Dados Coletados</h2>
    <ul class="list-disc list-inside">
        <li><strong>Dados cadastrais:</strong> nome, e-mail, telefone, CPF (quando houver).</li>
        <li><strong>Endereços de entrega:</strong> CEP, rua, número, complemento, bairro, cidade e ponto de referência.</li>
        <li><strong>Dados de pedido:</strong> itens selecionados, sabores, adicionais, quantidade, valor unitário e total.</li>
        <li><strong>Dados de geolocalização:</strong> latitude/longitude obtidas via Google Maps API para cálculo de frete.</li>
        <li><strong>Informações de sessão e cookies:</strong> identificação de sessão PHP, preferências e carrinho de compras.</li>
        <li><strong>Comunicação via WhatsApp:</strong> número de telefone para envio automático de pedido (quando autorizado).</li>
        <li><strong>Dados de pagamento:</strong> forma de pagamento escolhida (não armazenamos dados de cartão de crédito).</li>
        <li><strong>Dados de acesso:</strong> endereço IP, data e hora de acesso.</li>
    </ul>

    <h2 class="text-xl font-semibold mt-6 mb-2">2. Finalidades do Tratamento</h2>
    <ul class="list-disc list-inside">
        <li>Cadastro de usuário e autenticação.</li>
        <li>Processamento e confirmação de pedidos.</li>
        <li>Cálculo de frete e estimativa de entrega.</li>
        <li>Envio de notificações e mensagens via WhatsApp.</li>
        <li>Aprimoramento do serviço e prevenção a fraudes.</li>
        <li>Manutenção da sessão de login e preferências de navegação.</li>
        <li>Atendimento a obrigações legais e fiscais.</li>
    </ul>

    <h2 class="text-xl font-semibold mt-6 mb-2">3. Compartilhamento de Dados</h2>
    <p>
        Seus dados não são vendidos. Podemos compartilhar suas informações com:
    </p>
    <ul class="list-disc list-inside">
        <li><strong>Google:</strong> uso de Maps API para geocoding (somente coordenadas).</li>
        <li><strong>Transportadoras parceiras:</strong> apenas dados necessários para entrega.</li>
        <li><strong>Autoridades legais:</strong> quando exigido por lei ou ordem judicial.</li>
    </ul>

    <h2 class="text-xl font-semibold mt-6 mb-2">4. Cookies e Tecnologias Semelhantes</h2>
    <p>
        Utilizamos cookies estritamente necessários para:
    </p>
    <ul class="list-disc list-inside">
        <li>Manter sessão de usuário autenticado.</li>
        <li>Armazenar carrinho de compras.</li>
        <li>Preferências de idioma e exibição.</li>
    </ul>
    <p>
        Não usamos cookies de perfilamento de terceiros.
    </p>

    <h2 class="text-xl font-semibold mt-6 mb-2">5. Segurança dos Dados</h2>
    <p>
        Adotamos medidas técnicas e administrativas para proteger seus dados contra acesso não autorizado, alteração ou destruição. Todos os dados sensíveis (como senhas) são armazenados de forma criptografada.
    </p>

    <h2 class="text-xl font-semibold mt-6 mb-2">6. Direitos dos Titulares</h2>
    <p>
        Você pode, a qualquer momento:
    </p>
    <ul class="list-disc list-inside">
        <li>Confirmar a existência de tratamento.</li>
        <li>Acessar, corrigir ou excluir seus dados.</li>
        <li>Revogar consentimento.</li>
        <li>Portar seus dados para outro fornecedor.</li>
        <li>Solicitar anonimização, bloqueio ou eliminação.</li>
    </ul>
    <p>
        Para exercer seus direitos, envie e-mail para <a href="mailto:<?= $emailLoja ?>"><?= $emailLoja ?></a>.
    </p>

    <h2 class="text-xl font-semibold mt-6 mb-2">7. Retenção de Dados</h2>
    <p>
        Mantemos seus dados pelo período necessário para cumprir finalidades descritas ou enquanto houver relação contratual. Após esse prazo, serão anonimizados ou excluídos de nossos sistemas.
    </p>

    <h2 class="text-xl font-semibold mt-6 mb-2">8. Alterações nesta Política</h2>
    <p>
        Podemos atualizar esta Política de Privacidade a qualquer momento. Publicaremos a versão mais recente nesta página, indicando a data da última revisão.
    </p>

    <p class="mt-8">
        <strong>Última atualização:</strong> 10 de maio de 2025
    </p>
</main>

<?php
// inclui rodapé com informações de contato, links de redes sociais, scripts JS etc.
include_once 'assets/footer.php';
