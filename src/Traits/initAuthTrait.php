<?php

namespace Vnideas\Initial\Traits;

trait initAuthTrait
{
    public static function checkCreate($model): bool
    {
        return isSuperAdmin() || auth()->user()->can('create_'.strtolower(class_basename($model)));
    }

    public static function checkDelete($model): bool
    {
        return isSuperAdmin() || auth()->user()->can('delete_'.strtolower(class_basename($model)));
    }

    public static function checkEdit($model): bool
    {
        return isSuperAdmin() || auth()->user()->can('edit_'.strtolower(class_basename($model)));
    }

    public static function checkView($model): bool
    {
        return isSuperAdmin() || auth()->user()->can('view_'.strtolower(class_basename($model)));
    }

    public static function checkViewAny($model): bool
    {
        return isSuperAdmin() || auth()->user()->can('view_any_'.strtolower(class_basename($model)));
    }
}