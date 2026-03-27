<?php

return [
    'allowed_admin_emails' => array_values(array_filter(array_map(
        static fn (string $email) => strtolower(trim($email)),
        explode(',', env('ALLOWED_ADMIN_EMAILS', 'admin@admin.com,sadmin@sadmin.com'))
    ))),
];
