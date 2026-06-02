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
$apiUrl = 'https://api.risepay.com.br/api/External/Transactions';
$secretToken = '79e58ef673d96fde29880ee925c743c185d6942cac5860f1a56e5cab4483b7df';

// Dados recebidos do frontend
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$plan = $data['plan'] ?? '';

// Mapear planos para valores (aceita todos os planos antigos também)
$planos = [
    'unico' => [
        'valor' => 9.90,
        'nome' => 'Acesso ao WhatsApp'
    ],
    'basico' => [
        'valor' => 9.90,
        'nome' => 'Acesso ao WhatsApp'
    ],
    'premium' => [
        'valor' => 23.90,
        'nome' => 'Acesso ao WhatsApp'
    ],
    'completo' => [
        'valor' => 49.90,
        'nome' => 'Acesso ao WhatsApp'
    ]
];

// Se o plano não existir, usa o padrão
if (!isset($planos[$plan])) {
    $plan = 'unico';
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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Processar resposta
$respostaDecodificada = json_decode($resposta, true);

// Extrair dados do Pix - sabemos o caminho exato agora!
$copiaECola = '';
if ($respostaDecodificada && isset($respostaDecodificada['object']['pix']['qrCode'])) {
    $copiaECola = $respostaDecodificada['object']['pix']['qrCode'];
}

// Gerar QR Code usando o código Copia e Cola
$qrcode = '';
if (!empty($copiaECola)) {
    $qrcode = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($copiaECola);
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
        'respostaBruta' => $resposta
    ]);
}
?>