<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function getGroqApiKey(): string
{
    $apiKey = getenv('GROQ_API_KEY');
    if (is_string($apiKey) && trim($apiKey) !== '') {
        return trim($apiKey);
    }

    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($envPath)) {
        return '';
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return '';
    }

    foreach ($lines as $line) {
        if (preg_match('/^\s*GROQ_API_KEY\s*=\s*(.*?)\s*$/', $line, $matches) === 1) {
            return trim($matches[1], " \t\n\r\0\x0B\"'");
        }
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['error' => 'Método não permitido.']);
}

if (($_SESSION['logado'] ?? false) !== true) {
    respond(401, ['error' => 'Sua sessão expirou. Entre novamente para continuar.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    respond(403, ['error' => 'Não foi possível validar esta solicitação. Atualize a página e tente novamente.']);
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 8 * 1024 * 1024) {
    respond(413, ['error' => 'A solicitação excede o limite permitido. Escolha uma imagem menor.']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 8 * 1024 * 1024) {
    respond(413, ['error' => 'A solicitação excede o limite permitido. Escolha uma imagem menor.']);
}

$input = json_decode($rawBody, true);
if (!is_array($input)) {
    respond(400, ['error' => 'A solicitação está inválida. Tente novamente.']);
}

$message = trim((string) ($input['message'] ?? ''));
if (mb_strlen($message, 'UTF-8') > 3000) {
    respond(400, ['error' => 'A mensagem deve ter no máximo 3.000 caracteres.']);
}

$history = [];
if (isset($input['history']) && is_array($input['history'])) {
    foreach (array_slice($input['history'], -10) as $entry) {
        if (!is_array($entry) || !isset($entry['sender'], $entry['text'])) {
            continue;
        }
        $role = $entry['sender'] === 'user' ? 'user' : ($entry['sender'] === 'bot' ? 'assistant' : '');
        $text = trim((string) $entry['text']);
        if ($role !== '' && $text !== '' && mb_strlen($text, 'UTF-8') <= 2000) {
            $history[] = ['role' => $role, 'content' => $text];
        }
    }
}

$imageData = null;
$image = $input['image'] ?? null;
if ($image !== null) {
    if (!is_array($image) || !isset($image['base64']) || !is_string($image['base64'])) {
        respond(400, ['error' => 'A imagem enviada está inválida. Selecione um arquivo de imagem e tente novamente.']);
    }

    $imageBytes = base64_decode($image['base64'], true);
    if ($imageBytes === false || $imageBytes === '' || strlen($imageBytes) > 5 * 1024 * 1024) {
        respond(400, ['error' => 'A imagem deve ter no máximo 5 MB.']);
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->buffer($imageBytes);
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $dimensions = @getimagesizefromstring($imageBytes);
    if (!in_array($mimeType, $allowedMimeTypes, true) || $dimensions === false) {
        respond(400, ['error' => 'Formato não aceito. Envie uma imagem JPG, PNG ou WebP.']);
    }
    if ($dimensions[0] * $dimensions[1] > 25000000) {
        respond(400, ['error' => 'A resolução da imagem é muito grande. Reduza a imagem e tente novamente.']);
    }

    $imageData = [
        'mime' => $mimeType,
        'base64' => base64_encode($imageBytes)
    ];
}

if ($message === '' && $imageData === null) {
    respond(400, ['error' => 'Escreva uma mensagem ou anexe uma imagem para conversar.']);
}

$emergencyPatterns = [
    '/\b(?:dor|press[aã]o|aperto)\b[^.!?]{0,45}\b(?:muito\s+)?(?:forte|intens[oa]|sever[oa])\b[^.!?]{0,45}\b(?:peito|t[oó]rax)\b/iu',
    '/\b(?:falta de ar|dificuldade para respirar)\b[^.!?]{0,35}\b(?:muito\s+forte|intensa|grave|importante|severa)\b/iu',
    '/\b(?:desmaio|perda de consci[eê]ncia|fraqueza s[uú]bita|fraqueza de um lado|rosto ca[ií]do|fala enrolada)\b/iu'
];
$explicitDenial = preg_match('/\b(?:n[aã]o|sem|nunca)\b[^.!?]{0,35}\b(?:dor|press[aã]o|aperto|falta de ar|desmaio|fraqueza)\b/iu', $message) === 1;
foreach ($emergencyPatterns as $pattern) {
    if (!$explicitDenial && preg_match($pattern, $message) === 1) {
        respond(200, ['reply' => 'Se esses sintomas graves estão acontecendo agora, procure atendimento de emergência imediatamente e ligue para o SAMU (192) no Brasil. Esta resposta não é 100% certa; procure um médico para avaliação.']);
    }
}

$apiKey = getGroqApiKey();
if ($apiKey === '') {
    error_log('CardioWeb AI chat is unavailable: GROQ_API_KEY is not configured.');
    respond(503, ['error' => 'O serviço de IA ainda não está configurado. Verifique GROQ_API_KEY no arquivo .env.']);
}
if (!function_exists('curl_init')) {
    error_log('CardioWeb AI chat is unavailable: PHP cURL extension is not enabled.');
    respond(503, ['error' => 'O serviço de IA está indisponível neste servidor no momento.']);
}

$systemPrompt = 'Você é o assistente educativo de saúde do CardioWeb. Responda em português do Brasil, com empatia, clareza e objetividade. Converse naturalmente sobre dúvidas gerais, sintomas e imagens; explique possibilidades comuns e faça perguntas breves sobre início, duração, intensidade e evolução quando isso ajudar. Não afirme diagnóstico, não prescreva medicamentos nem recomende alterar tratamentos. Descreva imagens com cautela, sem tratá-las como confirmação de doença. Não peça dados identificáveis. Evite preâmbulos genéricos, longas ressalvas ou repetir limitações: responda diretamente; a plataforma acrescenta uma breve nota de cautela ao final. Se houver dor ou pressão intensa no peito, falta de ar importante, desmaio, confusão súbita, fraqueza de um lado do corpo ou piora rápida, oriente atendimento de emergência imediato e ligação para o SAMU 192 no Brasil.';
$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $entry) {
    $messages[] = $entry;
}

$currentMessage = $message !== ''
    ? $message
    : 'Descreva brevemente o que é visível nesta imagem e pergunte qual dúvida a pessoa gostaria de esclarecer.';
if ($imageData !== null) {
    $messages[] = [
        'role' => 'user',
        'content' => [
            ['type' => 'text', 'text' => $currentMessage],
            ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $imageData['mime'] . ';base64,' . $imageData['base64']]]
        ]
    ];
} else {
    $messages[] = ['role' => 'user', 'content' => $currentMessage];
}

session_write_close();

$requestBody = json_encode([
    'model' => 'qwen/qwen3.8-27b',
    'messages' => $messages,
    'temperature' => 0.4,
    'max_completion_tokens' => 700
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($requestBody === false) {
    respond(500, ['error' => 'Não foi possível preparar a mensagem para o assistente.']);
}

$curl = curl_init('https://api.groq.com/openai/v1/chat/completions');
if ($curl === false) {
    error_log('CardioWeb AI chat could not initialize a cURL request.');
    respond(503, ['error' => 'O serviço de IA está indisponível neste servidor no momento.']);
}

curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $requestBody,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 45
]);

$responseBody = curl_exec($curl);
$curlError = curl_error($curl);
$httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($responseBody === false) {
    error_log('CardioWeb AI provider request failed: ' . $curlError);
    respond(502, ['error' => 'Não foi possível conectar ao serviço de IA. Tente novamente em instantes.']);
}

if ($httpStatus < 200 || $httpStatus >= 300) {
    $providerResult = json_decode($responseBody, true);
    $providerMessage = $providerResult['error']['message'] ?? '';
    $providerMessage = is_string($providerMessage)
        ? mb_substr(preg_replace('/[\r\n\t]+/', ' ', $providerMessage) ?? '', 0, 240)
        : '';
    error_log('CardioWeb AI provider returned HTTP ' . $httpStatus . ($providerMessage !== '' ? ': ' . $providerMessage : '.'));
    if ($httpStatus === 429) {
        respond(502, ['error' => 'O assistente está recebendo muitas solicitações. Aguarde um pouco e tente novamente.']);
    }
    if ($httpStatus === 400 && $imageData !== null) {
        respond(502, ['error' => 'O serviço de IA não conseguiu processar essa imagem. Tente outro arquivo JPG, PNG ou WebP.']);
    }
    respond(502, ['error' => 'O serviço de IA não conseguiu responder agora. Tente novamente em instantes.']);
}

$providerResult = json_decode($responseBody, true);
$reply = $providerResult['choices'][0]['message']['content'] ?? null;
if (!is_string($reply) || trim($reply) === '') {
    error_log('CardioWeb AI provider returned an empty or invalid response.');
    respond(502, ['error' => 'O assistente não retornou uma resposta válida. Tente novamente.']);
}

$reply = trim($reply);
$reply .= "\n\nEsta resposta não é 100% certa; procure um médico para avaliação.";
respond(200, ['reply' => $reply]);