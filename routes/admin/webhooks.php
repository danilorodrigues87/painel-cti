<?php

use \App\Services\ReceiveMessage;

// Lista de webhooks disponíveis
$webhooks = [
    'tU34Q9d4BIVIBsusPOad',
    'MWxmaEkmc29bO15tQZ4k',
    'WdVzUw33ljjOwGEimjU3',
    '4DrtroMC5MJBih2DP9LQ',
    'ub7JWvpkCQhpu7WakQIL'
];

// Cria rotas dinamicamente
foreach ($webhooks as $webhook) {
    // Rota GET para verificação
    $obRouter->get('/webhook/' . $webhook, [
        function($request) {
            return ReceiveMessage::verifyWebhook($request);
        }
    ]);

    // Rota POST para mensagens
    $obRouter->post('/webhook/' . $webhook, [
        function($request) {
            return ReceiveMessage::handleMessage($request);
        }
    ]);
} 