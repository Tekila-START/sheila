<?php
// Teste da API RisePay
echo "<h1>Teste da API RisePay</h1>";

$apiUrl = 'https://api.risepay.com.br/api/External/Transactions';
$secretToken = '79e58ef673d96fde29880ee925c743c185d6942cac5860f1a56e5cab4483b7df';

$dadosApi = [
    'amount' => 14.90,
    'payment' => [
        'method' => 'pix',
        'expiresAt' => 48
    ],
    'customer' => [
        'name' => 'Frederick Aquino',
        'email' => 'frederick.aquino@gmail.com',
        'cpf' => '08539374358',
        'phone' => '(00) 00000-0000'
    ]
];

echo "<h3>Dados enviados:</h3>";
echo "<pre>";
print_r($dadosApi);
echo "</pre>";

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
curl_setopt($ch, CURLOPT_VERBOSE, true); // Adiciona logs detalhados

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erroCurl = curl_error($ch);
curl_close($ch);

echo "<h3>HTTP Code:</h3>";
echo $httpCode;

if ($erroCurl) {
    echo "<h3>Erro do cURL:</h3>";
    echo "<pre>";
    echo $erroCurl;
    echo "</pre>";
}

echo "<h3>Resposta Bruta:</h3>";
echo "<pre>";
echo htmlspecialchars($resposta);
echo "</pre>";

echo "<h3>Resposta Decodificada:</h3>";
$respostaDecodificada = json_decode($resposta, true);
echo "<pre>";
print_r($respostaDecodificada);
echo "</pre>";
?>