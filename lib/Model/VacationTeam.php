<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Model;

use InvalidArgumentException;
use OCA\LocalBase\Model\ModelApiTrait;

/** Zweck: Beschreibt eine dynamische Urlaubssicht aus ASN- oder Organisationsgruppen. */
final class VacationTeam {
    use ModelApiTrait;

    public function __construct(
        private string $id,
        private string $code,
        private string $displayName,
        private string $category,
        private array $employees,
        private int $sortOrder = 0,
    ) {
        if ($id === '' || $displayName === '' || !in_array($category, ['asn', 'organization'], true)) {
            throw new InvalidArgumentException('Ungültiges Urlaubsteam.');
        }
    }

    protected static function fromArray(array $data): self {
        return new self(
            (string)($data['id'] ?? ''),
            (string)($data['code'] ?? ''),
            (string)($data['displayName'] ?? ''),
            (string)($data['category'] ?? ''),
            is_array($data['employees'] ?? null) ? $data['employees'] : [],
            (int)($data['sortOrder'] ?? 0),
        );
    }

    public function id(): string { return $this->id; }
    public function code(): string { return $this->code; }
    public function displayName(): string { return $this->displayName; }
    public function employees(): array { return $this->employees; }
    public function sortOrder(): int { return $this->sortOrder; }
    public function contains(string $uid): bool { return in_array($uid, array_column($this->employees, 'uid'), true); }

    public function toArray(): array {
        return ['id' => $this->id, 'code' => $this->code, 'displayName' => $this->displayName, 'category' => $this->category, 'employees' => $this->employees, 'sortOrder' => $this->sortOrder];
    }
}
