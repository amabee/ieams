<?php

return [
    /*
     * How often (in milliseconds) to poll for new notifications.
     * Set NOTIFICATION_POLL_INTERVAL in .env to override.
     */
    'poll_interval' => (int) env('NOTIFICATION_POLL_INTERVAL', 5000),
];
