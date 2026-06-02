<?php
header('Content-Type: application/json');

// Configurações
$apiUrl = 'https://api.risepay.com.br/api/External/Transactions';
$secretToken = '79e58ef673d96fde29880ee925c743c185d6942cac5860f1a56e5cab4483b7df';

// Dados recebidos do frontend
$data = json_decode(file_get_contents('php://input'), true);
$plan = $data['plan'] ?? '';

// Mapear planos para valores
$planos = [
    'basico' => [
        'valor' => 14.90,
        'nome' => 'Plano Básico'
    ],
    'premium' => [
        'valor' => 23.90,
        'nome' => 'Plano Premium'
    ],
    'completo' => [
        'valor' => 49.90,
        'nome' => 'Plano Completo'
    ]
];

if (!isset($planos[$plan])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Plano inválido']);
    exit;
}

$planoSelecionado = $planos[$plan];

// Dados para a API RisePay
$dadosApi = [
    'amount' => $planoSelecionado['valor'],
    'payment' => [
        'method' => 'pix',
        'expiresAt' => 48
    ],
    'customer' => [
        'name' => 'Cliente',
        'email' => 'cliente@exemplo.com',
        'cpf' => '08539374358',
        'phone' => '(00) 00000-0000'
    ]
];

// Inicializar cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $secretToken
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosApi));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desenvolvimento (remover em produção se possível)

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erroCurl = curl_error($ch);
curl_close($ch);

// Salvar log da resposta para debug
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . "\n" . "HTTP Code: " . $httpCode . "\n" . "Resposta: " . $resposta . "\n\n", FILE_APPEND);

// Processar resposta
$respostaDecodificada = json_decode($resposta, true);

// Extrair dados do Pix de forma flexível
$qrcode = '';
$copiaECola = '';

if ($respostaDecodificada) {
    // Percorrer todo o array para encontrar campos relevantes
    array_walk_recursive($respostaDecodificada, function($value, $key) use (&$qrcode, &$copiaECola) {
        $keyLower = strtolower($key);
        if (empty($qrcode) && (strpos($keyLower, 'qrcode') !== false || strpos($keyLower, 'qr_code') !== false || strpos($keyLower, 'qrimage') !== false)) {
            if (filter_var($value, FILTER_VALIDATE_URL) || strpos($value, 'data:image') === 0) {
                $qrcode = $value;
            }
        }
        if (empty($copiaECola) && (strpos($keyLower, 'copia') !== false || strpos($keyLower, 'pix') !== false || strpos($keyLower, 'code') !== false)) {
            if (strlen($value) > 20) { // Código Pix é longo
                $copiaECola = $value;
            }
        }
    });
}

// Se temos o código copia e cola mas não o QR Code, gerar o QR Code usando uma API gratuita
if (empty($qrcode) && !empty($copiaECola)) {
    $qrcode = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' . urlencode($copiaECola);
}

if ($httpCode === 200 || $httpCode === 201) {
    echo json_encode([
        'sucesso' => true,
        'plano' => $planoSelecionado['nome'],
        'valor' => $planoSelecionado['valor'],
        'qrcode' => $qrcode,
        'copiaECola' => $copiaECola,
        'respostaOriginal' => $respostaDecodificada
    ]);
} else {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao gerar pagamento',
        'detalhes' => $respostaDecodificada,
        'httpCode' => $httpCode,
        'erroCurl' => $erroCurl
    ]);
}
?>