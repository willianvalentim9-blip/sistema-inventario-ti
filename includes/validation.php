<?php
/**
 * Arquivo de Validação Centralizada
 * Sistema de Estoque de TI
 * 
 * Este arquivo contém classes e funções para validação de dados
 * de forma consistente em todo o sistema.
 */

class Validator {
    private $errors = [];
    
    /**
     * Adiciona um erro à lista
     */
    public function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Retorna todos os erros
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Verifica se há erros
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Limpa todos os erros
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Valida campo obrigatório
     */
    public function required($field, $value, $message = null) {
        if (empty(trim($value))) {
            $message = $message ?: "O campo {$field} é obrigatório.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida comprimento mínimo
     */
    public function minLength($field, $value, $min, $message = null) {
        if (strlen(trim($value)) < $min) {
            $message = $message ?: "O campo {$field} deve ter pelo menos {$min} caracteres.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida comprimento máximo
     */
    public function maxLength($field, $value, $max, $message = null) {
        if (strlen(trim($value)) > $max) {
            $message = $message ?: "O campo {$field} deve ter no máximo {$max} caracteres.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida email
     */
    public function email($field, $value, $message = null) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $message = $message ?: "O campo {$field} deve conter um email válido.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida número inteiro
     */
    public function integer($field, $value, $message = null) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $message = $message ?: "O campo {$field} deve ser um número inteiro.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida número decimal
     */
    public function decimal($field, $value, $message = null) {
        if (!empty($value) && !is_numeric($value)) {
            $message = $message ?: "O campo {$field} deve ser um número válido.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida valor mínimo
     */
    public function min($field, $value, $min, $message = null) {
        if (!empty($value) && $value < $min) {
            $message = $message ?: "O campo {$field} deve ser maior ou igual a {$min}.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida valor máximo
     */
    public function max($field, $value, $max, $message = null) {
        if (!empty($value) && $value > $max) {
            $message = $message ?: "O campo {$field} deve ser menor ou igual a {$max}.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida se valor está em uma lista de opções
     */
    public function inArray($field, $value, $options, $message = null) {
        if (!empty($value) && !in_array($value, $options)) {
            $message = $message ?: "O campo {$field} contém um valor inválido.";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }
    
    /**
     * Valida formato de data
     */
    public function date($field, $value, $format = 'Y-m-d', $message = null) {
        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $message = $message ?: "O campo {$field} deve conter uma data válida.";
                $this->addError($field, $message);
                return false;
            }
        }
        return true;
    }
    
    /**
     * Valida unicidade no banco de dados
     */
    public function unique($field, $value, $table, $column, $excludeId = null, $message = null) {
        if (empty($value)) return true;
        
        try {
            $pdo = getConnection();
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $message = $message ?: "O valor do campo {$field} já está em uso.";
                $this->addError($field, $message);
                return false;
            }
        } catch (PDOException $e) {
            $this->addError($field, "Erro ao validar unicidade do campo {$field}.");
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida upload de arquivo
     */
    public function file($field, $file, $allowedTypes = [], $maxSize = null, $message = null) {
        if (empty($file['name'])) return true;
        
        // Verifica se houve erro no upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message = $message ?: "Erro no upload do arquivo {$field}.";
            $this->addError($field, $message);
            return false;
        }
        
        // Verifica tipo de arquivo
        if (!empty($allowedTypes)) {
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileType, $allowedTypes)) {
                $message = $message ?: "O arquivo {$field} deve ser do tipo: " . implode(', ', $allowedTypes);
                $this->addError($field, $message);
                return false;
            }
        }
        
        // Verifica tamanho do arquivo
        if ($maxSize && $file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            $message = $message ?: "O arquivo {$field} deve ter no máximo {$maxSizeMB}MB.";
            $this->addError($field, $message);
            return false;
        }
        
        return true;
    }
}

/**
 * Classe para validação específica de produtos
 */
class ProductValidator extends Validator {
    public function validateProduct($data, $isEdit = false, $productId = null) {
        // Campos obrigatórios
        $this->required('name', $data['name'] ?? '');
        $this->required('category', $data['category'] ?? '');
        $this->required('quantity', $data['quantity'] ?? '');
        
        // Validações específicas
        if (!empty($data['name'])) {
            $this->minLength('name', $data['name'], 2);
            $this->maxLength('name', $data['name'], 255);
        }
        
        if (!empty($data['description'])) {
            $this->maxLength('description', $data['description'], 1000);
        }
        
        if (!empty($data['quantity'])) {
            $this->integer('quantity', $data['quantity']);
            $this->min('quantity', $data['quantity'], 0);
        }
        
        if (!empty($data['min_quantity'])) {
            $this->integer('min_quantity', $data['min_quantity']);
            $this->min('min_quantity', $data['min_quantity'], 0);
        }
        
        if (!empty($data['max_quantity'])) {
            $this->integer('max_quantity', $data['max_quantity']);
            $this->min('max_quantity', $data['max_quantity'], 0);
        }
        
        if (!empty($data['price'])) {
            $this->decimal('price', $data['price']);
            $this->min('price', $data['price'], 0);
        }
        
        // Validar status
        $validStatuses = ['available', 'low_stock', 'out_of_stock', 'discontinued'];
        $this->inArray('status', $data['status'] ?? '', $validStatuses);
        
        // Validar códigos únicos se fornecidos
        if (!empty($data['serial_number'])) {
            $this->unique('serial_number', $data['serial_number'], 'products', 'serial_number', $productId);
        }
        
        if (!empty($data['barcode'])) {
            $this->unique('barcode', $data['barcode'], 'products', 'barcode', $productId);
        }
        
        if (!empty($data['qr_code'])) {
            $this->unique('qr_code', $data['qr_code'], 'products', 'qr_code', $productId);
        }
        
        return !$this->hasErrors();
    }
}

/**
 * Classe para validação específica de máquinas
 */
class MachineValidator extends Validator {
    public function validateMachine($data, $isEdit = false, $machineId = null) {
        // Campos obrigatórios
        $this->required('name', $data['name'] ?? '');
        $this->required('type', $data['type'] ?? '');
        $this->required('status', $data['status'] ?? '');
        
        // Validações específicas
        if (!empty($data['name'])) {
            $this->minLength('name', $data['name'], 2);
            $this->maxLength('name', $data['name'], 255);
        }
        
        if (!empty($data['description'])) {
            $this->maxLength('description', $data['description'], 1000);
        }
        
        // Validar status
        $validStatuses = ['ready', 'in_use', 'maintenance', 'out_of_order', 'retired'];
        $this->inArray('status', $data['status'] ?? '', $validStatuses);
        
        // Validar códigos únicos se fornecidos
        if (!empty($data['serial_number'])) {
            $this->unique('serial_number', $data['serial_number'], 'ready_machines', 'serial_number', $machineId);
        }
        
        if (!empty($data['barcode'])) {
            $this->unique('barcode', $data['barcode'], 'ready_machines', 'barcode', $machineId);
        }
        
        if (!empty($data['qr_code'])) {
            $this->unique('qr_code', $data['qr_code'], 'ready_machines', 'qr_code', $machineId);
        }
        
        return !$this->hasErrors();
    }
}

/**
 * Classe para validação de usuários
 */
class UserValidator extends Validator {
    public function validateUser($data, $isEdit = false, $userId = null) {
        // Campos obrigatórios
        $this->required('username', $data['username'] ?? '');
        $this->required('email', $data['email'] ?? '');
        $this->required('role', $data['role'] ?? '');
        
        if (!$isEdit) {
            $this->required('password', $data['password'] ?? '');
        }
        
        // Validações específicas
        if (!empty($data['username'])) {
            $this->minLength('username', $data['username'], 3);
            $this->maxLength('username', $data['username'], 50);
            $this->unique('username', $data['username'], 'users', 'username', $userId);
        }
        
        if (!empty($data['email'])) {
            $this->email('email', $data['email']);
            $this->unique('email', $data['email'], 'users', 'email', $userId);
        }
        
        if (!empty($data['password'])) {
            $this->minLength('password', $data['password'], 6);
        }
        
        // Validar role
        $validRoles = ['admin', 'user', 'administrativo'];
        $this->inArray('role', $data['role'] ?? '', $validRoles);
        
        return !$this->hasErrors();
    }
}

/**
 * Função auxiliar para exibir erros de validação
 */
function displayValidationErrors($errors) {
    if (empty($errors)) return '';
    
    $html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    $html .= '<h6><i class="fas fa-exclamation-triangle me-2"></i>Corrija os seguintes erros:</h6>';
    $html .= '<ul class="mb-0">';
    
    foreach ($errors as $field => $fieldErrors) {
        foreach ($fieldErrors as $error) {
            $html .= '<li>' . htmlspecialchars($error) . '</li>';
        }
    }
    
    $html .= '</ul>';
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $html .= '</div>';
    
    return $html;
}
