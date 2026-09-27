<?php

return [
    'approval_threshold' => (float) env('APPROVAL_THRESHOLD', 1000000),

    /* Number of backup archives kept on disk; older ones are pruned after each backup. 0 disables pruning. */
    'backup_retention' => (int) env('BACKUP_RETENTION', 14),

    /* Daily automatic backup time (HH:MM), used by the scheduler. Empty disables it. */
    'backup_schedule' => env('BACKUP_SCHEDULE', '02:00'),
];
