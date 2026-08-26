<?php
// webhookGit.php - Para GitHub Webhook

// Configuración
$secret = "********"; // ¡Cámbiala!

// Archivo de log
$log_file = '/var/www/html/webhook.log';

function log_message($msg) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

log_message("📥 Webhook recibido");

// Verificar firma de GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');

if ($signature) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($hash, $signature)) {
        log_message("❌ Firma inválida");
        http_response_code(403);
        die('❌ Firma inválida');
    }
}

// Decodificar payload
$data = json_decode($payload, true);

// Verificar que es un push a main
if ($data['ref'] === 'refs/heads/main') {
    log_message("📦 Push detectado: " . ($data['head_commit']['message'] ?? ''));
    
    // Cambiar al directorio del proyecto
    chdir('/var/www/html');
    
    // Ejecutar comandos
    $commands = [
        'git pull origin main 2>&1',
        'docker compose down 2>&1',
        'docker compose up -d --build 2>&1'
    ];
    
    $output = [];
    foreach ($commands as $command) {
        log_message("▶️ Ejecutando: $command");
        exec($command, $output_line, $return_var);
        $output = array_merge($output, $output_line);
        if ($return_var !== 0) {
            log_message("❌ Error en: $command");
            log_message(implode("\n", $output_line));
        }
    }
    
    log_message("✅ Deploy completado");
    echo "✅ Deploy completado";
    
} else {
    log_message("ℹ️ No es push a main (ref: " . ($data['ref'] ?? 'desconocido') . ")");
    echo "ℹ️ No es push a main";
}
?>
