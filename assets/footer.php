    </main>

    <footer class="bg-primary text-primary-content mt-10 p-6">
      <div class="container mx-auto text-center space-y-4">
        <div class="flex justify-center space-x-6">
          <?php if ($whatsapp): ?>
            <a href="https://wa.me/55<?= htmlspecialchars($whatsapp, ENT_QUOTES) ?>"
              target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
              <i class="fab fa-whatsapp text-2xl hover:text-green-400"></i>
            </a>
          <?php endif; ?>
          <?php if ($instagram): ?>
            <a href="https://instagram.com/<?= htmlspecialchars(ltrim($instagram, '@'), ENT_QUOTES) ?>"
              target="_blank" rel="noopener noreferrer" aria-label="Instagram">
              <i class="fab fa-instagram text-2xl hover:text-pink-400"></i>
            </a>
          <?php endif; ?>
        </div>
        <?php if (!empty($enderecoLoja)): ?>
          <p><?= htmlspecialchars($enderecoLoja, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p>© <?= date('Y') ?> <?= htmlspecialchars($nomeLoja, ENT_QUOTES, 'UTF-8') ?>. Todos os direitos reservados.</p>
      </div>
    </footer>

    <div
      id="cookie-banner"
      class="fixed bottom-0 inset-x-0 bg-base-100 border-t shadow-lg p-4 flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0 z-50 hidden">
      <p class="text-sm text-gray-700">
        Usamos cookies para melhorar sua experiência. Ao continuar navegando, você concorda com nossa
        <a href="/politica-de-privacidade.php" class="underline text-primary">Política de Privacidade</a>.
      </p>
      <button
        id="accept-cookies"
        class="btn btn-primary btn-sm">
        Aceitar
      </button>
    </div>

    <!-- Scripts JS no fim do body -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      $(document).ready(function() {
        function doLogout() {
          $.post('logout.php', {}, function(response) {
              if (response.status === 'ok') {
                Swal.fire({
                  title: 'Deslogado!',
                  text: 'Você saiu da sua conta.',
                  icon: 'success',
                  confirmButtonText: 'OK'
                }).then(() => {
                  window.location.href = 'index.php';
                });
              } else {
                Swal.fire('Erro', 'Não foi possível sair.', 'error');
              }
            }, 'json')
            .fail(() => {
              Swal.fire('Erro', 'Erro na comunicação com o servidor.', 'error');
            });
        }

        $('#btnLogout, #btnLogoutMobile').click(function(e) {
          e.preventDefault();
          doLogout();
        });

        $('#telefone').mask('(00) 00000-0000');
        $('#cep').mask('00000-000');
        $('#pf-valor, .currency').mask('#.##0,00', {
          reverse: true
        });
      });

      (function() {
        const banner = document.getElementById('cookie-banner');
        const btn = document.getElementById('accept-cookies');

        // Função para ler um cookie
        function getCookie(name) {
          const v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
          return v ? v.pop() : null;
        }

        // Função para criar um cookie
        function setCookie(name, value, days) {
          const expires = new Date(Date.now() + days * 864e5).toUTCString();
          document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/';
        }

        // Se não aceitou ainda, mostra banner
        if (!getCookie('cookiesAccepted')) {
          banner.classList.remove('hidden');
        }

        // Ao clicar em “Aceitar”
        btn.addEventListener('click', function() {
          setCookie('cookiesAccepted', 'true', 365);
          banner.classList.add('hidden');
        });
      })();
    </script>
    </body>

    </html>