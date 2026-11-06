<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function sendHealthRecordsJson(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendHealthRecordsJson(405, ['success' => false, 'error' => 'Método não permitido.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['csrf_token']) || !is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    sendHealthRecordsJson(403, ['success' => false, 'error' => 'Sua sessão expirou. Atualize a página e tente novamente.']);
}

$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if (($_SESSION['logado'] ?? false) !== true || !$userId || $userId < 1) {
    sendHealthRecordsJson(401, ['success' => false, 'error' => 'Faça login novamente para acessar seus registros.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    sendHealthRecordsJson(400, ['success' => false, 'error' => 'Não foi possível interpretar os dados enviados.']);
}

$definitions = [
    'heart-rate' => ['label' => 'Batimentos cardíacos (FC)', 'unit' => 'BPM', 'initial' => '0', 'editable' => false],
    'hrv' => ['label' => 'Variabilidade (VFC)', 'unit' => 'ms', 'initial' => '—', 'editable' => false],
    'spo2' => ['label' => 'Oxigenação (SpO₂)', 'unit' => '%', 'initial' => '—', 'editable' => false],
    'ecg' => ['label' => 'ECG', 'unit' => '', 'initial' => '—', 'editable' => false],
    'blood-pressure' => ['label' => 'Pressão arterial', 'unit' => 'mmHg', 'initial' => '120/80', 'editable' => true],
    'cholesterol' => ['label' => 'Colesterol total', 'unit' => 'mg/dL', 'initial' => '180', 'editable' => true],
    'glucose' => ['label' => 'Glicemia', 'unit' => 'mg/dL', 'initial' => '95', 'editable' => true],
];

$normalizeValue = static function (string $recordId, mixed $rawValue) use ($definitions): ?string {
    if (!isset($definitions[$recordId]) || !is_scalar($rawValue)) {
        return null;
    }

    $value = trim((string) $rawValue);
    if (!$definitions[$recordId]['editable']) {
        if ($value === '—') {
            return $value;
        }
        if ($recordId === 'ecg') {
            return $value === 'Traçado ilustrativo' ? $value : null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        $minimum = $recordId === 'spo2' ? 50 : ($recordId === 'hrv' ? 1 : 0);
        $maximum = $recordId === 'spo2' ? 100 : 300;
        return $number >= $minimum && $number <= $maximum ? $value : null;
    }

    if ($recordId === 'blood-pressure') {
        return preg_match('/^\d{2,3}\s*\/\s*\d{2,3}$/', $value) === 1
            ? preg_replace('/\s+/', '', $value)
            : null;
    }

    if (!is_numeric($value)) {
        return null;
    }
    $number = (float) $value;
    $maximum = $recordId === 'cholesterol' ? 1000 : 2000;
    return $number >= 0 && $number <= $maximum ? $value : null;
};

$normalizeNote = static function (mixed $rawNote): ?string {
    if (!is_string($rawNote) || mb_strlen($rawNote, 'UTF-8') > 120) {
        return null;
    }
    return trim($rawNote);
};

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS health_records (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            metric_id VARCHAR(40) NOT NULL,
            value VARCHAR(40) NOT NULL,
            note VARCHAR(120) NOT NULL DEFAULT \'\',
            source VARCHAR(60) NOT NULL DEFAULT \'Registro editável\',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_health_record_user_metric (user_id, metric_id),
            KEY idx_health_records_user_updated (user_id, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS health_record_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            metric_id VARCHAR(40) NOT NULL,
            old_value VARCHAR(40) NOT NULL,
            new_value VARCHAR(40) NOT NULL,
            old_note VARCHAR(120) NOT NULL DEFAULT \'\',
            new_note VARCHAR(120) NOT NULL DEFAULT \'\',
            changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_health_history_user_changed (user_id, changed_at),
            KEY idx_health_history_user_metric (user_id, metric_id, changed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $action = $input['action'] ?? '';
    if ($action === 'load') {
        $pdo->beginTransaction();
        $legacy = is_array($input['legacy'] ?? null) ? $input['legacy'] : [];
        $insert = $pdo->prepare(
            'INSERT INTO health_records (user_id, metric_id, value, note, source)
             VALUES (:user_id, :metric_id, :value, :note, :source)
             ON DUPLICATE KEY UPDATE updated_at = updated_at'
        );

        foreach ($definitions as $recordId => $definition) {
            $legacyRecord = is_array($legacy[$recordId] ?? null) ? $legacy[$recordId] : [];
            $legacyValue = $normalizeValue($recordId, $legacyRecord['value'] ?? null);
            $legacyNote = $normalizeNote($legacyRecord['note'] ?? '');
            $insert->execute([
                'user_id' => $userId,
                'metric_id' => $recordId,
                'value' => $legacyValue ?? $definition['initial'],
                'note' => $legacyNote ?? '',
                'source' => $legacyValue !== null ? 'Migrado do navegador' : 'Registro editável',
            ]);
        }
        $pdo->commit();
    } elseif ($action === 'update') {
        $recordId = is_string($input['recordId'] ?? null) ? $input['recordId'] : '';
        if (!isset($definitions[$recordId]) || !$definitions[$recordId]['editable']) {
            sendHealthRecordsJson(422, ['success' => false, 'error' => 'Este indicador não pode ser editado manualmente.']);
        }
        $value = $normalizeValue($recordId, $input['value'] ?? null);
        $note = $normalizeNote($input['note'] ?? '');
        if ($value === null || $note === null) {
            sendHealthRecordsJson(422, ['success' => false, 'error' => 'Confira o valor e a observação do registro.']);
        }

        $pdo->beginTransaction();
        $currentQuery = $pdo->prepare(
            'SELECT value, note FROM health_records WHERE user_id = :user_id AND metric_id = :metric_id FOR UPDATE'
        );
        $currentQuery->execute(['user_id' => $userId, 'metric_id' => $recordId]);
        $current = $currentQuery->fetch();
        if (!$current) {
            $initialValue = $definitions[$recordId]['initial'];
            $seed = $pdo->prepare(
                'INSERT INTO health_records (user_id, metric_id, value, note, source)
                 VALUES (:user_id, :metric_id, :value, \'\', \'Registro editável\')'
            );
            $seed->execute(['user_id' => $userId, 'metric_id' => $recordId, 'value' => $initialValue]);
            $current = ['value' => $initialValue, 'note' => ''];
        }

        if ($current['value'] !== $value || $current['note'] !== $note) {
            $history = $pdo->prepare(
                'INSERT INTO health_record_history
                    (user_id, metric_id, old_value, new_value, old_note, new_note)
                 VALUES (:user_id, :metric_id, :old_value, :new_value, :old_note, :new_note)'
            );
            $history->execute([
                'user_id' => $userId,
                'metric_id' => $recordId,
                'old_value' => $current['value'],
                'new_value' => $value,
                'old_note' => $current['note'],
                'new_note' => $note,
            ]);
        }

        $update = $pdo->prepare(
            'UPDATE health_records SET value = :value, note = :note, source = \'Editado manualmente\'
             WHERE user_id = :user_id AND metric_id = :metric_id'
        );
        $update->execute([
            'value' => $value,
            'note' => $note,
            'user_id' => $userId,
            'metric_id' => $recordId,
        ]);
        $pdo->commit();
    } elseif ($action === 'sync_watch') {
        $watchValues = is_array($input['values'] ?? null) ? $input['values'] : [];
        $normalizedWatchValues = [];
        foreach (array_keys($definitions) as $recordId) {
            if ($definitions[$recordId]['editable'] || !array_key_exists($recordId, $watchValues)) {
                continue;
            }
            $value = $normalizeValue($recordId, $watchValues[$recordId]);
            if ($value === null) {
                sendHealthRecordsJson(422, ['success' => false, 'error' => 'Não foi possível validar uma leitura do relógio.']);
            }
            $normalizedWatchValues[$recordId] = $value;
        }
        $pdo->beginTransaction();
        $sync = $pdo->prepare(
            'INSERT INTO health_records (user_id, metric_id, value, note, source)
             VALUES (:user_id, :metric_id, :value, :note, \'Relógio · demonstração\')
             ON DUPLICATE KEY UPDATE
                value = VALUES(value), note = VALUES(note), source = VALUES(source), updated_at = CURRENT_TIMESTAMP'
        );
        foreach ($normalizedWatchValues as $recordId => $value) {
            $sync->execute([
                'user_id' => $userId,
                'metric_id' => $recordId,
                'value' => $value,
                'note' => $value === '—' ? '' : 'Leitura demonstrativa; não é uma medição clínica.',
            ]);
        }
        $pdo->commit();
    } elseif ($action !== 'history') {
        sendHealthRecordsJson(400, ['success' => false, 'error' => 'Ação de registro inválida.']);
    }

    $currentQuery = $pdo->prepare(
        'SELECT metric_id, value, note, source, updated_at
         FROM health_records WHERE user_id = :user_id'
    );
    $currentQuery->execute(['user_id' => $userId]);
    $currentRecords = [];
    foreach ($currentQuery->fetchAll() as $record) {
        $currentRecords[$record['metric_id']] = [
            'value' => $record['value'],
            'note' => $record['note'],
            'source' => $record['source'],
            'updatedAt' => date(DATE_ATOM, strtotime($record['updated_at'])),
        ];
    }

    $historyQuery = $pdo->prepare(
        'SELECT metric_id, old_value, new_value, old_note, new_note, changed_at
         FROM health_record_history WHERE user_id = :user_id
         ORDER BY changed_at DESC, id DESC'
    );
    $historyQuery->execute(['user_id' => $userId]);
    $history = array_values(array_filter(array_map(static function (array $record) use ($definitions): ?array {
        if (!isset($definitions[$record['metric_id']])) {
            return null;
        }
        return [
            'metricId' => $record['metric_id'],
            'label' => $definitions[$record['metric_id']]['label'],
            'unit' => $definitions[$record['metric_id']]['unit'],
            'oldValue' => $record['old_value'],
            'newValue' => $record['new_value'],
            'oldNote' => $record['old_note'],
            'newNote' => $record['new_note'],
            'changedAt' => date(DATE_ATOM, strtotime($record['changed_at'])),
        ];
    }, $historyQuery->fetchAll())));

    sendHealthRecordsJson(200, [
        'success' => true,
        'records' => $currentRecords,
        'history' => $history,
    ]);
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('CardioWeb health records database error: ' . $exception->getMessage());
    sendHealthRecordsJson(500, ['success' => false, 'error' => 'Não foi possível acessar o histórico no banco de dados. Tente novamente.']);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('CardioWeb health records error: ' . $exception->getMessage());
    sendHealthRecordsJson(500, ['success' => false, 'error' => 'Ocorreu um erro ao processar os registros de saúde.']);
}
