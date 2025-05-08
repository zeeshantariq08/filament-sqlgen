<?php

namespace ZeeshanTariq\FilamentSqlGen\Models;

use Illuminate\Database\Eloquent\Model;

class SqlGenLog extends Model
{
    protected $table = 'sql_gen_logs';

    protected $fillable = ['question', 'sql_query', 'response','response_time_ms'];

    protected $casts = [
        'sql_query' => 'json',  // Cast sql_query as JSON
        'response' => 'json',   // Cast response as JSON
    ];

    public function __construct(array $attributes = [])
    {
        $this->connection = config('filament-sqlgen.database_connection');
        parent::__construct($attributes);
    }

}
