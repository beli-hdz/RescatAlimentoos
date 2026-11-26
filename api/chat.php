<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Método no permitido.']); exit; }
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($origin && parse_url($origin, PHP_URL_HOST) !== preg_replace('/:\d+$/', '', $host)) {
    http_response_code(403); echo json_encode(['error'=>'Origen no autorizado.']); exit;
}
// Límite básico por sesión para evitar abuso accidental del endpoint.
$now = time();
$_SESSION['chat_requests'] = array_values(array_filter($_SESSION['chat_requests'] ?? [], fn($t) => $t > $now - 60));
if (count($_SESSION['chat_requests']) >= 12) { http_response_code(429); echo json_encode(['error'=>'Has enviado varias preguntas. Espera un minuto e inténtalo de nuevo.']); exit; }
$_SESSION['chat_requests'][] = $now;
$payload = json_decode(file_get_contents('php://input'), true);
$message = trim((string)($payload['message'] ?? ''));
if ($message === '' || mb_strlen($message) > 1200) { http_response_code(422); echo json_encode(['error'=>'Escribe una pregunta de máximo 1200 caracteres.']); exit; }
$apiKey = getenv('OPENAI_API_KEY') ?: '';
if (!$apiKey) { http_response_code(503); echo json_encode(['error'=>'El asistente aún no está configurado. Contacta al administrador.']); exit; }
if (!function_exists('curl_init')) { http_response_code(500); echo json_encode(['error'=>'El servidor necesita habilitar cURL.']); exit; }
$body = [
    'model' => getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini',
    'instructions' => 'Eres el asistente de RescatAlimentos. Responde en español, con claridad y brevedad. Ayuda únicamente con dudas generales sobre registro, publicación de donaciones, entregas, recolecciones y centros de apoyo. No inventes direcciones, horarios, teléfonos, disponibilidad ni políticas específicas; indica que deben confirmarse en la sección Centros de apoyo o con el administrador. Nunca solicites contraseñas, códigos ni datos bancarios. No afirmes que realizaste registros o entregas. Ante una emergencia real, recomienda llamar al 911.',
    'input' => $message,
    'max_output_tokens' => 350
];
$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
    CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE)
]);
$response = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($response === false || $status < 200 || $status >= 300) {
    http_response_code(502); echo json_encode(['error'=>'No fue posible conectar con el asistente. Inténtalo más tarde.']); exit;
}
$data = json_decode($response, true);
$answer = '';
foreach (($data['output'] ?? []) as $item) {
    foreach (($item['content'] ?? []) as $part) if (($part['type'] ?? '') === 'output_text') $answer .= ($part['text'] ?? '');
}
if ($answer === '') { http_response_code(502); echo json_encode(['error'=>'El asistente no pudo generar una respuesta.']); exit; }
echo json_encode(['answer'=>$answer], JSON_UNESCAPED_UNICODE);
