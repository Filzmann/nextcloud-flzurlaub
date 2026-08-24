<?php

declare(strict_types=1);

if (!defined('OC_CONSOLE')) define('OC_CONSOLE', true);
require dirname(__DIR__, 4) . '/lib/base.php';

use Doctrine\DBAL\Schema\Schema;
use OCA\AdUrlaub\Migration\Version000001Date202607130001;
use OCA\AdUrlaub\Migration\Version000003Date202607130003;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;

/**
 * Zweck: Prüft Fresh- und Upgrade-Schema von AD Urlaub gegen die reale lokale Nextcloud-Datenbank.
 * Isolation: Ausschließlich zufällig benannte temporäre Tabellen werden erzeugt und im finally entfernt.
 * Vertrag: Die aktuellen Migrationen erzeugen das kanonische Schema; das frühere Import-Schema verliert nur
 * seine Herkunftsfelder und bewahrt vorhandene Fachdaten auch bei fachlich widersprüchlichen Altdaten.
 */

final class IsolatedVacationSchemaWrapper extends SchemaWrapper {
    public function __construct(Connection $connection, Schema $schema, private string $physicalTable) {
        parent::__construct($connection, $schema);
    }

    public function getTable($tableName) {
        return $this->schema->getTable($this->physicalTable);
    }

    public function hasTable($tableName) {
        return $this->schema->hasTable($this->physicalTable);
    }

    public function createTable($tableName) {
        return $this->schema->createTable($this->physicalTable);
    }

    public function dropTable($tableName) {
        return $this->schema->dropTable($this->physicalTable);
    }
}

final class SilentMigrationOutput implements IOutput {
    public function debug(string $message): void {}
    public function info($message): void {}
    public function warning($message): void {}
    public function startProgress($max = 0): void {}
    public function advance($step = 1, $description = ''): void {}
    public function finishProgress(): void {}
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

/** @var Connection $connection */
$connection = \OCP\Server::get(Connection::class);
$platform = $connection->getDatabasePlatform();
$schemaManager = $connection->createSchemaManager();
$output = new SilentMigrationOutput();
$prefix = $connection->getPrefix();
$freshLogical = 'adu_mig_f_' . bin2hex(random_bytes(5));
$upgradeLogical = 'adu_mig_u_' . bin2hex(random_bytes(5));
$freshPhysical = $prefix . $freshLogical;
$upgradePhysical = $prefix . $upgradeLogical;
$createdTables = [];

$applySql = static function (array $statements) use ($connection): void {
    foreach ($statements as $statement) $connection->executeStatement($statement);
};
$quote = static fn(string $identifier): string => $platform->quoteIdentifier($identifier);

try {
    $freshSchema = new Schema();
    $freshWrapper = new IsolatedVacationSchemaWrapper($connection, $freshSchema, $freshPhysical);
    (new Version000001Date202607130001())->changeSchema($output, static fn() => $freshWrapper, []);
    (new Version000003Date202607130003())->changeSchema($output, static fn() => $freshWrapper, []);
    $applySql($freshSchema->toSql($platform));
    $createdTables[] = $freshPhysical;

    $freshTable = $schemaManager->introspectTable($freshPhysical);
    $expectedColumns = [
        'id',
        'employee_uid',
        'start_date',
        'end_date',
        'status',
        'note',
        'created_by_uid',
        'created_at',
        'updated_at',
    ];
    $assert(array_keys($freshTable->getColumns()) === $expectedColumns, 'Fresh-Installation erzeugt nicht das kanonische Urlaubsschema.');
    $assert($freshTable->hasPrimaryKey(), 'Fresh-Installation erzeugt keinen Primärschlüssel.');
    $assert($freshTable->hasIndex('adu_employee_range'), 'Fresh-Installation erzeugt den Mitarbeiter-/Zeitraumindex nicht.');
    $assert($freshTable->hasIndex('adu_range'), 'Fresh-Installation erzeugt den Zeitraumindex nicht.');
    $assert(!$freshTable->hasColumn('source_app') && !$freshTable->hasColumn('source_id'), 'Fresh-Installation enthält entfernte Importfelder.');

    $connection->executeStatement(
        'INSERT INTO ' . $quote($freshPhysical)
        . ' (' . implode(', ', array_map($quote, ['employee_uid', 'start_date', 'end_date', 'status', 'note', 'created_by_uid', 'created_at', 'updated_at'])) . ')'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        ['fresh-user', '2026-08-01', '2026-08-02', 'planned', 'synthetisch', 'fresh-user', '2026-07-27 08:00:00', '2026-07-27 08:00:00'],
    );
    $freshRow = $connection->fetchAssociative('SELECT employee_uid, note FROM ' . $quote($freshPhysical));
    $assert($freshRow === ['employee_uid' => 'fresh-user', 'note' => 'synthetisch'], 'Das Fresh-Schema ist nicht beschreib- und lesbar.');

    $legacySchema = new Schema();
    $legacyWrapper = new IsolatedVacationSchemaWrapper($connection, $legacySchema, $upgradePhysical);
    (new Version000001Date202607130001())->changeSchema($output, static fn() => $legacyWrapper, []);
    $legacyTable = $legacySchema->getTable($upgradePhysical);
    $legacyTable->addColumn('source_app', Types::STRING, ['length' => 32, 'notnull' => false]);
    $legacyTable->addColumn('source_id', Types::BIGINT, ['notnull' => false]);
    $legacyTable->addUniqueIndex(['source_app', 'source_id'], 'adu_source_unique');
    $applySql($legacySchema->toSql($platform));
    $createdTables[] = $upgradePhysical;

    $connection->executeStatement(
        'INSERT INTO ' . $quote($upgradePhysical)
        . ' (' . implode(', ', array_map($quote, ['employee_uid', 'start_date', 'end_date', 'status', 'note', 'created_by_uid', 'created_at', 'updated_at', 'source_app', 'source_id'])) . ')'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        ['legacy-user', '2026-08-10', '2026-08-01', 'planned', 'widersprüchlicher Altbestand', 'legacy-user', '2026-07-13 10:00:00', '2026-07-13 10:00:00', 'legacy-source', 42],
    );

    $upgradedSchema = clone $legacySchema;
    $upgradeWrapper = new IsolatedVacationSchemaWrapper($connection, $upgradedSchema, $upgradePhysical);
    (new Version000003Date202607130003())->changeSchema($output, static fn() => $upgradeWrapper, []);
    $applySql($legacySchema->getMigrateToSql($upgradedSchema, $platform));

    $upgradedTable = $schemaManager->introspectTable($upgradePhysical);
    $assert(!$upgradedTable->hasColumn('source_app') && !$upgradedTable->hasColumn('source_id'), 'Upgrade entfernt die früheren Importfelder nicht.');
    $assert(!$upgradedTable->hasIndex('adu_source_unique'), 'Upgrade entfernt den früheren Importindex nicht.');
    $assert($upgradedTable->hasIndex('adu_employee_range') && $upgradedTable->hasIndex('adu_range'), 'Upgrade verliert fachliche Indizes.');
    $legacyRow = $connection->fetchAssociative(
        'SELECT employee_uid, start_date, end_date, status, note FROM ' . $quote($upgradePhysical)
    );
    $assert(
        $legacyRow === [
            'employee_uid' => 'legacy-user',
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-01',
            'status' => 'planned',
            'note' => 'widersprüchlicher Altbestand',
        ],
        'Upgrade verändert oder verliert vorhandene Fachdaten.'
    );

    echo "AD Urlaub Fresh-/Upgrade-Migrationsschema: OK\n";
} finally {
    foreach (array_reverse($createdTables) as $table) {
        if ($schemaManager->tablesExist([$table])) $schemaManager->dropTable($table);
    }
}
