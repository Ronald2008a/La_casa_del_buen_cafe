<?php
/**
 * Cola de Trabajos Asíncrona Ligera (Background Job Queue)
 * La Casa del Buen Café
 * Despacha tareas secundarias (recibos, logs, alertas) a una cola
 * para no demorar la respuesta de la interfaz al usuario.
 */

class JobQueue {
    private static string $queueFile = __DIR__ . '/../data/job_queue.json';

    public static function push(string $task, array $payload = []): bool {
        $job = [
            'id'         => uniqid('job_', true),
            'task'       => $task,
            'payload'    => $payload,
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        $jobs = self::getAll();
        $jobs[] = $job;
        if (!is_dir(dirname(self::$queueFile))) {
            @mkdir(dirname(self::$queueFile), 0777, true);
        }
        return file_put_contents(self::$queueFile, json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    public static function getAll(): array {
        if (!file_exists(self::$queueFile)) return [];
        $data = json_decode(file_get_contents(self::$queueFile), true);
        return is_array($data) ? $data : [];
    }

    public static function processPending(int $limit = 10): int {
        $jobs = self::getAll();
        $processed = 0;
        foreach ($jobs as &$job) {
            if ($job['status'] === 'pending') {
                $job['status'] = 'completed';
                $job['completed_at'] = date('Y-m-d H:i:s');
                $processed++;
                if ($processed >= $limit) break;
            }
        }
        file_put_contents(self::$queueFile, json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $processed;
    }
}

