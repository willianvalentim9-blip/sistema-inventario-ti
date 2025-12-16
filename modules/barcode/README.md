# 📦 Módulo Barcode - Sistema de Código de Barras

## 📍 Localização Nova
- **Antes**: Arquivos espalhados na raiz (`/barcode_print.php`, `/generate_barcode_image.php`, `/scanner_modal.php`, etc.)
- **Agora**: Centralizado em `/modules/barcode/`

## 📋 Arquivos do Módulo

### Arquivos PHP
- `barcode_print.php` - Página de impressão de códigos de barras
- `generate_barcode_image.php` - API que gera imagens SVG de barcode
- `scanner_modal.php` - Modal de scanner com câmera (HTML5 + Quagga + Html5-qrcode)
- `barcode-loader.php` - Carregador centralizado da biblioteca de barcode
- `BarcodeBar.php` - Classe de barra individual
- `barcode-lib/` - Biblioteca de geração de códigos (Picqer)

### Redirects Automáticos (Raiz)
Para manter compatibilidade, existem redirects automáticos na raiz:
- `barcode_print_redirect.php` → `modules/barcode/barcode_print.php`
- `generate_barcode_image_redirect.php` → `modules/barcode/generate_barcode_image.php`
- `scanner_modal_redirect.php` → `modules/barcode/scanner_modal.php`

## ✅ O que foi corrigido

### 1. **Paths Relativos**
- ✅ `barcode_print.php`: Atualizado para usar `../../config.php` e `../../includes/header.php`
- ✅ `scanner_modal.php`: Atualizado para usar `../../config.php` e `../../css/bootstrap.min.css`
- ✅ `generate_barcode_image.php`: Carrega `barcode-loader.php` do diretório atual

### 2. **URL de Geração de Barcode**
- ✅ Corrigido em `barcode_print.php`: Agora usa `generate_barcode_image.php` (relativo) em vez de caminho absoluto
- A URL era: `${baseUrl}/generate_barcode_image.php`
- Agora é: `generate_barcode_image.php` (mesmo diretório)

### 3. **Integração com Footer**
- ✅ Atualizado `includes/footer.php` para chamar: `modules/barcode/scanner_modal.php`
- A linha: `scannerIframe.src = \`scanner_modal.php?target=${targetInputId}\``
- Agora é: `scannerIframe.src = \`modules/barcode/scanner_modal.php?target=${targetInputId}\``

### 4. **CSS e JavaScript**
- ✅ Bootstrap CDN: Funcionando normalmente (CDN)
- ✅ Font Awesome CDN: Funcionando normalmente (CDN)
- ✅ Html5-qrcode CDN: Funcionando normalmente (CDN)
- ✅ Quagga CDN: Funcionando normalmente (CDN)
- ✅ Custom JS: Nenhuma alteração necessária (funciona em qualquer local)

## 🧪 Como Testar

### 1. Teste de Diagnóstico
```bash
http://localhost/sistema5/diagnostic_barcode.php
```
Este script verifica:
- Todos os arquivos da biblioteca
- Se a classe BarcodeGeneratorSVG funciona
- Integração com config.php
- CSS e JS carregados
- CDN APIs disponíveis

### 2. Teste de Impressão
```bash
http://localhost/sistema5/modules/barcode/barcode_print.php?code=TEST123
```
Deve exibir uma página com um código de barras Code 128

### 3. Teste de Geração de Imagem
```bash
http://localhost/sistema5/modules/barcode/generate_barcode_image.php?code=TEST123&type=C128
```
Deve retornar uma imagem SVG do código de barras

### 4. Teste de Scanner Modal
Abra qualquer página com um botão de scanner e clique. O modal deve abrir com a câmera ativa.

## 🔄 URLs Compatíveis (Redirects)

Todas estas URLs ainda funcionam (com redirect automático):
- `http://localhost/sistema5/barcode_print_redirect.php?code=TEST`
- `http://localhost/sistema5/generate_barcode_image_redirect.php?code=TEST&type=C128`
- `http://localhost/sistema5/scanner_modal_redirect.php?target=product_code`

## 📊 Estrutura de Diretórios

```
módulos/barcode/
├── barcode_print.php              # Página de impressão
├── generate_barcode_image.php      # API de geração SVG
├── scanner_modal.php               # Modal de scanner
├── barcode-loader.php              # Loader centralizado
├── BarcodeBar.php                  # Classe de barra
└── barcode-lib/                    # Biblioteca Picqer
    ├── src/
    │   ├── BarcodeBar.php
    │   ├── BarcodeGenerator.php
    │   ├── BarcodeGeneratorSVG.php
    │   ├── BarcodeGeneratorPNG.php
    │   ├── Exceptions/
    │   ├── Helpers/
    │   ├── Renderers/
    │   └── Types/
    └── composer.json
```

## 🚀 Próximos Módulos

Após validar o barcode funcionando perfeitamente, migrar:
1. Módulo de Logs → `modules/logs/`
2. Módulo de Garantias → `modules/warranties/`
3. ... (seguir ordem do PLANO_ORGANIZACAO_ARQUIVOS.md)

## ⚠️ Observações Importantes

1. **Não delete os arquivos antigos** na raiz até validar que tudo funciona
2. **Os redirects automáticos** mantêm compatibilidade com código antigo
3. **Quando migrar outros módulos**, use o mesmo padrão de paths relativos
4. **CDN URLs** não precisam ser alteradas (permanecem iguais)
5. **Config.php** agora fica em `/config/config.php` (plano futuro)

## 📞 Diagnóstico & Debug

Se algo não funcionar:

1. Verifique `diagnostic_barcode.php`
2. Procure por erros em `error_log`
3. Verifique paths relativos em cada arquivo PHP
4. Valide que `config.php` está acessível
5. Teste CDNs (Font Awesome, Bootstrap, Quagga, Html5-qrcode)

---

**Data de Migração**: Dezembro 11, 2025  
**Status**: ✅ Pronto para testes
