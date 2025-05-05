<?php

namespace ZeeshanTariq\FilamentSqlGen\Services;

interface SqlGenServiceInterface
{
    public function generateSql(string $question): string;
}
