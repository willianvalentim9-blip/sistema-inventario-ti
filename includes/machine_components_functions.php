<?php
/**
 * Funções para gerenciar relacionamentos entre máquinas e produtos
 * Integra produtos aos componentes das máquinas prontas
 */

/**
 * Salva os componentes da máquina
 * 
 * @param PDO $pdo Conexão com banco de dados
 * @param int $machine_id ID da máquina
 * @param string $components_json JSON com componentes selecionados
 * @return bool true se bem-sucedido
 */
function saveMachineComponents($pdo, $machine_id, $components_json) {
    try {
        $components = json_decode($components_json, true);

        // DEBUG MELHORADO
        error_log("═══════════════════════════════════════════════════════════");
        error_log("DEBUG saveMachineComponents: INICIANDO");
        error_log("Machine ID: " . $machine_id);
        error_log("JSON recebido: " . $components_json);
        error_log("Array decoded: " . var_export($components, true));
        error_log("═══════════════════════════════════════════════════════════");

        if (!is_array($components) || empty($components)) {
            error_log("⚠️ Nenhum componente para salvar");
            return true; // Sem componentes é permitido
        }

        // 🔍 NOVO DEBUG: Conta total de componentes antes de processar
        $totalComponentsBefore = 0;
        foreach ($components as $type => $data) {
            if (is_array($data)) {
                foreach ($data as $item) {
                    if (is_array($item) && isset($item['productId'])) {
                        $totalComponentsBefore++;
                    }
                }
            } elseif (is_array($data) && isset($data['productId'])) {
                $totalComponentsBefore++;
            }
        }
        error_log("📊 TOTAL DE COMPONENTES NO JSON RECEBIDO: " . $totalComponentsBefore);

        // Verifica se já existe uma transação ativa
        $shouldCommit = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $shouldCommit = true;
            error_log("✅ Transação iniciada em saveMachineComponents");
        } else {
            error_log("⚠️ Transação já ativa, usando transação existente");
        }

        // Remove componentes anteriores se houver
        $stmt = $pdo->prepare("DELETE FROM machine_products WHERE machine_id = ?");
        $stmt->execute([$machine_id]);
        error_log("✅ Componentes anteriores removidos");

        // IMPORTANTE: Agrupa componentes iguais para contar quantidade correta
        // Exemplo: 2x HD 500GB → salva como quantity=2, não 2 linhas separadas
        $aggregatedComponents = [];

        foreach ($components as $componentType => $component) {
            // NOVO: Detecta se é múltiplo (array de arrays) ou único (array simples)
            $componentsToProcess = [];
            
            error_log("-----------------------------------------------------------");
            error_log("Processando tipo: {$componentType}");
            error_log("Dados brutos: " . var_export($component, true));
            
            // ✅ FIX: Detecta se é array de múltiplos componentes
            // Múltiplos: [{"productId": 1}, {"productId": 2}]
            // Único: {"productId": 1}
            if (is_array($component) && !empty($component)) {
                // Verifica se o primeiro elemento é array (array de múltiplos)
                $firstElement = reset($component);
                if (is_array($firstElement)) {
                    // É um array de múltiplos componentes (ex: HDD com 2 itens)
                    error_log("🔀 Detectado MÚLTIPLOS componentes para {$componentType}");
                    $componentsToProcess = $component;
                } else {
                    // É um único componente com struktura {"productId": ..., "productName": ...}
                    error_log("🔀 Detectado ÚNICO componente para {$componentType}");
                    $componentsToProcess = [$component];
                }
            } else {
                // Pode ser string, número, ou outro tipo
                error_log("🔀 Estrutura não-array para {$componentType}");
                $componentsToProcess = [$component];
            }
            
            // Agrupa componentes iguais
            foreach ($componentsToProcess as $singleComponent) {
                $productId = null;
                $componentName = null;

                error_log("  Processando item: " . var_export($singleComponent, true));

                if (is_array($singleComponent)) {
                    // Estrutura: {"productId": 123, "productName": "..."}
                    $componentName = $singleComponent['name'] ?? $singleComponent['productName'] ?? null;
                    $productId = isset($singleComponent['productId']) ? intval($singleComponent['productId']) : null;

                    error_log("    Nome: " . ($componentName ?: 'N/A'));
                    error_log("    Product ID: " . ($productId ?: 'NULL'));

                    // Se não tem productId, tenta buscar por nome EXATO
                    if (!$productId && $componentName) {
                        error_log("    ⚠️ Tentando buscar produto por nome exato: '{$componentName}'");
                        $searchStmt = $pdo->prepare("SELECT id, quantity FROM products WHERE name = ? AND status = 'available' LIMIT 1");
                        $searchStmt->execute([$componentName]);
                        $found = $searchStmt->fetch(PDO::FETCH_ASSOC);
                        if ($found) {
                            $productId = $found['id'];
                            error_log("    ✅ Produto encontrado por nome! ID: {$productId}, Estoque: {$found['quantity']}");
                        } else {
                            error_log("    ❌ Produto não encontrado por nome");
                        }
                    }
                } elseif (is_numeric($singleComponent)) {
                    // Se for apenas um número, é o ID
                    $productId = intval($singleComponent);
                    error_log("    Product ID direto: {$productId}");
                } else {
                    // Se for string, é o nome
                    $componentName = $singleComponent;
                    error_log("    Tentando buscar por nome string: '{$componentName}'");
                    $searchStmt = $pdo->prepare("SELECT id, quantity FROM products WHERE name = ? AND status = 'available' LIMIT 1");
                    $searchStmt->execute([$componentName]);
                    $found = $searchStmt->fetch(PDO::FETCH_ASSOC);
                    if ($found) {
                        $productId = $found['id'];
                        error_log("    ✅ Produto encontrado! ID: {$productId}, Estoque: {$found['quantity']}");
                    }
                }

                // Se não tem productId, componente foi digitado manualmente
                if (!$productId) {
                    error_log("    ⚠️ Componente '{$componentType}' sem product_id - NÃO será vinculado ao estoque");
                    error_log("    ⚠️ Texto: '{$componentName}'");
                    continue;
                }

                // Valida se o produto existe
                $checkStmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE id = ?");
                $checkStmt->execute([$productId]);
                $product = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    error_log("    ❌ ERRO: Produto ID {$productId} não encontrado no banco!");
                    throw new Exception("Produto ID {$productId} não encontrado");
                }

                error_log("    ✅ Produto validado: {$product['name']} (Estoque atual: {$product['quantity']})");

                // CRUCIAL: Agrupa por product_id + component_type
                // Se mesmo produto + tipo aparece múltiplas vezes, soma as quantidades
                $key = "{$productId}_{$componentType}";
                if (!isset($aggregatedComponents[$key])) {
                    $aggregatedComponents[$key] = [
                        'machine_id' => $machine_id,
                        'product_id' => $productId,
                        'component_type' => $componentType,
                        'quantity' => 0,
                        'product_name' => $product['name']
                    ];
                }
                $aggregatedComponents[$key]['quantity']++;
                error_log("    ✅ Produto agregado: {$product['name']} (qty: {$aggregatedComponents[$key]['quantity']})");
            }
        }

        // Agora insere componentes agregados com quantidade correta
        $stmtInsert = $pdo->prepare("
            INSERT INTO machine_products (machine_id, product_id, component_type, quantity, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $componentsInserted = 0;
        $totalQuantityInserted = 0;
        foreach ($aggregatedComponents as $key => $comp) {
            $stmtInsert->execute([
                $comp['machine_id'],
                $comp['product_id'],
                $comp['component_type'],
                $comp['quantity']
            ]);
            $componentsInserted++;
            $totalQuantityInserted += $comp['quantity'];
            error_log("✅ Inserido: {$comp['product_name']} x{$comp['quantity']} ({$comp['component_type']})");
        }

        error_log("📊 RESUMO DE INSERÇÃO:");
        error_log("  - Linhas inseridas na machine_products: {$componentsInserted}");
        error_log("  - Total de unidades inseridas: {$totalQuantityInserted}");
        error_log("  - JSON tinha {$totalComponentsBefore} elementos, foi agregado em {$componentsInserted} linhas");

        if ($shouldCommit) {
            $pdo->commit();
            error_log("✅ Transação comitada em saveMachineComponents");
        }

        error_log("═══════════════════════════════════════════════════════════");
        error_log("═══════════════════════════════════════════════════════════");
        error_log("✅ saveMachineComponents CONCLUÍDO");
        error_log("Componentes inseridos: {$componentsInserted}");
        error_log("═══════════════════════════════════════════════════════════");

        if ($shouldCommit) {
            $pdo->commit();
            error_log("✅ Transação comitada em saveMachineComponents");
        }

        return true;

    } catch (Exception $e) {
        if ($shouldCommit && $pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("❌ Rollback em saveMachineComponents");
        }
        error_log("❌ ERRO em saveMachineComponents: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

/**
 * Busca os componentes de uma máquina
 * Retorna dados agregados (quantity = número de unidades)
 * 
 * @param PDO $pdo Conexão com banco de dados
 * @param int $machine_id ID da máquina
 * @return array Array com os componentes
 */
function getMachineComponents($pdo, $machine_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                mp.id,
                mp.product_id,
                mp.component_type,
                mp.quantity,
                p.name,
                p.manufacturer,
                p.model,
                p.category,
                p.price,
                p.quantity as stock_available
            FROM machine_products mp
            JOIN products p ON mp.product_id = p.id
            WHERE mp.machine_id = ?
            ORDER BY mp.component_type ASC
        ");
        
        $stmt->execute([$machine_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erro ao buscar componentes: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca os componentes de uma máquina DESDOBRADOS para o frontend
 * Importante: Expande quantity em múltiplos itens do mesmo produto
 * Ex: HD 500GB (quantity=2) retorna como 2 items separados
 * 
 * @param PDO $pdo Conexão com banco de dados
 * @param int $machine_id ID da máquina
 * @return array Array com os componentes desdobrados (para consolidação no frontend)
 */
function getMachineComponentsForFrontend($pdo, $machine_id) {
    try {
        // Primeiro obtém os componentes agregados
        $components = getMachineComponents($pdo, $machine_id);
        
        if (empty($components)) {
            return [];
        }
        
        // Desdobra cada componente segundo sua quantidade
        // Isso permite que o frontend consolide corretamente em "(Nx)"
        $expandedComponents = [];
        
        foreach ($components as $component) {
            // Para cada unidade na quantity, cria um item separado
            for ($i = 0; $i < $component['quantity']; $i++) {
                $expandedComponents[] = [
                    'id' => $component['product_id'],
                    'name' => $component['name'],
                    'product_id' => $component['product_id'],
                    'component_type' => $component['component_type'],
                    'quantity' => 1  // Frontend verá cada item como quantidade 1
                ];
            }
        }
        
        error_log("🔄 getMachineComponentsForFrontend: Expandido de " . count($components) . " para " . count($expandedComponents) . " itens");
        
        // 🔍 DEBUG: Mostra os itens desdobrados
        foreach ($expandedComponents as $i => $item) {
            error_log("  [$i] {$item['name']} (product_id: {$item['product_id']})");
        }
        
        return $expandedComponents;
        
    } catch (Exception $e) {
        error_log("Erro ao buscar componentes para frontend: " . $e->getMessage());
        return [];
    }
}

/**
 * Retira estoque dos produtos quando máquina é criada
 * 
 * @param PDO $pdo Conexão com banco de dados
 * @param int $machine_id ID da máquina
 * @param int $quantity Quantidade de máquinas
 * @return bool true se bem-sucedido
 */
function deductProductsStock($pdo, $machine_id, $quantity = 1) {
    try {
        error_log("═══════════════════════════════════════════════════════════");
        error_log("DEBUG deductProductsStock: INICIANDO");
        error_log("Machine ID: {$machine_id}");
        error_log("Quantidade de máquinas: {$quantity}");

        // Busca o nome da máquina para incluir no histórico
        $machineStmt = $pdo->prepare("SELECT name FROM ready_machines WHERE id = ?");
        $machineStmt->execute([$machine_id]);
        $machineData = $machineStmt->fetch(PDO::FETCH_ASSOC);
        $machineName = $machineData['name'] ?? "Máquina #{$machine_id}";
        error_log("Máquina: {$machineName}");

        $components = getMachineComponents($pdo, $machine_id);

        error_log("Componentes encontrados: " . count($components));
        error_log("Detalhes: " . var_export($components, true));

        if (empty($components)) {
            error_log("⚠️ ATENÇÃO: Nenhum componente encontrado para máquina {$machine_id}");
            error_log("⚠️ ESTOQUE NÃO SERÁ DEDUZIDO!");
            error_log("═══════════════════════════════════════════════════════════");
            return true;
        }

        // Verifica se já existe uma transação ativa
        $shouldCommit = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $shouldCommit = true;
            error_log("✅ Transação iniciada em deductProductsStock");
        } else {
            error_log("⚠️ Transação já ativa, usando transação existente");
        }

        $deductedCount = 0;
        $totalDeducted = 0;

        foreach ($components as $component) {
            $productId = $component['product_id'];
            $productName = $component['name'];
            $usedQuantity = $component['quantity'] * $quantity;
            $currentStock = $component['stock_available'];

            error_log("-----------------------------------------------------------");
            error_log("Processando dedução:");
            error_log("  Produto: {$productName} (ID: {$productId})");
            error_log("  Estoque atual: {$currentStock}");
            error_log("  quantity em machine_products: {$component['quantity']}");
            error_log("  Número de máquinas: {$quantity}");
            error_log("  Quantidade a deduzir: {$usedQuantity} (= {$component['quantity']} * {$quantity})");

            // Valida se há estoque suficiente
            if ($currentStock < $usedQuantity) {
                error_log("❌ ERRO: Estoque insuficiente!");
                throw new Exception(
                    "Estoque insuficiente de {$productName}. " .
                    "Disponível: {$currentStock}, Necessário: {$usedQuantity}"
                );
            }

            // ALERTA SE ESTOQUE FICARÁ ZERADO
            $newStock = $currentStock - $usedQuantity;
            if ($newStock == 0) {
                error_log("⚠️⚠️⚠️ ALERTA: Produto '{$productName}' ficará com ESTOQUE ZERO!");
            } elseif ($newStock < 5) {
                error_log("⚠️ ALERTA: Produto '{$productName}' ficará com estoque baixo ({$newStock} unidades)");
            }

            // Diminui estoque
            $stmt = $pdo->prepare("
                UPDATE products
                SET quantity = quantity - ?
                WHERE id = ?
            ");
            $stmt->execute([$usedQuantity, $productId]);

            error_log("✅ Estoque deduzido: {$currentStock} → {$newStock}");
            $totalDeducted += $usedQuantity;

            // Registra movimento de estoque
            logProductMovement(
                $productId,
                $_SESSION['user_id'] ?? 1,
                'saida',
                $usedQuantity,
                $currentStock,
                $newStock,
                "Adicionado à máquina: {$machineName} (ID: {$machine_id})"
            );

            error_log("✅ Movimento registrado no histórico");

            $deductedCount++;
        }

        if ($shouldCommit) {
            $pdo->commit();
            error_log("✅ Transação comitada em deductProductsStock");
        }

        error_log("═══════════════════════════════════════════════════════════");
        error_log("📊 RESUMO DA DEDUCTION:");
        error_log("  - Produtos processados: {$deductedCount}");
        error_log("  - Total de unidades deduzidas: {$totalDeducted}");
        error_log("═══════════════════════════════════════════════════════════");
        error_log("✅ deductProductsStock CONCLUÍDO");
        error_log("═══════════════════════════════════════════════════════════");

        return true;

    } catch (Exception $e) {
        if ($shouldCommit && $pdo->inTransaction()) {
            $pdo->rollBack();
            error_log("❌ Rollback em deductProductsStock");
        }
        error_log("❌ ERRO em deductProductsStock: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

/**
 * Remove componentes de uma máquina e devolve estoque se necessário
 *
 * @param PDO $pdo Conexão com banco de dados
 * @param int $machine_id ID da máquina
 * @param bool $return_to_stock Se true, devolve os componentes ao estoque
 * @param string $reason Motivo da remoção (para log)
 * @return bool true se bem-sucedido
 */
function removeMachineComponents($pdo, $machine_id, $return_to_stock = true, $reason = 'Máquina removida') {
    try {
        // Busca o nome da máquina para incluir no histórico
        $machineStmt = $pdo->prepare("SELECT name FROM ready_machines WHERE id = ?");
        $machineStmt->execute([$machine_id]);
        $machineData = $machineStmt->fetch(PDO::FETCH_ASSOC);
        $machineName = $machineData['name'] ?? "Máquina #{$machine_id}";

        // Antes de remover, devolve o estoque se foi descontado
        $components = getMachineComponents($pdo, $machine_id);

        if (!empty($components) && $return_to_stock) {
            foreach ($components as $component) {
                $productId = $component['product_id'];
                $returnQuantity = $component['quantity'];

                // Obtém quantidade atual do produto
                $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $currentStock = $stmt->fetchColumn();

                // Devolve ao estoque
                $stmt = $pdo->prepare("
                    UPDATE products
                    SET quantity = quantity + ?
                    WHERE id = ?
                ");
                $stmt->execute([$returnQuantity, $productId]);

                // Registra movimento de estoque
                logProductMovement(
                    $productId,
                    $_SESSION['user_id'] ?? 1,
                    'entrada',
                    $returnQuantity,
                    $currentStock,
                    $currentStock + $returnQuantity,
                    "Devolvido de: {$machineName} ({$reason}, ID: {$machine_id})"
                );
            }
        }

        // Remove os componentes
        $stmt = $pdo->prepare("DELETE FROM machine_products WHERE machine_id = ?");
        $stmt->execute([$machine_id]);

        return true;

    } catch (Exception $e) {
        error_log("Erro ao remover componentes: " . $e->getMessage());
        throw $e; // Propaga a exceção para que a transação externa faça rollback
    }
}

/**
 * Devolve estoque dos componentes de uma máquina vendida ou descartada
 *
 * @param PDO $pdo Conexão com banco de dados
 * @param int $machine_id ID da máquina
 * @param string $operation Tipo de operação: 'venda' ou 'descarte'
 * @return bool true se bem-sucedido
 */
function returnMachineComponentsToStock($pdo, $machine_id, $operation = 'descarte') {
    $reason_map = [
        'venda' => 'Máquina vendida',
        'descarte' => 'Máquina descartada',
        'exclusao' => 'Máquina excluída'
    ];

    $reason = $reason_map[$operation] ?? 'Devolução de componentes';
    return removeMachineComponents($pdo, $machine_id, true, $reason);
}

/**
 * Gera relatório visual dos componentes de uma máquina
 * 
 * @param PDO $pdo Conexão com banco de dados
 * @param int $machine_id ID da máquina
 * @return string HTML com relatório
 */
function getMachineComponentsHTML($pdo, $machine_id) {
    $components = getMachineComponents($pdo, $machine_id);
    
    if (empty($components)) {
        return '<p class="text-muted"><i class="fas fa-info-circle"></i> Nenhum componente registrado</p>';
    }
    
    $html = '<div class="components-table">';
    $html .= '<table class="table table-sm table-hover">';
    $html .= '<thead><tr>';
    $html .= '<th><i class="fas fa-microchip"></i> Tipo</th>';
    $html .= '<th>Produto</th>';
    $html .= '<th class="text-center">Qtd</th>';
    $html .= '<th>Preço Unit.</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    
    $total = 0;
    foreach ($components as $component) {
        $subtotal = ($component['price'] ?? 0) * $component['quantity'];
        $total += $subtotal;
        
        $html .= '<tr>';
        $html .= '<td><strong>' . htmlspecialchars($component['component_type']) . '</strong></td>';
        $html .= '<td>';
        $html .= htmlspecialchars($component['name']);
        if ($component['manufacturer']) {
            $html .= '<br><small class="text-muted">' . htmlspecialchars($component['manufacturer']);
            if ($component['model']) {
                $html .= ' - ' . htmlspecialchars($component['model']);
            }
            $html .= '</small>';
        }
        $html .= '</td>';
        $html .= '<td class="text-center">' . $component['quantity'] . '</td>';
        $html .= '<td class="text-right">R$ ' . number_format($component['price'] ?? 0, 2, ',', '.') . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '<tr class="table-info">';
    $html .= '<td colspan="3"><strong>Total em Componentes:</strong></td>';
    $html .= '<td class="text-right"><strong>R$ ' . number_format($total, 2, ',', '.') . '</strong></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

?>
