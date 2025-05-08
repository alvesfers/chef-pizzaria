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
        $('#pf-valor.currency').mask('#.##0,00', {
          reverse: true
        });
      });
    </script>
    </body>

    </html>