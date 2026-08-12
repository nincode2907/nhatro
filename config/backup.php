<?php

return [
    'path' => env('DB_BACKUP_PATH', storage_path('app/backups')),
    'retention' => (int) env('DB_BACKUP_RETENTION', 30),
];
