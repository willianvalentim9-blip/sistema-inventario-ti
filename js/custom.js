document.addEventListener('DOMContentLoaded', function () {
    // Inicializa os tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

/**
 * Função para exibir alertas estilizados e flutuantes.
 */
function showAlert(message, type = 'info', duration = 5000) {
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '80px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '1056'; // Z-index alto para ficar sobre o backdrop do modal
        alertContainer.style.minWidth = '300px';
        document.body.appendChild(alertContainer);
    }

    const alertId = 'alert-' + Date.now();
    const iconMap = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    const icon = iconMap[type] || 'info-circle';

    const alertHTML = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show shadow-lg" role="alert">
            <i class="fas fa-${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHTML);

    if (duration > 0) {
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertElement);
                if(bsAlert) bsAlert.close();
            }
        }, duration);
    }
}

/**
 * Função base para abrir um modal e carregar conteúdo via AJAX, garantindo a execução dos scripts.
 * *** CORRIGIDA PARA EVITAR RECARREGAMENTO DE SCRIPTS GLOBAIS ***
 */
function openActionModal(url, title) {
    const modalElement = document.getElementById('actionModal');
    if (!modalElement) {
        console.error('Modal element #actionModal not found.');
        return;
    }
    const actionModal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const modalTitle = document.getElementById('actionModalLabel');
    const modalBody = document.getElementById('actionModalBody');

    modalTitle.innerText = title;
    modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    actionModal.show();

    // **A CORREÇÃO PRINCIPAL ESTÁ AQUI**
    // Adiciona o parâmetro `modal=true` à URL para que o PHP não inclua header/footer.
    const fetchUrl = url.includes('?') ? `${url}&modal=true` : `${url}?modal=true`;

    fetch(fetchUrl)
        .then(response => {
            if (!response.ok) throw new Error('Falha ao carregar o conteúdo.');
            return response.text();
        })
        .then(html => {
            modalBody.innerHTML = html;
            // Re-executa apenas os scripts DENTRO do conteúdo carregado.
            // Como o footer não é mais incluído, isso não causará erros de redeclaração.
            Array.from(modalBody.querySelectorAll("script")).forEach(oldScript => {
                const newScript = document.createElement("script");
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        })
        .catch(error => {
            modalBody.innerHTML = `<div class="alert alert-danger">Erro ao carregar o conteúdo: ${error.message}</div>`;
        });
}

/**
 * Função para abrir o modal de dar baixa em um item.
 */
function openStockOutModal(type, id, name) {
    const url = `${type}_stock_out.php?id=${id}`;
    const title = `Dar Baixa: ${name}`;
    openActionModal(url, title);
}

/**
 * Função para abrir o modal de dar entrada em um PRODUTO.
 */
function openStockInModal(id, name) {
    const url = `product_stock_in.php?id=${id}`;
    const title = `Dar Entrada: ${name}`;
    openActionModal(url, title);
}

/**
 * Função para abrir o modal de dar entrada em uma MÁQUINA.
 */
function openMachineStockInModal(id, name) {
    const url = `machine_stock_in.php?id=${id}`;
    const title = `Dar Entrada: ${name}`;
    openActionModal(url, title);
}

/**
 * Abre um modal de confirmação para exclusão.
 */
function openDeleteModal(type, id, name) {
    let itemType = '';
    let url = '';

    switch(type) {
        case 'product': itemType = 'produto'; url = 'delete_product.php'; break;
        case 'machine': itemType = 'máquina'; url = 'delete_machine.php'; break;
        case 'user': itemType = 'usuário'; url = 'delete_user.php'; break;
        default: console.error('Tipo de exclusão desconhecido:', type); return;
    }

    const deleteFunction = () => {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        });
    };

    if (window.showConfirmation) { 
        showConfirmation({
            title: 'Confirmar Exclusão',
            message: `Deseja realmente excluir o ${itemType} "${name}"?`,
            details: '<strong class="text-danger">Esta ação não pode ser desfeita.</strong>',
            type: 'danger',
            confirmText: 'Sim, Excluir',
            confirmClass: 'btn-danger',
            onConfirm: deleteFunction
        });
    } else {
        if(confirm(`Deseja realmente excluir o ${itemType} "${name}"? Esta ação não pode ser desfeita.`)) {
            deleteFunction();
        }
    }
}


/**
 * Inicializa a funcionalidade de upload de imagem para um formulário.
 */
function setupImageUpload(options) {
    const { formId, fileInputId, hiddenInputId, previewImageId, placeholderId, previewContainerId, addBtnContainerId, removeBtnId, uploadAreaId, itemType, itemId } = options;

    const form = document.getElementById(formId);
    if (!form) return;

    const fileInput = document.getElementById(fileInputId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const previewImage = document.getElementById(previewImageId);
    const placeholder = document.getElementById(placeholderId);
    const previewContainer = document.getElementById(previewContainerId);
    const addBtnContainer = document.getElementById(addBtnContainerId);
    const uploadArea = document.getElementById(uploadAreaId);
    const removeBtn = document.getElementById(removeBtnId);

    if (!fileInput || !hiddenInput || !previewImage || !placeholder || !previewContainer || !addBtnContainer || !uploadArea || !removeBtn) {
        // Silencioso para não poluir o console em páginas sem upload
        return;
    }

    const handleFileUpload = (file) => {
        if (file.size > 5 * 1024 * 1024) { showAlert('Arquivo muito grande (máx 5MB).', 'warning'); return; }
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) { showAlert('Tipo de arquivo não permitido.', 'warning'); return; }

        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.src = e.target.result;
            placeholder.style.display = 'none';
            addBtnContainer.style.display = 'none';
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(file);

        const formData = new FormData();
        formData.append('image', file);
        formData.append('type', itemType);
        formData.append('id', itemId);

        fetch('upload_image.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    hiddenInput.value = data.filename;
                    showAlert('Imagem enviada com sucesso!', 'success');
                } else {
                    showAlert(`Erro no upload: ${data.message}`, 'danger');
                }
            });
    };

    const handleRemoveImage = () => {
        if (!confirm("Tem certeza que deseja remover esta imagem?")) return;

        const imagePath = hiddenInput.value;
        fetch("delete_image.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ image_path: imagePath, item_id: itemId, item_type: itemType }),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                previewImage.src = '';
                previewContainer.style.display = 'none';
                placeholder.style.display = 'block';
                fileInput.value = '';
                hiddenInput.value = '';
                showAlert(data.message, "success");
            } else {
                showAlert(data.message, "danger");
            }
        });
    };
    
    uploadArea.addEventListener('click', (e) => {
        if (removeBtn.contains(e.target)) return;
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            handleFileUpload(fileInput.files[0]);
        }
    });
    
    removeBtn.addEventListener('click', handleRemoveImage);
}


// ========================================
// LÓGICA DE TEMA (DARK/LIGHT)
// ========================================

function applyTheme(theme) {
    document.body.className = ""; // Limpa classes existentes
    document.body.classList.add(`theme-${theme}`);
}

function saveThemePreference(theme) {
    fetch("save_theme_preference.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ theme: theme }),
    })
    .catch(error => console.error("Erro de rede ao salvar preferência de tema:", error));
}

function toggleTheme() {
    const currentTheme = document.body.classList.contains("theme-dark_blue") ? "dark_blue" : "light";
    const newTheme = currentTheme === "light" ? "dark_blue" : "light";
    applyTheme(newTheme);
    saveThemePreference(newTheme);

    const themeToggleButton = document.getElementById("theme-toggle");
    if (themeToggleButton) {
        const icon = themeToggleButton.querySelector("i");
        icon.className = newTheme === "dark_blue" ? "fas fa-sun" : "fas fa-moon";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const themeToggleButton = document.getElementById("theme-toggle");
    if (themeToggleButton) {
        const icon = themeToggleButton.querySelector("i");
        const currentTheme = document.body.classList.contains("theme-dark_blue") ? "dark_blue" : "light";
        icon.className = currentTheme === "dark_blue" ? "fas fa-sun" : "fas fa-moon";
        themeToggleButton.addEventListener("click", toggleTheme);
    }
});