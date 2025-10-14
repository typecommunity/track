<?php
/**
 * Cron Job - Processa eventos CAPI pendentes
 * Arquivo: cron/process-capi-events.php
 * 
 * Configure para rodar a cada 5 minutos:
 * */5 * * * * php /caminho/para/cron/process-capi-events.php
 */

// Evita execução via browser
if (php_sapi_name() !== 'cli') {
    die('Este script deve ser executado via CLI');
}

require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/FacebookCapi.php';

class CapiEventProcessor {
    
    private $db;
    private $batchSize = 50;
    private $maxRetries = 3;
    private $retryDelay = 300; // 5 minutos
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Processa eventos pendentes
     */
    public function processPendingEvents() {
        $this->log("Iniciando processamento de eventos pendentes...");
        
        // Busca eventos pendentes agrupados por pixel
        $pixels = $this->getPendingEventsByPixel();
        
        if (empty($pixels)) {
            $this->log("Nenhum evento pendente encontrado.");
            return;
        }
        
        $totalProcessed = 0;
        $totalSent = 0;
        $totalFailed = 0;
        
        foreach ($pixels as $pixelData) {
            $result = $this->processPixelEvents($pixelData);
            
            $totalProcessed += $result['processed'];
            $totalSent += $result['sent'];
            $totalFailed += $result['failed'];
        }
        
        $this->log("Processamento concluído:");
        $this->log("- Total processado: {$totalProcessed}");
        $this->log("- Enviados com sucesso: {$totalSent}");
        $this->log("- Falhas: {$totalFailed}");
    }
    
    /**
     * Busca eventos pendentes agrupados por pixel
     */
    private function getPendingEventsByPixel() {
        return $this->db->fetchAll("
            SELECT 
                p.pixel_id,
                p.access_token,
                p.test_event_code,
                p.user_id,
                COUNT(ce.id) as pending_count
            FROM pixels p
            INNER JOIN capi_events ce ON p.id = ce.pixel_id
            WHERE ce.status = 'pending'
            AND p.capi_enabled = 1
            AND p.status = 'active'
            GROUP BY p.id
            ORDER BY pending_count DESC
        ");
    }
    
    /**
     * Processa eventos de um pixel específico
     */
    private function processPixelEvents($pixelData) {
        $this->log("\nProcessando pixel: {$pixelData['pixel_id']} ({$pixelData['pending_count']} eventos)");
        
        $processed = 0;
        $sent = 0;
        $failed = 0;
        
        // Busca eventos pendentes deste pixel
        $events = $this->db->fetchAll("
            SELECT *
            FROM capi_events
            WHERE pixel_id = (
                SELECT id FROM pixels WHERE pixel_id = :pixel_id LIMIT 1
            )
            AND status = 'pending'
            ORDER BY created_at ASC
            LIMIT {$this->batchSize}
        ", ['pixel_id' => $pixelData['pixel_id']]);
        
        if (empty($events)) {
            return ['processed' => 0, 'sent' => 0, 'failed' => 0];
        }
        
        // Inicializa CAPI
        $capi = new FacebookCapi(
            $pixelData['pixel_id'],
            $pixelData['access_token'],
            $pixelData['test_event_code']
        );
        
        // Processa em lotes de até 10 eventos
        $batches = array_chunk($events, 10);
        
        foreach ($batches as $batch) {
            $eventsToSend = [];
            $eventIds = [];
            
            foreach ($batch as $event) {
                $eventIds[] = $event['id'];
                
                $eventsToSend[] = [
                    'event_name' => $event['event_name'],
                    'event_time' => $event['event_time'],
                    'event_id' => $event['event_id'],
                    'event_source_url' => $event['event_source_url'],
                    'action_source' => $event['action_source'],
                    'user_data' => json_decode($event['user_data'], true),
                    'custom_data' => json_decode($event['custom_data'], true)
                ];
            }
            
            // Envia lote
            $result = $capi->sendBatchEvents($eventsToSend);
            
            if ($result['success']) {
                // Marca eventos como enviados
                foreach ($eventIds as $eventId) {
                    $this->markEventAsSent($eventId, $result);
                }
                
                $sent += count($eventIds);
                $this->log("✓ Lote de " . count($eventIds) . " eventos enviado com sucesso");
            } else {
                // Marca eventos como falhos
                foreach ($eventIds as $eventId) {
                    $this->markEventAsFailed($eventId, $result);
                }
                
                $failed += count($eventIds);
                $this->log("✗ Falha ao enviar lote: " . ($result['error'] ?? 'Erro desconhecido'));
            }
            
            $processed += count($eventIds);
            
            // Aguarda 1 segundo entre lotes para evitar rate limit
            sleep(1);
        }
        
        return [
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed
        ];
    }
    
    /**
     * Marca evento como enviado
     */
    private function markEventAsSent($eventId, $result) {
        $this->db->update('capi_events', [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'response_data' => json_encode($result),
            'error_message' => null
        ], 'id = :id', ['id' => $eventId]);
    }
    
    /**
     * Marca evento como falho
     */
    private function markEventAsFailed($eventId, $result) {
        $this->db->update('capi_events', [
            'status' => 'failed',
            'error_message' => $result['error'] ?? 'Unknown error',
            'response_data' => json_encode($result)
        ], 'id = :id', ['id' => $eventId]);
    }
    
    /**
     * Reprocessa eventos com falha
     */
    public function retryFailedEvents() {
        $this->log("\nVerificando eventos com falha para reprocessamento...");
        
        // Busca eventos que falharam há mais de 5 minutos
        $failedEvents = $this->db->fetchAll("
            SELECT ce.*, p.pixel_id, p.access_token, p.test_event_code
            FROM capi_events ce
            INNER JOIN pixels p ON ce.pixel_id = p.id
            WHERE ce.status = 'failed'
            AND ce.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND ce.sent_at <= DATE_SUB(NOW(), INTERVAL {$this->retryDelay} SECOND)
            LIMIT 100
        ");
        
        if (empty($failedEvents)) {
            $this->log("Nenhum evento para reprocessar.");
            return;
        }
        
        $this->log("Encontrados " . count($failedEvents) . " eventos para reprocessar");
        
        $retried = 0;
        $success = 0;
        
        foreach ($failedEvents as $event) {
            // Volta status para pending
            $this->db->update('capi_events', [
                'status' => 'pending',
                'error_message' => null
            ], 'id = :id', ['id' => $event['id']]);
            
            $retried++;
        }
        
        $this->log("✓ {$retried} eventos marcados para reprocessamento");
    }
    
    /**
     * Remove eventos antigos
     */
    public function cleanOldEvents() {
        $this->log("\nLimpando eventos antigos...");
        
        $daysToKeep = 90;
        
        $deleted = $this->db->query("
            DELETE FROM capi_events
            WHERE created_at < DATE_SUB(NOW(), INTERVAL {$daysToKeep} DAY)
        ");
        
        $count = $deleted ? $deleted->rowCount() : 0;
        $this->log("✓ {$count} eventos antigos removidos (>{$daysToKeep} dias)");
    }
    
    /**
     * Limpa logs antigos
     */
    public function cleanOldLogs() {
        $this->log("\nLimpando logs antigos...");
        
        $daysToKeep = 30;
        
        $deleted = $this->db->query("
            DELETE FROM capi_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL {$daysToKeep} DAY)
        ");
        
        $count = $deleted ? $deleted->rowCount() : 0;
        $this->log("✓ {$count} logs antigos removidos (>{$daysToKeep} dias)");
    }
    
    /**
     * Gera relatório de saúde do sistema
     */
    public function healthCheck() {
        $this->log("\n=== HEALTH CHECK ===");
        
        // Total de eventos
        $stats = $this->db->fetch("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM capi_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        
        $this->log("Últimas 24 horas:");
        $this->log("- Total de eventos: {$stats['total']}");
        $this->log("- Pendentes: {$stats['pending']}");
        $this->log("- Enviados: {$stats['sent']}");
        $this->log("- Falhas: {$stats['failed']}");
        
        if ($stats['total'] > 0) {
            $successRate = round(($stats['sent'] / $stats['total']) * 100, 2);
            $this->log("- Taxa de sucesso: {$successRate}%");
            
            if ($successRate < 80) {
                $this->log("⚠️  ALERTA: Taxa de sucesso abaixo de 80%!");
            }
        }
        
        // Pixels ativos
        $activePixels = $this->db->fetch("
            SELECT COUNT(*) as total
            FROM pixels
            WHERE capi_enabled = 1 AND status = 'active'
        ");
        
        $this->log("\nPixels ativos: {$activePixels['total']}");
        
        // Eventos pendentes há mais de 1 hora
        $stuckEvents = $this->db->fetch("
            SELECT COUNT(*) as total
            FROM capi_events
            WHERE status = 'pending'
            AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        
        if ($stuckEvents['total'] > 0) {
            $this->log("\n⚠️  {$stuckEvents['total']} eventos pendentes há mais de 1 hora");
        }
    }
    
    /**
     * Log com timestamp
     */
    private function log($message) {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }
}

// ===================================
// EXECUÇÃO
// ===================================

try {
    $processor = new CapiEventProcessor();
    
    // Verifica argumentos da linha de comando
    $command = $argv[1] ?? 'process';
    
    switch ($command) {
        case 'process':
            $processor->processPendingEvents();
            break;
            
        case 'retry':
            $processor->retryFailedEvents();
            break;
            
        case 'clean':
            $processor->cleanOldEvents();
            $processor->cleanOldLogs();
            break;
            
        case 'health':
            $processor->healthCheck();
            break;
            
        case 'all':
            $processor->processPendingEvents();
            $processor->retryFailedEvents();
            $processor->healthCheck();
            break;
            
        default:
            echo "Uso: php process-capi-events.php [command]\n";
            echo "Comandos disponíveis:\n";
            echo "  process  - Processa eventos pendentes (padrão)\n";
            echo "  retry    - Reprocessa eventos com falha\n";
            echo "  clean    - Remove eventos e logs antigos\n";
            echo "  health   - Relatório de saúde do sistema\n";
            echo "  all      - Executa todos os comandos\n";
            exit(1);
    }
    
    echo "\n✓ Processamento concluído com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}