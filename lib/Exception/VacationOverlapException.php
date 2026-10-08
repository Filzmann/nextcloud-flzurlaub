<?php

declare(strict_types=1);

namespace OCA\FlzUrlaub\Exception;

/** Zweck: Kennzeichnet einen Urlaubszeitraum, der sich mit einem Urlaub derselben Person überschneidet. */
final class VacationOverlapException extends \RuntimeException {
    public function __construct() {
        parent::__construct('Der Zeitraum überschneidet sich mit einem vorhandenen Urlaub dieser Person.');
    }
}
