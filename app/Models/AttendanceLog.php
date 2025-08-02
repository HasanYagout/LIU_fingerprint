<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use \Sushi\Sushi;

    // provide at least an empty array so that count([]) works
    protected array $rows = [];

    // OPTIONAL: if you plan to load from an API on each request,
    // implement getRows() instead of $rows, but remember that
    // it won’t cache between requests:
    public function getRows(): array
    {
        // return your API‐fetched logs here
        return $this->rows;
    }

    // If your rows array might be empty, also define the schema:
    protected array $schema = [
        'C_Date'   => 'string',
        'C_Time'   => 'string',
        'C_Name'   => 'string',
        'C_Unique' => 'string',
        'L_Mode'   => 'integer',
        'L_Result' => 'integer',
    ];
}
