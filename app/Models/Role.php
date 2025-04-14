<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    const ROLE_ADMIN = 'admin';

    const ROLE_MANAGER = 'manager';

    const ROLE_USER = 'user';
}
