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

<div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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


<style>
/* CSS para permitir modais empilhados (scanner sobre modal de edição) */
#actionModal {
    z-index: 1055; /* Modal de edição/ação (base) */
}

#actionModal + .modal-backdrop {
    z-index: 1054; /* Backdrop do actionModal */
}

#scannerModal {
    z-index: 1060; /* Modal do scanner (acima de tudo) */
}

#scannerModal + .modal-backdrop,
.modal-backdrop:nth-of-type(2) {
    z-index: 1059; /* Backdrop do scanner entre os modais */
}

/* Garante que múltiplos backdrops sejam exibidos corretamente */
body.modal-open {
    overflow: hidden !important;
}
</style>

<script src="/sistema5/js/bootstrap.bundle.min.js"></script>
<script src="/sistema5/js/custom.js"></script>
<script src="/sistema5/js/enhanced_ui.js"></script>
<script src="/sistema5/js/history-modal.js"></script>
<script src="/sistema5/js/media-upload.js"></script>

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

        // --- SCRIPT GLOBAL PARA O SCANNER MODAL (SUPORTE A MODAIS EMPILHADOS) ---
        const scannerModalEl = document.getElementById('scannerModal');
        const actionModalEl = document.getElementById('actionModal');

        if (scannerModalEl) {
            const scannerIframe = document.getElementById('scannerIframe');
            let scannerModalInstance = null;

            // Event listener global para todos os botões com data-open-scanner
            document.addEventListener('click', function(event) {
                const button = event.target.closest('[data-open-scanner]');
                if (!button) return;

                event.preventDefault();
                event.stopPropagation();

                const targetInputId = button.getAttribute('data-target-input');
                if (!targetInputId) {
                    console.error('Atributo data-target-input não encontrado no botão do scanner');
                    return;
                }

                // Carrega o iframe com o target input
                if (scannerIframe) {
                    scannerIframe.src = `modules/barcode/scanner_modal.php?target=${targetInputId}`;
                }

                // Cria/pega a instância do modal e abre programaticamente
                // Isso NÃO fecha outros modais abertos
                scannerModalInstance = bootstrap.Modal.getOrCreateInstance(scannerModalEl, {
                    backdrop: 'static',
                    keyboard: false
                });
                scannerModalInstance.show();
            });

            // Quando o scanner modal fecha
            scannerModalEl.addEventListener('hide.bs.modal', function(event) {
                // Verifica se há outros modais abertos
                const otherModals = document.querySelectorAll('.modal.show:not(#scannerModal)');

                if (otherModals.length > 0) {
                    // Previne que o Bootstrap remova a classe modal-open do body
                    setTimeout(() => {
                        if (!document.body.classList.contains('modal-open')) {
                            document.body.classList.add('modal-open');
                        }
                        document.body.style.overflow = 'hidden';
                        document.body.style.paddingRight = '';
                    }, 10);
                }
            });

            // Depois que o scanner modal fecha completamente
            scannerModalEl.addEventListener('hidden.bs.modal', function() {
                // Limpa o iframe
                if (scannerIframe) {
                    scannerIframe.src = '';
                }

                // Garante que o body mantenha o estado correto se outro modal ainda estiver aberto
                const otherModals = document.querySelectorAll('.modal.show');
                if (otherModals.length > 0) {
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';

                    // Remove apenas backdrops extras (do scanner)
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length > 1) {
                        backdrops[backdrops.length - 1].remove();
                    }
                }
            });

            // Função global para receber o código do iframe do scanner
            window.setScannedCode = function(code, targetId) {
                const targetInput = document.getElementById(targetId);

                if (targetInput) {
                    targetInput.value = code;

                    // Dispara eventos para validações
                    targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                    targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Fecha o modal do scanner
                if (scannerModalInstance) {
                    scannerModalInstance.hide();
                }

                // Foca no campo que recebeu o valor
                setTimeout(() => {
                    if (targetInput) {
                        targetInput.focus();
                    }
                }, 300);
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
