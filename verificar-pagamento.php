<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configurações
$apiUrl = 'https://api.risepay.com.br/api/External/Transactions/';
$secretToken = '79e58ef673d96fde29880ee925c743c185d6942cac5860f1a56e5cab4483b7df';

// Dados recebidos
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$transactionId = $data['transactionId'] ?? '';

if (empty($transactionId)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID da transação não informado']);
    exit;
}

// Inicializar cURL
$ch = curl_init($apiUrl . $transactionId);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $secretToken
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$respostaDecodificada = json_decode($resposta, true);

// Verificar status
$status = '';
$pago = false;
if ($respostaDecodificada && isset($respostaDecodificada['object']['status'])) {
    $status = $respostaDecodificada['object']['status'];
    // Status que indicam pagamento aprovado
    $pago = in_array($status, ['Paid', 'Approved', 'Concluded', 'Payment Received', 'Pago', 'Aprovado']);
}

echo json_encode([
    'sucesso' => true,
    'pago' => $pago,
    'status' => $status,
    'respostaOriginal' => $respostaDecodificada
]);
?>