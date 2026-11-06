<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$medico = $_GET['medico'] ?? '';
$data = $_GET['data'] ?? '';

if (empty($medico) || empty($data)) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT hora_consulta FROM consultas 
        WHERE nome_medico = ? AND data_consulta = ? AND status != 'Recusada'
    ");
    $stmt->execute([$medico, $data]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ocupados = array_map(function($row) {
        return substr($row['hora_consulta'], 0, 5);
    }, $resultados);
    
    echo json_encode($ocupados);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>