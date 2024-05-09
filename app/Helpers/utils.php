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
        'action_type' => $actionArray['actionType'],
        'action_id' => $actionArray['actionId'],
        'action' => $actionArray['action'],
        'company_id' => $actionArray['companyId'],
        'causer_id' => $actionArray['causerId'],
        'description' => $actionArray['description'] ?? ""
    ];
    if (!is_null(AuditLog::create($logArray))) {
        return true;
    }

    return false;
}
