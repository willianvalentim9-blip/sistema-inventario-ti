</main>

        <?php if (isLoggedIn() && !isset($hide_sidebar)): ?>
            <footer class="bg-light text-secondary mt-auto py-3 text-center no-print" style="font-size: 0.9rem;">
                <div class="container-fluid">
                    <p class="mb-0">
                        © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Desenvolvido para controle de estoque.
                    </p>
                </div>
            </footer>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalLabel">Ação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="actionModalBody">
                <div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scannerModalLabel"><i class="fas fa-barcode me-2"></i>Scanner de Código</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="scannerIframe" src="" style="width: 100%; height: 450px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quickStockInModal" tabindex="-1" aria-labelledby="quickStockInModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="quickStockInModalLabel"><i class="fas fa-bolt me-2"></i> Entrada Expressa de Produto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="quickStockInForm">
          <div id="quickStockInMessage" class="mb-3"></div>
          <p class="text-muted small">Use o leitor de código de barras no campo abaixo para identificar o produto e informar a quantidade a ser adicionada ao estoque.</p>
          <div class="mb-3">
            <label for="product_code" class="form-label">Código (Barra, QR, Serial)</label>
            <div class="input-group">
                <input type="text" class="form-control" id="product_code" name="product_code" required autofocus>
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#scannerModal" data-target-input="product_code">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
          </div>
          <div class="mb-3">
            <label for="add_quantity" class="form-label">Quantidade a Adicionar</label>
            <input type="number" class="form-control" id="add_quantity" name="add_quantity" value="1" min="1" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" form="quickStockInForm" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Adicionar ao Estoque</button>
      </div>
    </div>
  </div>
</div>


<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/custom.js"></script>
<script src="js/enhanced_ui.js"></script>

<?php if (isset($additional_js)): ?>
    <?php foreach ($additional_js as $js_file): ?>
        <script src="<?php echo $js_file; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    // Variável para controlar se a animação está a decorrer
    let isSidebarTransitioning = false;

    function toggleSidebar() {
        if (isSidebarTransitioning) return;
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            isSidebarTransitioning = true;
            if (window.innerWidth <= 991.98) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            }
            setTimeout(() => { isSidebarTransitioning = false; }, 300);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Lógica da sidebar
        const sidebar = document.getElementById('sidebar');
        if (sidebar && window.innerWidth > 991.98) {
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
        }

        // --- SCRIPT GLOBAL E CORRIGIDO PARA O SCANNER MODAL ---
        const scannerModalEl = document.getElementById('scannerModal');
        const actionModalEl = document.getElementById('actionModal');

        if (scannerModalEl && actionModalEl) {
            const scannerIframe = document.getElementById('scannerIframe');

            scannerModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const targetInputId = button.getAttribute('data-target-input');
                if (scannerIframe) {
                    scannerIframe.src = `scanner_modal.php?target=${targetInputId}`;
                }
            });

            // Disparado quando o modal do scanner começa a fechar
            scannerModalEl.addEventListener('hide.bs.modal', function (event) {
                // Se houver outro modal visível, removemos o backdrop deste modal
                // para que o Bootstrap não remova o backdrop do modal de baixo.
                if (document.querySelector('.modal.show')) {
                    scannerModalEl.removeAttribute('data-bs-backdrop');
                }
            });

            // Disparado DEPOIS que o modal do scanner fechou
            scannerModalEl.addEventListener('hidden.bs.modal', function() {
                if (scannerIframe) {
                    scannerIframe.src = '';
                }
                // Garante que o body mantenha o estado correto se outro modal ainda estiver aberto.
                const isAnotherModalOpen = document.querySelector('.modal.show');
                if (isAnotherModalOpen) {
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                }
                // Restaura o atributo de backdrop para a próxima vez que o modal for aberto.
                scannerModalEl.setAttribute('data-bs-backdrop', 'true');
            });

            // Função global para receber o código do iframe do scanner
            window.setScannedCode = function(code, targetId) {
                const targetInput = document.getElementById(targetId);
                const scannerModalInstance = bootstrap.Modal.getInstance(scannerModalEl);

                if (targetInput) {
                    targetInput.value = code;
                    targetInput.focus();
                }

                // Fecha apenas o modal do scanner
                if (scannerModalInstance) {
                    scannerModalInstance.hide();
                }
            };

        }

        // --- SCRIPT PARA O MODAL DE ENTRADA EXPRESSA ---
        document.getElementById('quickStockInForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitButton = form.closest('.modal-content').querySelector('button[type="submit"]');
            const originalButtonHtml = submitButton.innerHTML;
            const messageDiv = document.getElementById('quickStockInMessage');

            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
            submitButton.disabled = true;
            messageDiv.innerHTML = '';

            const formData = new FormData(form);

            fetch('quick_stock_in.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    form.reset();
                    document.getElementById('product_code').focus();
                    if(typeof table !== 'undefined') { // Recarrega a tabela se existir
                        table.ajax.reload();
                    }
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                showAlert('Erro de comunicação com o servidor.', 'danger');
            })
            .finally(() => {
                submitButton.innerHTML = originalButtonHtml;
                submitButton.disabled = false;
            });
        });

        const quickStockInModal = document.getElementById('quickStockInModal');
        if (quickStockInModal) {
            quickStockInModal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('quickStockInForm').reset();
                document.getElementById('quickStockInMessage').innerHTML = '';
            });
             quickStockInModal.addEventListener('shown.bs.modal', function () {
                document.getElementById('product_code').focus();
            });
        }
    });
</script>

</body>
</html>
