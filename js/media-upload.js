/**
 * ========================================
 * Media Upload Module
 * Gerencia upload de imagem com câmera e galeria
 * Compatível com Samsung e outros aparelhos
 * ========================================
 */

// Previne carregamento duplicado - MediaUploadManager já foi declarado
if (typeof window.mediaUploadModuleLoaded !== 'undefined' && window.mediaUploadModuleLoaded === true) {
    console.warn('⚠️ media-upload.js já foi carregado, ignorando duplicata');
    // Retorna imediatamente sem declarar a classe novamente
} else {
    // Marca como carregado
    window.mediaUploadModuleLoaded = true;
    console.log('✅ media-upload.js carregando...');

    class MediaUploadManager {
        constructor(config = {}) {
            this.config = {
            containerId: 'media-upload-container',
            fileInputId: 'media-file-input',
            cameraInputId: 'media-camera-input',
            hiddenInputId: 'uploaded_image',
            uploadUrl: 'upload_image.php',
            maxFileSize: 5 * 1024 * 1024, // 5MB
            allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
            itemType: 'products', // 'products' ou 'machine'
            ...config
        };

        this.isUploading = false; // Flag para evitar envios duplicados
        this.statusTimeoutId = null; // ID do timeout para hideStatus

        this.container = document.getElementById(this.config.containerId);
        if (!this.container) {
            console.warn(`MediaUploadManager: Container #${this.config.containerId} não encontrado`);
            return;
        }

        this.init();
    }

    init() {
        this.createDOM();
        this.setupEventListeners();
        this.detectDevice();
    }

    // Método para limpar e reinicializar sem duplicar listeners
    reinit() {
        this.cleanup();
        this.init();
    }

    // Limpa todos os event listeners
    cleanup() {
        if (this.galeryBtn && this._galleryClickHandler) {
            this.galeryBtn.removeEventListener('click', this._galleryClickHandler);
        }
        if (this.cameraBtn && this._cameraClickHandler) {
            this.cameraBtn.removeEventListener('click', this._cameraClickHandler);
        }
        if (this.fileInput && this._fileChangeHandler) {
            this.fileInput.removeEventListener('change', this._fileChangeHandler);
        }
        if (this.cameraInput && this._cameraChangeHandler) {
            this.cameraInput.removeEventListener('change', this._cameraChangeHandler);
        }
        if (this.removeBtn && this._removeClickHandler) {
            this.removeBtn.removeEventListener('click', this._removeClickHandler);
        }
        if (this.changeBtn && this._changeClickHandler) {
            this.changeBtn.removeEventListener('click', this._changeClickHandler);
        }
        if (this.container && this._dragoverHandler) {
            this.container.removeEventListener('dragover', this._dragoverHandler);
        }
        if (this.container && this._dragleaveHandler) {
            this.container.removeEventListener('dragleave', this._dragleaveHandler);
        }
        if (this.container && this._dropHandler) {
            this.container.removeEventListener('drop', this._dropHandler);
        }
        if (this.statusTimeoutId) {
            clearTimeout(this.statusTimeoutId);
            this.statusTimeoutId = null;
        }
        
        // Remove elemento de status se existir
        let statusEl = this.container?.querySelector('.media-upload-status');
        if (!statusEl && this.container?.nextElementSibling?.classList.contains('media-upload-status')) {
            statusEl = this.container.nextElementSibling;
        }
        if (statusEl) {
            statusEl.remove();
        }
    }

    createDOM() {
        console.log('🔍 Criando DOM para container:', this.config.containerId);

        // HTML já existe no HTML, então apenas referenciamos
        this.placeholder = this.container.querySelector('.media-upload-placeholder');
        this.preview = this.container.querySelector('.media-upload-preview');
        this.previewImage = this.container.querySelector('.media-upload-preview-image');
        this.fileInput = document.getElementById(this.config.fileInputId);
        this.cameraInput = document.getElementById(this.config.cameraInputId);
        this.hiddenInput = document.getElementById(this.config.hiddenInputId);
        this.galeryBtn = this.container.querySelector('[data-action="gallery"]');
        this.cameraBtn = this.container.querySelector('[data-action="camera"]');
        this.removeBtn = this.container.querySelector('[data-action="remove"]');
        this.changeBtn = this.container.querySelector('[data-action="change"]');

        // Debug
        console.log('📋 Elementos encontrados:', {
            placeholder: !!this.placeholder,
            preview: !!this.preview,
            galeryBtn: !!this.galeryBtn,
            cameraBtn: !!this.cameraBtn,
            fileInput: !!this.fileInput,
            cameraInput: !!this.cameraInput,
            hiddenInput: !!this.hiddenInput,
            removeBtn: !!this.removeBtn,
            changeBtn: !!this.changeBtn
        });

        // Criar inputs se não existirem
        if (!this.fileInput) {
            this.fileInput = document.createElement('input');
            this.fileInput.id = this.config.fileInputId;
            this.fileInput.type = 'file';
            this.fileInput.accept = 'image/*';
            this.fileInput.style.display = 'none';
            this.container.appendChild(this.fileInput);
        }

        if (!this.cameraInput) {
            this.cameraInput = document.createElement('input');
            this.cameraInput.id = this.config.cameraInputId;
            this.cameraInput.type = 'file';
            this.cameraInput.accept = 'image/*';
            this.cameraInput.capture = 'environment';
            this.cameraInput.style.display = 'none';
            this.container.appendChild(this.cameraInput);
        }

        if (!this.hiddenInput) {
            this.hiddenInput = document.createElement('input');
            this.hiddenInput.id = this.config.hiddenInputId;
            this.hiddenInput.name = 'uploaded_image';
            this.hiddenInput.type = 'hidden';
            this.container.parentElement.appendChild(this.hiddenInput);
        }
    }

    setupEventListeners() {
        console.log('🔧 Configurando event listeners...', {
            galeryBtn: !!this.galeryBtn,
            cameraBtn: !!this.cameraBtn,
            fileInput: !!this.fileInput,
            cameraInput: !!this.cameraInput,
            removeBtn: !!this.removeBtn,
            changeBtn: !!this.changeBtn
        });

        // Botão Galeria
        if (this.galeryBtn) {
            if (this._galleryClickHandler) {
                this.galeryBtn.removeEventListener('click', this._galleryClickHandler);
            }
            this._galleryClickHandler = (e) => {
                console.log('🖼️ EVENTO CLICK CAPTURADO no botão galeria!', e);
                e.stopPropagation();
                console.log('🖼️ Abrindo file input para galeria...');
                if (this.fileInput) {
                    this.fileInput.click();
                } else {
                    console.error('❌ fileInput não encontrado!');
                }
            };
            this.galeryBtn.addEventListener('click', this._galleryClickHandler);
            console.log('✅ Listener de Galeria adicionado ao botão:', this.galeryBtn);

            // Debug: Verifica se o botão está visível e clicável
            console.log('🔍 GalleryBtn details:', {
                className: this.galeryBtn.className,
                visible: this.galeryBtn.offsetParent !== null,
                disabled: this.galeryBtn.disabled,
                type: this.galeryBtn.type
            });
        } else {
            console.warn('⚠️ Botão de Galeria NÃO encontrado!');
        }

        // Botão Câmera
        if (this.cameraBtn) {
            if (this._cameraClickHandler) {
                this.cameraBtn.removeEventListener('click', this._cameraClickHandler);
            }
            this._cameraClickHandler = (e) => {
                console.log('📷 EVENTO CLICK CAPTURADO no botão câmera!', e);
                e.stopPropagation();
                console.log('📷 Chamando openCamera()...');
                this.openCamera();
            };
            this.cameraBtn.addEventListener('click', this._cameraClickHandler);
            console.log('✅ Listener de Câmera adicionado ao botão:', this.cameraBtn);

            // Debug: Verifica se o botão está visível e clicável
            console.log('🔍 CameraBtn details:', {
                className: this.cameraBtn.className,
                visible: this.cameraBtn.offsetParent !== null,
                disabled: this.cameraBtn.disabled,
                type: this.cameraBtn.type
            });
        } else {
            console.warn('⚠️ Botão de Câmera NÃO encontrado!');
        }

        // Inputs de arquivo
        if (this.fileInput) {
            if (this._fileChangeHandler) {
                this.fileInput.removeEventListener('change', this._fileChangeHandler);
            }
            this._fileChangeHandler = (e) => {
                if (e.target.files.length > 0) {
                    this.handleFile(e.target.files[0]);
                }
            };
            this.fileInput.addEventListener('change', this._fileChangeHandler);
            console.log('✅ Listener de File Input adicionado');
        }

        if (this.cameraInput) {
            if (this._cameraChangeHandler) {
                this.cameraInput.removeEventListener('change', this._cameraChangeHandler);
            }
            this._cameraChangeHandler = (e) => {
                if (e.target.files.length > 0) {
                    this.handleFile(e.target.files[0]);
                }
            };
            this.cameraInput.addEventListener('change', this._cameraChangeHandler);
            console.log('✅ Listener de Camera Input adicionado');
        }

        // Botões de ação (remover, trocar)
        if (this.removeBtn) {
            if (this._removeClickHandler) {
                this.removeBtn.removeEventListener('click', this._removeClickHandler);
            }
            this._removeClickHandler = (e) => {
                console.log('🗑️ EVENTO CLICK CAPTURADO no botão remover!', e);
                e.preventDefault();
                e.stopPropagation();
                console.log('🗑️ Chamando removeImage()...');
                this.removeImage();
            };
            this.removeBtn.addEventListener('click', this._removeClickHandler);
            console.log('✅ Listener de Remove adicionado ao botão:', this.removeBtn);

            // Debug: Verifica se o botão está visível e clicável
            console.log('🔍 RemoveBtn details:', {
                className: this.removeBtn.className,
                visible: this.removeBtn.offsetParent !== null,
                disabled: this.removeBtn.disabled,
                type: this.removeBtn.type
            });
        } else {
            console.warn('⚠️ Botão de Remove NÃO encontrado!');
        }

        if (this.changeBtn) {
            if (this._changeClickHandler) {
                this.changeBtn.removeEventListener('click', this._changeClickHandler);
            }
            this._changeClickHandler = (e) => {
                console.log('🔄 EVENTO CLICK CAPTURADO no botão trocar!', e);
                e.preventDefault();
                e.stopPropagation();
                console.log('🔄 Abrindo file input...');
                if (this.fileInput) {
                    this.fileInput.click();
                } else {
                    console.error('❌ fileInput não encontrado!');
                }
            };
            this.changeBtn.addEventListener('click', this._changeClickHandler);
            console.log('✅ Listener de Change adicionado ao botão:', this.changeBtn);

            // Debug: Verifica se o botão está visível e clicável
            console.log('🔍 ChangeBtn details:', {
                className: this.changeBtn.className,
                visible: this.changeBtn.offsetParent !== null,
                disabled: this.changeBtn.disabled,
                type: this.changeBtn.type
            });
        } else {
            console.warn('⚠️ Botão de Change NÃO encontrado!');
        }

        // Drag and Drop
        this.setupDragDrop();
    }

    setupDragDrop() {
        if (this.container) {
            if (this._dragoverHandler) {
                this.container.removeEventListener('dragover', this._dragoverHandler);
            }
            this._dragoverHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.container.classList.add('drag-over');
            };
            this.container.addEventListener('dragover', this._dragoverHandler);

            if (this._dragleaveHandler) {
                this.container.removeEventListener('dragleave', this._dragleaveHandler);
            }
            this._dragleaveHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.container.classList.remove('drag-over');
            };
            this.container.addEventListener('dragleave', this._dragleaveHandler);

            if (this._dropHandler) {
                this.container.removeEventListener('drop', this._dropHandler);
            }
            this._dropHandler = (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.container.classList.remove('drag-over');

                if (e.dataTransfer.files.length > 0) {
                    this.handleFile(e.dataTransfer.files[0]);
                }
            };
            this.container.addEventListener('drop', this._dropHandler);
        }
    }

    detectDevice() {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        const isSamsung = /Samsung/.test(userAgent) || /SM-/.test(userAgent);
        const isAndroid = /Android/.test(userAgent);
        const isIOS = /iPhone|iPad|iPod/.test(userAgent);

        this.device = {
            isSamsung,
            isAndroid,
            isIOS,
            isMobile: isAndroid || isIOS
        };

        console.log('📱 Device detectado:', this.device);
    }

    openCamera() {
        // Estratégia 1: Tentar capture="environment" do input file
        // Estratégia 2: Se falhar, usar getUserMedia
        // Estratégia 3: Se falhar, fallback para galeria

        try {
            // Para Samsung, forçar abertura da câmera com capture
            if (this.device.isSamsung || this.device.isAndroid) {
                // Método 1: Tentar clique direto no input camera
                this.cameraInput.click();
            } else {
                // Para iOS e outros, usar capture=user para câmera frontal
                this.cameraInput.setAttribute('capture', 'user');
                this.cameraInput.click();
            }

            // Fallback: Se não funcionar em 1 segundo, tentar getUserMedia
            setTimeout(() => {
                if (this.cameraInput.files.length === 0) {
                    this.tryUserMediaCamera();
                }
            }, 1000);
        } catch (error) {
            console.error('Erro ao abrir câmera:', error);
            this.tryUserMediaCamera();
        }
    }

    tryUserMediaCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showAlert('Câmera não disponível neste dispositivo. Use a galeria.', 'warning');
            return;
        }

        navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment' // Câmera traseira
            },
            audio: false
        })
        .then(stream => {
            this.showCameraModal(stream);
        })
        .catch(error => {
            console.error('getUserMedia error:', error);

            // Fallback: Se câmera traseira não funcionar, tentar frontal
            navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'user' }
                },
                audio: false
            })
            .then(stream => {
                this.showCameraModal(stream);
            })
            .catch(err => {
                console.error('Erro ao acessar câmera:', err);
                showAlert('Não foi possível acessar a câmera. Permissões recusadas ou câmera não disponível.', 'danger');
                // Fallback: abrir galeria
                this.fileInput.click();
            });
        });
    }

    showCameraModal(stream) {
        // Criar modal de câmera
        const modal = document.createElement('div');
        modal.className = 'media-camera-modal active';
        modal.id = 'camera-modal-' + Date.now();

        const container = document.createElement('div');
        container.className = 'media-camera-container';

        const video = document.createElement('video');
        video.className = 'media-camera-video';
        video.autoplay = true;
        video.playsinline = true;
        video.srcObject = stream;

        const controls = document.createElement('div');
        controls.className = 'media-camera-controls';

        const closeBtn = document.createElement('button');
        closeBtn.className = 'media-camera-btn close';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.type = 'button';

        const captureBtn = document.createElement('button');
        captureBtn.className = 'media-camera-btn capture';
        captureBtn.innerHTML = '<i class="fas fa-circle"></i>';
        captureBtn.type = 'button';

        closeBtn.addEventListener('click', () => {
            stream.getTracks().forEach(track => track.stop());
            modal.remove();
        });

        captureBtn.addEventListener('click', () => {
            this.captureFromCamera(video, stream);
            stream.getTracks().forEach(track => track.stop());
            modal.remove();
        });

        controls.appendChild(closeBtn);
        controls.appendChild(captureBtn);
        container.appendChild(video);
        container.appendChild(controls);
        modal.appendChild(container);

        document.body.appendChild(modal);
    }

    captureFromCamera(video, stream) {
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        canvas.toBlob((blob) => {
            const file = new File([blob], 'camera-' + Date.now() + '.jpg', {
                type: 'image/jpeg'
            });
            this.handleFile(file);
        }, 'image/jpeg', 0.9);
    }

    handleFile(file) {
        // Validações
        if (file.size > this.config.maxFileSize) {
            showAlert(`Arquivo muito grande. Máximo: ${(this.config.maxFileSize / 1024 / 1024).toFixed(0)}MB`, 'warning');
            return;
        }

        if (!this.config.allowedTypes.includes(file.type)) {
            showAlert('Tipo de arquivo não permitido. Use: JPG, PNG, GIF ou WebP', 'warning');
            return;
        }

        // Preview local
        const reader = new FileReader();
        reader.onload = (e) => {
            this.showPreview(e.target.result);
        };
        reader.readAsDataURL(file);

        // Upload
        this.uploadFile(file);
    }

    uploadFile(file) {
        // Previne múltiplos envios simultâneos
        if (this.isUploading) {
            console.warn('⚠️ Upload já em progresso, ignorando novo envio');
            return;
        }

        this.isUploading = true;
        const formData = new FormData();
        formData.append('image', file);
        formData.append('type', this.config.itemType);

        this.showStatus('loading', 'Enviando imagem...');

        fetch(this.config.uploadUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json().catch(err => {
                console.error('Erro ao parsear JSON:', err, 'Response:', response);
                throw new Error('Resposta do servidor não é um JSON válido');
            });
        })
        .then(data => {
            this.isUploading = false;
            console.log('Upload response:', data);
            if (data.success) {
                this.hiddenInput.value = data.filename;
                this.showStatus('success', 'Imagem enviada com sucesso!');
                this.statusTimeoutId = setTimeout(() => this.hideStatus(), 3000);
            } else {
                this.showStatus('error', `Erro: ${data.message || 'Erro desconhecido'}`);
                this.removePreview();
                this.statusTimeoutId = setTimeout(() => this.hideStatus(), 4000);
            }
        })
        .catch(error => {
            this.isUploading = false;
            console.error('Upload error:', error);
            this.showStatus('error', `Erro ao enviar: ${error.message}`);
            this.removePreview();
            this.statusTimeoutId = setTimeout(() => this.hideStatus(), 4000);
        });
    }

    showPreview(src) {
        if (!this.previewImage) return;

        this.previewImage.src = src;
        this.placeholder.style.display = 'none';
        this.preview.style.display = 'block';
    }

    removePreview() {
        if (!this.previewImage) return;

        this.previewImage.src = '';
        this.placeholder.style.display = 'block';
        this.preview.style.display = 'none';
    }

    removeImage() {
        if (!confirm('Deseja remover esta imagem?')) return;

        this.hiddenInput.value = '';
        this.fileInput.value = '';
        this.cameraInput.value = '';
        this.removePreview();
        this.showStatus('success', 'Imagem removida');
        this.statusTimeoutId = setTimeout(() => this.hideStatus(), 2000);
    }

    showStatus(type, message) {
        // Limpar timeout anterior se existir
        if (this.statusTimeoutId) {
            clearTimeout(this.statusTimeoutId);
            this.statusTimeoutId = null;
        }

        // ⭐️ CRÍTICO: Procura por elemento de status de forma rigorosa
        let statusEl = null;

        // Procura dentro do container
        const containerStatus = this.container?.querySelector('.media-upload-status');
        if (containerStatus) {
            statusEl = containerStatus;
        }

        // Se não encontrou, procura como irmão direto (nextElementSibling)
        if (!statusEl && this.container?.nextElementSibling?.classList.contains('media-upload-status')) {
            statusEl = this.container.nextElementSibling;
        }

        // Se ainda não encontrou, procura em toda a árvore pai
        if (!statusEl && this.container?.parentElement) {
            const parentStatus = this.container.parentElement.querySelector('.media-upload-status');
            if (parentStatus && parentStatus !== containerStatus) {
                statusEl = parentStatus;
            }
        }

        // Se AINDA não encontrou, cria um novo
        if (!statusEl) {
            statusEl = document.createElement('div');
            statusEl.className = 'media-upload-status';
            // Insere IMEDIATAMENTE após o container
            if (this.container?.parentElement) {
                this.container.parentElement.insertBefore(statusEl, this.container.nextSibling);
            }
        }

        // Atualiza o status com a nova mensagem
        const iconClass = type === 'loading' ? 'spinner fa-spin' : type === 'success' ? 'check-circle' : 'exclamation-circle';
        statusEl.className = `media-upload-status ${type}`;
        statusEl.innerHTML = `<i class="fas fa-${iconClass}"></i> ${message}`;
        statusEl.style.display = 'block';
    }

    hideStatus() {
        // Procura o status no container ou no parentElement
        let statusEl = this.container.querySelector('.media-upload-status');
        if (!statusEl) {
            statusEl = this.container.parentElement?.querySelector('.media-upload-status');
        }
        
        if (statusEl) {
            statusEl.style.display = 'none';
            statusEl.innerHTML = '';
        }
    }
}

    // ========================================
    // INICIALIZAÇÃO AUTOMÁTICA (compatível com modal dinâmico)
    // ========================================
    (function() {
        function initAllMediaUploadManagers() {
            console.log('📸 Inicializando Media Upload Managers...');

            // Inicializar para produtos (página completa)
            const productUpload = document.getElementById('product-upload-container');
            if (productUpload && !window.productUploadManager) {
                window.productUploadManager = new MediaUploadManager({
                    containerId: 'product-upload-container',
                    fileInputId: 'media-file-input-product',
                    cameraInputId: 'media-camera-input-product',
                    itemType: 'products'
                });
                console.log('✅ Product Upload Manager inicializado');
            }

            // Inicializar para máquinas (página completa)
            const machineUpload = document.getElementById('machine-upload-container');
            if (machineUpload && !window.machineUploadManager) {
                window.machineUploadManager = new MediaUploadManager({
                    containerId: 'machine-upload-container',
                    fileInputId: 'media-file-input-machine',
                    cameraInputId: 'media-camera-input-machine',
                    itemType: 'machine'
                });
                console.log('✅ Machine Upload Manager inicializado');
            }

            // Inicializar para warehouse (página completa)
            const warehouseUpload = document.getElementById('warehouse-upload-container');
            if (warehouseUpload && !window.warehouseUploadManager) {
                window.warehouseUploadManager = new MediaUploadManager({
                    containerId: 'warehouse-upload-container',
                    fileInputId: 'media-file-input',
                    cameraInputId: 'media-camera-input',
                    itemType: 'warehouse'
                });
                console.log('✅ Warehouse Upload Manager inicializado');
            }
        }

    // Tenta inicializar imediatamente
    if (document.readyState === 'loading') {
        // DOM ainda carregando, aguarda
        document.addEventListener('DOMContentLoaded', initAllMediaUploadManagers);
    } else {
        // DOM já carregado (caso de modal dinâmico), inicializa agora
        // MAS APENAS SE NÃO FOR MODAL (modais usam scripts próprios para evitar duplicação)
        if (!document.getElementById('actionModal') && !document.getElementById('editModal')) {
            initAllMediaUploadManagers();
        }
    }

    // OBSERVER: Detecta quando novos containers são adicionados ao DOM (modal dinâmico)
    if (typeof MutationObserver !== 'undefined') {
        let observerTimeout = null;
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        // Verifica se é o container de upload ou se contém o container
                        if (node.id === 'machine-upload-container-modal' ||
                            node.querySelector && node.querySelector('#machine-upload-container-modal')) {
                            console.log('🔄 Container modal detectado via observer, inicializando...');
                            // Debounce: evita múltiplas chamadas em sequência rápida
                            clearTimeout(observerTimeout);
                            observerTimeout = setTimeout(initAllMediaUploadManagers, 200);
                        }
                    }
                });
            });
        });

        // Observa o body para detectar novos nós
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    })();

    // Exportar para uso global
    window.MediaUploadManager = MediaUploadManager;

    // Função helper para inicialização manual (retrocompatibilidade)
    window.initMediaUpload = function(containerId, hiddenInputId, itemType) {
        return new MediaUploadManager({
            containerId: containerId,
            fileInputId: 'media-file-input-' + itemType,
            cameraInputId: 'media-camera-input-' + itemType,
            hiddenInputId: hiddenInputId,
            itemType: itemType
        });
    };

} // Fecha o bloco 'else' de prevençao de carregamento duplicado
