<?php
function sendJsonResponse($success, $data = null, $message = '', $httpCode = 200)
{
    http_response_code($httpCode);
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'meta' => [
            'timestamp' => date('c'),
            'version' => '1.0'
        ]
    ];
    echo json_encode($response);
    exit;
}

function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
?>
