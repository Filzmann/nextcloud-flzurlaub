<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** Zweck: Legt die app-eigene Urlaubsquelle ohne Abhängigkeit von Kalenderdaten an. */
final class Version000001Date202607130001 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        if ($schema->hasTable('flz_vacation_vacations')) return null;
        $table = $schema->createTable('flz_vacation_vacations');
        $table->addColumn('id', Types::BIGINT, ['autoincrement' => true, 'notnull' => true]);
        $table->addColumn('employee_uid', Types::STRING, ['length' => 64, 'notnull' => true]);
        $table->addColumn('start_date', Types::STRING, ['length' => 10, 'notnull' => true]);
        $table->addColumn('end_date', Types::STRING, ['length' => 10, 'notnull' => true]);
        $table->addColumn('status', Types::STRING, ['length' => 16, 'notnull' => true]);
        $table->addColumn('note', Types::STRING, ['length' => 500, 'notnull' => true, 'default' => '']);
        $table->addColumn('created_by_uid', Types::STRING, ['length' => 64, 'notnull' => true]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['employee_uid', 'start_date', 'end_date'], 'flz_vacation_employee_range');
        $table->addIndex(['start_date', 'end_date'], 'flz_vacation_range');
        return $schema;
    }
}
