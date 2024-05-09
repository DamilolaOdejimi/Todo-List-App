<?php

use App\Models\AuditLog;

/**
 * Reurn an object response
 *
 * @param array $actionArray
 * @return bool
 */
function logAction(array $actionArray): bool
{
    $logArray = [
        'log_name' => $actionArray['log_name'],
        'description' => $actionArray['description'],
        'resource_id' => $actionArray['resource_id'],
        'resource_model' => $actionArray['resource_model'],
        'user_id' => $actionArray['user_id']
    ];
    if (!is_null(AuditLog::create($logArray))) {
        return true;
    }

    return false;
}
