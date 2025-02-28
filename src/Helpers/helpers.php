<?php

if (!function_exists('isSpatiePermissionInstalled')) {
    function isSpatiePermissionInstalled(): bool
    {
        return class_exists('Spatie\Permission\PermissionServiceProvider::class');
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if (!isSpatiePermissionInstalled()) {
            return $user->id === 1;
        }
        return $user->hasRole('super_admin');
    }
}