# Fix: Dedução de Estoque de Componentes não estava funcionando

## Problema Identificado
Quando componentes eram adicionados a uma máquina e a máquina era salva, os componentes eram salvos corretamente na tabela `machine_products`, MAS **o estoque não estava sendo deduzido** da tabela `products`.

### Raiz do Problema
No arquivo de debug `debug_components.log`, encontramos o erro:
```
[2025-12-17 17:32:16] ❌ ERRO ao salvar componentes da máquina: There is no active transaction
[2025-12-17 17:32:16] ❌ Stack trace: #0 C:\xampp\htdocs\sistema5\includes\machine_components_functions.php(213): PDO->commit()
```

**Causa**: Em `machine_components_functions.php`, a função `saveMachineComponents` tinha:
1. Um **COMMIT DUPLICADO** (chamado 2 vezes)
2. A primeira chamada de commit fechava a transação que estava sendo iniciada em `add_machine.php`
3. A segunda chamada de commit tentava fazer commit de uma transação inexistente → erro!

Além disso, a exceção era capturada silenciosamente em `add_machine.php`, permitindo que a máquina fosse criada mesmo com erro.

## Solução Aplicada

### 1. Remover Commit Duplicado em `includes/machine_components_functions.php`
**Arquivo**: `includes/machine_components_functions.php` (linhas 200-217)

**Antes**:
```php
        if ($shouldCommit) {
            $pdo->commit();
            error_log("✅ Transação comitada em saveMachineComponents");
        }
        // ... código ...
        if ($shouldCommit) {
            $pdo->commit();  // ❌ DUPLICADO!
            error_log("✅ Transação comitada em saveMachineComponents");
        }
        return true;
```

**Depois**:
```php
        if ($shouldCommit) {
            $pdo->commit();
            error_log("✅ Transação comitada em saveMachineComponents");
        }
        // ... código (sem o commit duplicado) ...
        return true;
```

### 2. Re-lançar Exceção em `modules/machines/add_machine.php`
**Arquivo**: `modules/machines/add_machine.php` (linhas 210-240)

**Antes**:
```php
                    } catch (Exception $e) {
                        // Registra erro mas NÃO impede a criação da máquina ❌
                        debug_log("❌ ERRO ao salvar componentes da máquina: " . $e->getMessage());
                        // ... log ...
                        $_SESSION['error_message'] = 'ATENÇÃO: Máquina criada, mas houve erro...';
                    }
                }
                
                header("Location: ready_machines.php");
                exit();
            }
            
        } catch (PDOException $e) {
            $error_message = 'Erro ao adicionar máquina: ' . $e->getMessage();
        }
```

**Depois**:
```php
                    } catch (Exception $e) {
                        // ❌ ERRO CRÍTICO: Faz rollback e re-lança a exceção
                        debug_log("❌ ERRO CRÍTICO ao salvar componentes da máquina: " . $e->getMessage());
                        
                        // Faz rollback de toda a transação (máquina NÃO será criada)
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                            debug_log("❌ Rollback completo - Máquina NÃO será criada");
                        }
                        
                        // Re-lança a exceção para ser tratada no catch externo
                        throw $e;
                    }
                }
                
                header("Location: ready_machines.php");
                exit();
            }
            
        } catch (PDOException $e) {
            $error_message = 'Erro ao adicionar máquina: ' . $e->getMessage();
            error_log("Erro ao adicionar máquina: " . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = 'Erro ao adicionar máquina: ' . $e->getMessage();
            error_log("Erro ao adicionar máquina: " . $e->getMessage());
        }
```

## Fluxo Agora (Correto)

```
1. Usuário clica em salvar máquina com componentes
2. add_machine.php inicia transação: $pdo->beginTransaction()
3. Insere máquina: INSERT INTO ready_machines
4. Chama saveMachineComponents():
   - Inicia sub-transação? NÃO (detecta que já há transação ativa)
   - Insere componentes: INSERT INTO machine_products
   - Faz commit? NÃO (deixa a transação externa gerenciar)
5. Chama deductProductsStock():
   - Busca componentes que foram inseridos
   - Deduz do estoque: UPDATE products SET quantity = quantity - ?
   - Registra no histórico: logProductMovement()
   - Faz commit? NÃO (deixa a transação externa gerenciar)
6. Se tudo OK: $pdo->commit() (transação externa)
   ✅ Máquina, componentes E estoque deduzido são salvos atomicamente
7. Se houver erro em qualquer etapa:
   ❌ $pdo->rollBack() (tudo é desfeito, máquina NÃO é criada)
```

## Impacto

- ✅ Componentes agora são adicionados corretamente
- ✅ Estoque é deduzido automaticamente
- ✅ Se houver erro, TUDO é revertido (transação atômica)
- ✅ Não há mais máquinas criadas "sem estoque deduzido"

## Próximas Verificações

- [ ] Testar criação de máquina com componentes - verificar estoque deduzido
- [ ] Testar edição de máquina com componentes - verificar devolução e dedução
- [ ] Testar cenário onde estoque é insuficiente - deve rejeitar tudo
- [ ] Verificar logs em `debug_components.log` e PHP error_log

