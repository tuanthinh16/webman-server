<?php

namespace app\validation;

use Illuminate\Contracts\Validation\Rule;
use support\Db;

class CustomRulesNotExists implements Rule
{
    protected $table;
    protected $column;

    public function __construct($table, $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function passes($attribute, $value)
    {   
        return Db::table($this->table)->where($this->column, $value)->exists();
    }

    public function message()
    {
        return 'Giá trị :attribute không đã tồn tại.';
    }
}
