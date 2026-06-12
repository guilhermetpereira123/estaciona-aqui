<?php
/*
 * --------------------------------------------------------
 * Arquivo: api_corrigido.php
 * ATUALIZAÇÃO:
 * - Implementado sistema de Check-in / Check-out (Backend)
 * - Novas ações: 'checkin_reservation', 'checkout_reservation'
 * - 'makeReservation' e 'cancelReservation' atualizadas.
 * --------------------------------------------------------
 */

session_start();
date_default_timezone_set('America/Sao_Paulo');

// --- 1. Configuração do Banco de Dados ---
$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "estacionamento_db";

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados: ' . $e->getMessage()]);
    exit;
}

header('Content-Type: application/json');

// --- 3. Roteamento de Ações ---
$action = $_REQUEST['action'] ?? null;

switch ($action) {
    // Registros
    case 'register_user': registerUser($pdo); break;
    case 'register_owner': registerOwner($pdo); break;
    // Sessão
    case 'login_user': loginUser($pdo); break;
    case 'login_owner': loginOwner($pdo); break;
    case 'logout': logout(); break;
    case 'check_session': checkSession(); break;
    // Ações do Usuário
    case 'find_parking': findParking($pdo); break;
    case 'make_reservation': makeReservation($pdo); break;
    case 'get_my_reservations': getMyReservations($pdo); break;
    case 'get_all_bairros': getAllBairros($pdo); break;
    case 'cancel_reservation': cancelReservation($pdo); break;
    case 'checkin_reservation': checkinReservation($pdo); break; // <-- NOVO
    case 'checkout_reservation': checkoutReservation($pdo); break; // <-- NOVO
    // Ações do Dono
    case 'register_parking': registerParking($pdo); break;
    case 'get_my_notifications': getMyNotifications($pdo); break;
    case 'get_my_estacionamentos': getMyEstacionamentos($pdo); break;
    case 'get_plans_for_estacionamento': getPlansForEstacionamento($pdo); break;
    case 'add_plan': addPlan($pdo); break;
    case 'delete_plan': deletePlan($pdo); break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Ação inválida ou não especificada.']);
        break;
}

// --- 4. Funções de Registro e Sessão ---
// (Sem mudanças)
function registerUser($pdo) {
    $nome = $_POST['nome']; $cpf = $_POST['cpf']; $email = $_POST['email'];
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $carro = $_POST['carro']; $placa = $_POST['placa'];
    try {
        $sql = "INSERT INTO usuarios (nome, cpf, email, senha, carro, placa) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $cpf, $email, $senha_hash, $carro, $placa]);
        echo json_encode(['status' => 'success', 'message' => 'Usuário cadastrado com sucesso!']);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro: Email ou CPF já cadastrado.']); }
}
function registerOwner($pdo) {
    $nome = $_POST['nome']; $email = $_POST['email'];
    $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    try {
        $sql = "INSERT INTO donos (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $email, $senha_hash]);
        echo json_encode(['status' => 'success', 'message' => 'Dono cadastrado com sucesso!']);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro: Email já cadastrado.']); }
}
function loginUser($pdo) {
    $email = $_POST['email']; $senha = $_POST['senha'];
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['role'] = 'user';
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_placa'] = $user['placa'];
        $_SESSION['user_carro'] = $user['carro'];
        $data = ['id' => $user['id'], 'nome' => $user['nome'], 'placa' => $user['placa']];
        echo json_encode(['status' => 'success', 'message' => 'Login bem-sucedido!', 'role' => 'user', 'data' => $data]);
    } else { echo json_encode(['status' => 'error', 'message' => 'Email ou senha inválidos.']); }
}
function loginOwner($pdo) {
    $email = $_POST['email']; $senha = $_POST['senha'];
    $stmt = $pdo->prepare("SELECT * FROM donos WHERE email = ?");
    $stmt->execute([$email]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($owner && password_verify($senha, $owner['senha'])) {
        $_SESSION['role'] = 'owner';
        $_SESSION['owner_id'] = $owner['id'];
        $_SESSION['owner_nome'] = $owner['nome'];
        $data = ['id' => $owner['id'], 'nome' => $owner['nome']];
        echo json_encode(['status' => 'success', 'message' => 'Login bem-sucedido!', 'role' => 'owner', 'data' => $data]);
    } else { echo json_encode(['status' => 'error', 'message' => 'Email ou senha inválidos.']); }
}
function logout() {
    session_unset(); session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logout realizado com sucesso.']);
}
function checkSession() {
    if (isset($_SESSION['role'])) {
        $data = [];
        if ($_SESSION['role'] == 'user') {
            $data = ['id' => $_SESSION['user_id'], 'nome' => $_SESSION['user_nome'], 'placa' => $_SESSION['user_placa'], 'carro' => $_SESSION['user_carro'] ?? 'Não especificado'];
        } else if ($_SESSION['role'] == 'owner') {
            $data = ['id' => $_SESSION['owner_id'], 'nome' => $_SESSION['owner_nome']];
        }
        echo json_encode(['status' => 'success', 'role' => $_SESSION['role'], 'data' => $data]);
    } else { echo json_encode(['status' => 'none', 'message' => 'Nenhuma sessão ativa.']); }
}

// --- 5. Funções de Ações do Dono ---
// (Sem mudanças)
function registerParking($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    $dono_id = $_SESSION['owner_id'];
    $endereco = $_POST['endereco']; $bairro = $_POST['bairro'];
    $total_vagas = $_POST['total_vagas'];
    $hora_abertura = !empty($_POST['hora_abertura']) ? $_POST['hora_abertura'] : null;
    $hora_fechamento = !empty($_POST['hora_fechamento']) ? $_POST['hora_fechamento'] : null;
    $foto_url = null;
    $servicos_array = $_POST['servicos'] ?? [];
    $outros_servicos_string = implode(',', $servicos_array);
    try {
        $sql_check = "SELECT id FROM estacionamentos WHERE endereco = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$endereco]);
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Este endereço já está cadastrado no sistema.']);
            return;
        }
        if (isset($_FILES['foto_local']) && $_FILES['foto_local']['error'] == 0) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]+/", "", basename($_FILES['foto_local']['name']));
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['foto_local']['tmp_name'], $targetPath)) { $foto_url = $targetPath; }
        }
        $sql = "INSERT INTO estacionamentos
                  (dono_id, endereco, bairro, total_vagas, vagas_disponiveis,
                   outros_servicos, foto_url, hora_abertura, hora_fechamento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dono_id, $endereco, $bairro, $total_vagas, $total_vagas,
            $outros_servicos_string,
            $foto_url, $hora_abertura, $hora_fechamento
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Estacionamento cadastrado com sucesso!']);
    } catch (PDOException $e) {
        if ($foto_url && file_exists($foto_url)) { unlink($foto_url); }
        echo json_encode(['status' => 'error', 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
}
function getMyNotifications($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    $dono_id = $_SESSION['owner_id'];
    try {
        $sql = "SELECT id, mensagem, data_criacao, lida FROM notificacoes WHERE dono_id = ? ORDER BY data_criacao DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dono_id]);
        $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $notificacoes]);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar notificações.']); }
}
function getMyEstacionamentos($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    $dono_id = $_SESSION['owner_id'];
    try {
        $sql = "SELECT id, endereco FROM estacionamentos WHERE dono_id = ? ORDER BY endereco";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dono_id]);
        $estacionamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $estacionamentos]);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar seus estacionamentos.']); }
}
function getPlansForEstacionamento($pdo) {
    if (!isset($_SESSION['role'])) { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    $estacionamento_id = $_GET['estacionamento_id'];
    try {
        $sql_plans = "SELECT
                        id,
                        valor,
                        periodo_horas,
                        IF(periodo_horas = 1, '1 Hora', CONCAT(periodo_horas, ' Horas')) AS descricao
                      FROM planos_preco
                      WHERE estacionamento_id = ?
                      ORDER BY valor";
        $stmt_plans = $pdo->prepare($sql_plans);
        $stmt_plans->execute([$estacionamento_id]);
        $planos = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);
        $sql_info = "SELECT hora_abertura, hora_fechamento FROM estacionamentos WHERE id = ?";
        $stmt_info = $pdo->prepare($sql_info);
        $stmt_info->execute([$estacionamento_id]);
        $parking_info = $stmt_info->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'status' => 'success',
            'data' => $planos,
            'parking_info' => $parking_info
        ]);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar planos.']); }
}
function addPlan($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    $dono_id = $_SESSION['owner_id'];
    $estacionamento_id = $_POST['estacionamento_id'];
    $periodo_horas = $_POST['periodo_horas'];
    $valor = $_POST['valor'];
    if (!is_numeric($periodo_horas) || $periodo_horas <= 0 || !is_numeric($valor) || $valor <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Período e valor devem ser números positivos.']);
        return;
    }
    try {
        $sql_check = "SELECT id FROM estacionamentos WHERE id = ? AND dono_id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$estacionamento_id, $dono_id]);
        if ($stmt_check->fetch()) {
            $sql_insert = "INSERT INTO planos_preco (estacionamento_id, periodo_horas, valor) VALUES (?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([$estacionamento_id, $periodo_horas, $valor]);
            echo json_encode(['status' => 'success', 'message' => 'Plano adicionado com sucesso!']);
        } else { echo json_encode(['status' => 'error', 'message' => 'Erro: Estacionamento não encontrado ou não pertence a você.']); }
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro ao adicionar plano.']); }
}
function deletePlan($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    $dono_id = $_SESSION['owner_id']; $plano_id = $_POST['plano_id'];
    try {
        $sql_check = "SELECT p.id FROM planos_preco AS p JOIN estacionamentos AS e ON p.estacionamento_id = e.id WHERE p.id = ? AND e.dono_id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$plano_id, $dono_id]);
        if ($stmt_check->fetch()) {
            $sql_delete = "DELETE FROM planos_preco WHERE id = ?";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->execute([$plano_id]);
            echo json_encode(['status' => 'success', 'message' => 'Plano removido com sucesso!']);
        } else { echo json_encode(['status' => 'error', 'message' => 'Erro: Plano não encontrado ou não pertence a você.']); }
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro ao remover plano.']); }
}

// --- 6. Funções de Ações do Usuário ---

function findParking($pdo) {
    $bairro_filter = $_GET['bairro'] ?? null;
    $servicos_filter = $_GET['servicos'] ?? null;
    try {
        $sql = "SELECT id, endereco, bairro, vagas_disponiveis, hora_abertura, hora_fechamento, outros_servicos
                FROM estacionamentos
                WHERE vagas_disponiveis > 0 AND status = 'aberto'";
        $params = [];
        if ($bairro_filter) {
            $sql .= " AND bairro = ?";
            $params[] = $bairro_filter;
        }
        if ($servicos_filter && is_array($servicos_filter)) {
            foreach ($servicos_filter as $servico) {
                $sql .= " AND FIND_IN_SET(?, outros_servicos) > 0";
                $params[] = $servico;
            }
        }
        $sql .= " ORDER BY bairro, endereco";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $estacionamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $estacionamentos]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar estacionamentos.']);
    }
}

// ========================================================
// (FUNÇÃO ATUALIZADA) - Recebe 'hora_prevista_inicio'
// ========================================================
function makeReservation($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado. Faça login como usuário.']);
        return;
    }
    // Dados da Sessão
    $usuario_id = $_SESSION['user_id'];
    $placa_veiculo = $_SESSION['user_placa'];
    $nome_usuario = $_SESSION['user_nome'];
    $carro_usuario = $_SESSION['user_carro'];
    // Dados do POST
    $estacionamento_id = $_POST['estacionamento_id'];
    $plano_id = $_POST['plano_id'];
    $hora_prevista_inicio_str = $_POST['hora_prevista_inicio']; // <-- NOVO

    try {
        $pdo->beginTransaction();
       
        $sql_check = "SELECT
                        p.periodo_horas, p.valor,
                        e.hora_abertura, e.hora_fechamento, e.vagas_disponiveis, e.dono_id,
                        e.endereco
                      FROM planos_preco AS p
                      JOIN estacionamentos AS e ON p.estacionamento_id = e.id
                      WHERE p.id = ? AND p.estacionamento_id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$plano_id, $estacionamento_id]);
        $data = $stmt_check->fetch(PDO::FETCH_ASSOC);
        if (!$data) { throw new Exception("Plano ou estacionamento inválido."); }
       
        $valor_do_plano = $data['valor'];
        $periodo_horas = $data['periodo_horas'];
        $dono_id = $data['dono_id'];
        $estacionamento_endereco = $data['endereco'];
        $descricao_do_plano = ($periodo_horas == 1) ? "1 Hora" : $periodo_horas . " Horas";

        if ($data['vagas_disponiveis'] <= 0) { throw new Exception("Não há mais vagas disponíveis neste local."); }
       
        // (LÓGICA DE HORÁRIO ATUALIZADA)
        if ($data['hora_abertura'] && $data['hora_fechamento']) {
            $abertura = $data['hora_abertura'];
            $fechamento = $data['hora_fechamento'];
           
            $inicio_previsto_dt = new DateTime($hora_prevista_inicio_str);
            $inicio_previsto_em_minutos = (int)$inicio_previsto_dt->format('G') * 60 + (int)$inicio_previsto_dt->format('i');
           
            $abertura_em_minutos = (int)substr($abertura, 0, 2) * 60 + (int)substr($abertura, 3, 2);
            $fechamento_em_minutos = (int)substr($fechamento, 0, 2) * 60 + (int)substr($fechamento, 3, 2);

            // 1. Valida o INÍCIO
            if ($abertura_em_minutos > $fechamento_em_minutos) { // Pernoite
                if ($inicio_previsto_em_minutos > $fechamento_em_minutos && $inicio_previsto_em_minutos < $abertura_em_minutos) {
                    throw new Exception("O horário de início selecionado (" . $inicio_previsto_dt->format('H:i') . ") está fora do horário de funcionamento.");
                }
            } else { // Dia normal
                if ($inicio_previsto_em_minutos < $abertura_em_minutos || $inicio_previsto_em_minutos > $fechamento_em_minutos) {
                    throw new Exception("O horário de início selecionado (" . $inicio_previsto_dt->format('H:i') . ") está fora do horário de funcionamento.");
                }
            }

            // 2. Valida a DURAÇÃO
            $periodo_em_minutos = $periodo_horas * 60;
            $fim_reserva_em_minutos = $inicio_previsto_em_minutos + $periodo_em_minutos;

            if ($abertura_em_minutos < $fechamento_em_minutos) { // Dia normal
                if ($fim_reserva_em_minutos > $fechamento_em_minutos) {
                    throw new Exception("Este plano excede o horário de fechamento (" . date('H:i', strtotime($fechamento)) . ") do estacionamento.");
                }
            } else { // Pernoite
                $fim_reserva_wrapped = $fim_reserva_em_minutos % 1440;
                if ($fim_reserva_wrapped > $fechamento_em_minutos && $fim_reserva_wrapped < $abertura_em_minutos) {
                    throw new Exception("Este plano excede o horário de fechamento (" . date('H:i', strtotime($fechamento)) . ") do estacionamento.");
                }
            }
        }
       
        // (SQL ATUALIZADO)
        $sql_insert = "INSERT INTO reservas (usuario_id, estacionamento_id, plano_id, placa_veiculo, valor_pago, status, hora_prevista_inicio, hora_inicio)
                       VALUES (?, ?, ?, ?, ?, 'ativa', ?, NULL)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([$usuario_id, $estacionamento_id, $plano_id, $placa_veiculo, $valor_do_plano, $hora_prevista_inicio_str]);
       
        $sql_update = "UPDATE estacionamentos SET vagas_disponiveis = vagas_disponiveis - 1 WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$estacionamento_id]);
       
        if ($dono_id) {
            // (MENSAGEM ATUALIZADA)
            $mensagem = sprintf("Nova reserva em '%s': %s (Plano: %s) agendou para %s.",
                $estacionamento_endereco,
                $nome_usuario,
                $descricao_do_plano,
                $inicio_previsto_dt->format('d/m H:i')
            );
            $sql_notif = "INSERT INTO notificacoes (dono_id, mensagem) VALUES (?, ?)";
            $stmt_notif = $pdo->prepare($sql_notif);
            $stmt_notif->execute([$dono_id, $mensagem]);
        }
       
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Reserva realizada com sucesso!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// ========================================================
// (FUNÇÃO ATUALIZADA) - Busca 'status' e 'hora_prevista_inicio'
// ========================================================
function getMyReservations($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    $usuario_id = $_SESSION['user_id'];
    try {
        $sql = "SELECT
                    r.id, r.placa_veiculo, r.hora_prevista_inicio, r.hora_inicio, r.valor_pago, r.status,
                    e.endereco,
                    IF(p.periodo_horas = 1, '1 Hora', CONCAT(p.periodo_horas, ' Horas')) AS plano_descricao
                FROM reservas AS r
                JOIN estacionamentos AS e ON r.estacionamento_id = e.id
                LEFT JOIN planos_preco AS p ON r.plano_id = p.id
                WHERE r.usuario_id = ? AND (r.status = 'ativa' OR r.status = 'em_uso')
                ORDER BY r.hora_prevista_inicio ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id]);
        $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $reservas]);
    } catch (PDOException $e) { echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar suas reservas.']); }
}
function getAllBairros($pdo) {
    if (!isset($_SESSION['role'])) { echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']); return; }
    try {
        $sql = "SELECT DISTINCT bairro
                FROM estacionamentos
                WHERE status = 'aberto' AND vagas_disponiveis > 0
                ORDER BY bairro";
        $stmt = $pdo->query($sql);
        $bairros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $bairros]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar bairros.']);
    }
}

// ========================================================
// (FUNÇÃO ATUALIZADA) - Agora só cancela se status = 'ativa'
// ========================================================
function cancelReservation($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
        return;
    }
    $usuario_id = $_SESSION['user_id'];
    $reserva_id = $_POST['reserva_id'];
    $justificativa = $_POST['justificativa'] ?? 'Não informada';
    if (empty($justificativa)) { $justificativa = 'Não informada'; }

    try {
        $pdo->beginTransaction();
       
        $sql_check = "SELECT id, estacionamento_id, usuario_id, status FROM reservas WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$reserva_id]);
        $reserva = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$reserva) { throw new Exception("Reserva não encontrada."); }
        if ($reserva['usuario_id'] != $usuario_id) { throw new Exception("Esta reserva não pertence a você."); }
        if ($reserva['status'] == 'em_uso') { throw new Exception("Não é possível cancelar uma reserva após o check-in."); }
        if ($reserva['status'] != 'ativa') { throw new Exception("Esta reserva não pode mais ser cancelada."); }
       
        $estacionamento_id = $reserva['estacionamento_id'];

        $sql_cancel = "UPDATE reservas SET status = 'cancelada', hora_fim = NOW() WHERE id = ?";
        $stmt_cancel = $pdo->prepare($sql_cancel);
        $stmt_cancel->execute([$reserva_id]);
       
        $sql_vaga = "UPDATE estacionamentos SET vagas_disponiveis = vagas_disponiveis + 1 WHERE id = ?";
        $stmt_vaga = $pdo->prepare($sql_vaga);
        $stmt_vaga->execute([$estacionamento_id]);

        $sql_dono = "SELECT dono_id, endereco FROM estacionamentos WHERE id = ?";
        $stmt_dono = $pdo->prepare($sql_dono);
        $stmt_dono->execute([$estacionamento_id]);
        $estacionamento = $stmt_dono->fetch(PDO::FETCH_ASSOC);

        if ($estacionamento) {
            $dono_id = $estacionamento['dono_id'];
            $endereco = $estacionamento['endereco'];
            $nome_cliente = $_SESSION['user_nome'];
            $placa_cliente = $_SESSION['user_placa'];
            $mensagem = sprintf("Reserva Cancelada em '%s': %s (Placa: %s) cancelou. Motivo: %s",
                $endereco, $nome_cliente, $placa_cliente, $justificativa
            );
            $sql_notif = "INSERT INTO notificacoes (dono_id, mensagem) VALUES (?, ?)";
            $stmt_notif = $pdo->prepare($sql_notif);
            $stmt_notif->execute([$dono_id, $mensagem]);
        }
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Reserva cancelada com sucesso!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// ========================================================
// (NOVA FUNÇÃO)
// ========================================================
function checkinReservation($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
        return;
    }

    $usuario_id = $_SESSION['user_id'];
    $reserva_id = $_POST['reserva_id'];

    try {
        $pdo->beginTransaction();

        $sql_check = "SELECT id, estacionamento_id, usuario_id, status FROM reservas WHERE id = ? AND usuario_id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$reserva_id, $usuario_id]);
        $reserva = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$reserva) { throw new Exception("Reserva não encontrada."); }
        if ($reserva['status'] != 'ativa') { throw new Exception("Esta reserva não está mais aguardando check-in."); }

        // Atualiza a reserva
        $sql_update = "UPDATE reservas SET status = 'em_uso', hora_inicio = NOW() WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$reserva_id]);

        // Notifica o dono
        $sql_dono = "SELECT e.dono_id, e.endereco FROM estacionamentos e JOIN reservas r ON e.id = r.estacionamento_id WHERE r.id = ?";
        $stmt_dono = $pdo->prepare($sql_dono);
        $stmt_dono->execute([$reserva_id]);
        $estacionamento = $stmt_dono->fetch(PDO::FETCH_ASSOC);
       
        if ($estacionamento) {
            $mensagem = sprintf("CHECK-IN em '%s': Cliente %s (Placa: %s) acabou de chegar.",
                $estacionamento['endereco'],
                $_SESSION['user_nome'],
                $_SESSION['user_placa']
            );
            $sql_notif = "INSERT INTO notificacoes (dono_id, mensagem) VALUES (?, ?)";
            $stmt_notif = $pdo->prepare($sql_notif);
            $stmt_notif->execute([$estacionamento['dono_id'], $mensagem]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Check-in realizado!']);
       
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// ========================================================
// (NOVA FUNÇÃO)
// ========================================================
function checkoutReservation($pdo) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
        return;
    }

    $usuario_id = $_SESSION['user_id'];
    $reserva_id = $_POST['reserva_id'];

    try {
        $pdo->beginTransaction();

        $sql_check = "SELECT id, estacionamento_id, usuario_id, status FROM reservas WHERE id = ? AND usuario_id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$reserva_id, $usuario_id]);
        $reserva = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$reserva) { throw new Exception("Reserva não encontrada."); }
        if ($reserva['status'] != 'em_uso') { throw new Exception("Você só pode fazer check-out de uma reserva 'Em Uso'."); }
       
        $estacionamento_id = $reserva['estacionamento_id'];

        // 1. Finaliza a reserva
        $sql_update = "UPDATE reservas SET status = 'concluida', hora_fim = NOW() WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$reserva_id]);
       
        // 2. Devolve a vaga
        $sql_vaga = "UPDATE estacionamentos SET vagas_disponiveis = vagas_disponiveis + 1 WHERE id = ?";
        $stmt_vaga = $pdo->prepare($sql_vaga);
        $stmt_vaga->execute([$estacionamento_id]);

        // 3. Notifica o dono
        $sql_dono = "SELECT e.dono_id, e.endereco FROM estacionamentos e WHERE e.id = ?";
        $stmt_dono = $pdo->prepare($sql_dono);
        $stmt_dono->execute([$estacionamento_id]);
        $estacionamento = $stmt_dono->fetch(PDO::FETCH_ASSOC);
       
        if ($estacionamento) {
            $mensagem = sprintf("CHECK-OUT em '%s': Cliente %s (Placa: %s) finalizou a estadia. A vaga foi liberada.",
                $estacionamento['endereco'],
                $_SESSION['user_nome'],
                $_SESSION['user_placa']
            );
            $sql_notif = "INSERT INTO notificacoes (dono_id, mensagem) VALUES (?, ?)";
            $stmt_notif = $pdo->prepare($sql_notif);
            $stmt_notif->execute([$estacionamento['dono_id'], $mensagem]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Check-out realizado com sucesso!']);
       
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
