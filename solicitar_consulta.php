a<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$id_paciente = $data['id_paciente'] ?? '';
$nome_paciente = $data['nome_paciente'] ?? '';
$medico = $data['medico'] ?? '';
$especialidade = $data['especialidade'] ?? '';
$data_consulta = $data['data'] ?? '';
$hora_consulta = $data['hora'] ?? '';
$tipo = $data['tipo'] ?? '';
$observacoes = $data['observacoes'] ?? '';

if (empty($id_paciente) || empty($medico) || empty($data_consulta) || empty($hora_consulta)) {
    echo json_encode(['error' => 'Todos os campos são obrigatórios']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar se já existe consulta nesse horário
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM consultas 
        WHERE nome_medico = ? AND data_consulta = ? AND hora_consulta = ? AND status != 'Recusada'
    ");
    $stmt->execute([$medico, $data_consulta, $hora_consulta]);
    $existe = $stmt->fetchColumn();
    
    if ($existe > 0) {
        echo json_encode(['error' => 'Horário já ocupado. Escolha outro horário.']);
        exit;
    }
    
    // Inserir consulta
    $stmt = $pdo->prepare("
        INSERT INTO consultas (id_paciente, nome_medico, especialidade, data_consulta, hora_consulta, status, observacoes)
        VALUES (?, ?, ?, ?, ?, 'Pendente', ?)
    ");
    $stmt->execute([$id_paciente, $medico, $especialidade, $data_consulta, $hora_consulta, $observacoes]);
    
    echo json_encode(['success' => true, 'message' => 'Consulta solicitada com sucesso!']);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao salvar: ' . $e->getMessage()]);
}
?>