<?php

namespace HXM\DatabaseSystemConfig\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfigValue extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'parent_id';
    public $incrementing = false;
    protected $fillable = ['parent_id', 'value'];
}
