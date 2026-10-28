<?php

// Compatibility dictionary for shared frontend labels and dynamic section titles.
return array_merge(
    require __DIR__.'/backend/auth.php',
    require __DIR__.'/backend/admin.php',
    require __DIR__.'/backend/department.php',
    require __DIR__.'/backend/role.php',
    require __DIR__.'/backend/menu.php',
    require __DIR__.'/backend/log.php',
    require __DIR__.'/backend/layout.php',
    require __DIR__.'/backend/editor.php',
    require __DIR__.'/backend/common.php',
);
