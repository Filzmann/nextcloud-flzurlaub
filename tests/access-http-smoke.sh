#!/usr/bin/env bash
set -euo pipefail

: "${FLZU_BASE_URL:?FLZU_BASE_URL fehlt}"
: "${FLZU_USER:?FLZU_USER fehlt}"
: "${FLZU_PASSWORD:?FLZU_PASSWORD fehlt}"
: "${FLZU_EXPECTED:?FLZU_EXPECTED fehlt}"

response="$(mktemp)"
trap 'rm -f "$response"' EXIT

curl --fail --silent --show-error --insecure --user "$FLZU_USER:$FLZU_PASSWORD" \
    "$FLZU_BASE_URL/index.php/apps/flzurlaub/api/teams" --output "$response"

FLZU_RESPONSE="$response" php -r '
$state = json_decode(file_get_contents(getenv("FLZU_RESPONSE")), true, flags: JSON_THROW_ON_ERROR);
$actual = [];
foreach ($state["teams"] ?? [] as $team) {
    foreach ($team["employees"] ?? [] as $employee) {
        $uid = (string)($employee["uid"] ?? "");
        if ($uid === "") continue;
        $actual[$uid] = [
            "canManage" => (bool)($employee["canManage"] ?? false),
            "canApprove" => (bool)($employee["canApprove"] ?? false),
        ];
    }
}
foreach (explode(",", getenv("FLZU_EXPECTED")) as $expectation) {
    [$uid, $raw] = explode("=", $expectation, 2);
    [$visible, $manage, $approve] = explode(":", $raw, 3);
    $isVisible = array_key_exists($uid, $actual);
    if ($isVisible !== ($visible === "true")) {
        fwrite(STDERR, "Sichtbarkeitsvertrag verletzt für {$uid}: erwartet {$visible}." . PHP_EOL);
        exit(1);
    }
    if (!$isVisible) continue;
    foreach (["canManage" => $manage, "canApprove" => $approve] as $key => $expected) {
        if ($expected === "*") continue;
        $expectedValue = $expected === "true";
        if ($actual[$uid][$key] !== $expectedValue) {
            fwrite(STDERR, "Rechtevertrag {$key} verletzt für {$uid}: erwartet {$expected}." . PHP_EOL);
            exit(1);
        }
    }
}
'

echo "Filzmann Urlaubsplanung access HTTP smoke: OK ($FLZU_USER)"
