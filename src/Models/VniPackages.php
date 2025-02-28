<?php

namespace Vnideas\Initial\Models;

use Illuminate\Database\Eloquent\Model;

class VniPackages extends Model
{
    protected $table = 'vni_packages';
    protected $fillable = [
        'name',
        'description',
        'install_name',
        'version',
        'activated',
        'installed',
    ];
}