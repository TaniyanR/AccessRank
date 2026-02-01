<?php
declare(strict_types=1);

// AccessRank configuration

define('AR_DB_PATH', __DIR__ . '/data/accessrank.sqlite');

define('AR_RATE_LIMIT_WINDOW', 10); // seconds

define('AR_WIDGET_CACHE_TTL', 30); // seconds

define('AR_RANK_CACHE_TTL', 60); // seconds

define('AR_RANK_LIMIT', 50);

// Exclude internal referrers by host. Set AR_EXCLUDE_INTERNAL to false to disable.
define('AR_EXCLUDE_INTERNAL', true);

define('AR_INTERNAL_HOSTS', [
    'example.com',
]);

// Widget maximum bar height in px

define('AR_WIDGET_BAR_HEIGHT', 60);
