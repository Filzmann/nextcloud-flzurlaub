<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use OCA\LocalBase\Model\ModelApiTrait;

/** Zweck: Validiertes persistentes Urlaubsobjekt mit inklusivem Enddatum. */
final class Vacation {
    use ModelApiTrait;
    public const STATUS_PLANNED = 'planned';
    public const STATUS_APPROVED = 'approved';

    private function __construct(private ?int $id, private string $employeeUid, private string $startDate, private string $endDate, private string $status, private string $note) {
        if ($employeeUid === '' || !$this->validDate($startDate) || !$this->validDate($endDate) || $startDate > $endDate) throw new InvalidArgumentException('Ungültiger Urlaubszeitraum.');
        if (!in_array($status, [self::STATUS_PLANNED, self::STATUS_APPROVED], true)) throw new InvalidArgumentException('Ungültiger Urlaubsstatus.');
        if (strlen($note) > 500) throw new InvalidArgumentException('Die Notiz ist zu lang.');
    }

    protected static function fromArray(array $data): self {
        return new self(isset($data['id']) ? (int)$data['id'] : null, trim((string)($data['employeeUid'] ?? '')), (string)($data['startDate'] ?? ''), (string)($data['endDate'] ?? ''), (string)($data['status'] ?? ''), trim((string)($data['note'] ?? '')));
    }
    private function validDate(string $value): bool { $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value); return $date !== false && $date->format('Y-m-d') === $value; }
    public function id(): ?int { return $this->id; }
    public function employeeUid(): string { return $this->employeeUid; }
    public function startDate(): string { return $this->startDate; }
    public function endDate(): string { return $this->endDate; }
    public function status(): string { return $this->status; }
    public function note(): string { return $this->note; }
    public function toArray(): array { return ['id' => $this->id, 'employeeUid' => $this->employeeUid, 'startDate' => $this->startDate, 'endDate' => $this->endDate, 'status' => $this->status, 'note' => $this->note, 'marker' => $this->status === self::STATUS_APPROVED ? 'U' : 'U?', 'blocks' => $this->status === self::STATUS_APPROVED]; }
}
