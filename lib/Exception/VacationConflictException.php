<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Exception;

use RuntimeException;

/** Zweck: Transportiert sichere read-only Konfliktdetails bis zur API-Antwort. */
final class VacationConflictException extends RuntimeException {
    public function __construct(private array $conflicts) { parent::__construct('Der Urlaub überschneidet sich mit vorhandenen Kalendereinträgen.'); }
    public function conflicts(): array { return $this->conflicts; }
}
