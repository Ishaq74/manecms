<?php

use Illuminate\Support\Facades\Schema;

it('assigns every database table to exactly one owning context', function (): void {
    $tables = Schema::getTableListing(schemaQualified: false);
    sort($tables);

    $registered = array_keys(config()->array('domains.tables'));
    sort($registered);

    expect($registered)->toBe($tables);
});
