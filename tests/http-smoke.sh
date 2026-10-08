#!/usr/bin/env bash
set -euo pipefail

: "${FLZU_BASE_URL:?FLZU_BASE_URL fehlt}"
: "${FLZU_USER:?FLZU_USER fehlt}"
: "${FLZU_PASSWORD:?FLZU_PASSWORD fehlt}"

workdir="$(mktemp -d)"
page="$workdir/page.html"
cookies="$workdir/cookies.txt"
teams="$workdir/teams.json"
created="$workdir/created.json"
conflict="$workdir/conflict.json"
vacation_id=""
token=""

cleanup() {
    if [[ -n "$vacation_id" && -n "$token" ]]; then
        curl --silent --show-error --insecure --user "$FLZU_USER:$FLZU_PASSWORD" \
            --cookie "$cookies" --cookie-jar "$cookies" \
            -H "requesttoken: $token" -H 'Content-Type: application/json' \
            -X DELETE --data '{}' "$FLZU_BASE_URL/index.php/apps/flzurlaub/api/vacations/$vacation_id" >/dev/null || true
    fi
    rm -rf "$workdir"
}
trap cleanup EXIT

curl --fail --silent --show-error --insecure --user "$FLZU_USER:$FLZU_PASSWORD" \
    --cookie-jar "$cookies" "$FLZU_BASE_URL/index.php/apps/flzurlaub/" --output "$page"
for contract in 'id="flzurlaub-app"' 'id="flz-vacation-team"' 'id="flz-vacation-own-form"' 'id="flz-vacation-calendar-body"'; do
    if ! grep -q "$contract" "$page"; then
        echo "App-DOM-Vertrag fehlt: $contract" >&2
        exit 1
    fi
done

token="$(sed -n 's/.*data-requesttoken="\([^"]*\)".*/\1/p' "$page" | head -n 1)"
if [[ -z "$token" ]]; then
    echo 'Request-Token fehlt.' >&2
    exit 1
fi

curl --fail --silent --show-error --insecure --user "$FLZU_USER:$FLZU_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" \
    "$FLZU_BASE_URL/index.php/apps/flzurlaub/api/teams" --output "$teams"
for contract in '"teams"' '"currentUser"'; do
    if ! grep -q "$contract" "$teams"; then
        echo "TeamAPI-Vertrag fehlt: $contract" >&2
        exit 1
    fi
done

payload="{\"employeeUid\":\"$FLZU_USER\",\"startDate\":\"2099-12-29\",\"endDate\":\"2099-12-30\",\"status\":\"planned\",\"note\":\"HTTP-Smoke\"}"
curl --fail --silent --show-error --insecure --user "$FLZU_USER:$FLZU_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" \
    -H "requesttoken: $token" -H 'Content-Type: application/json' \
    -X POST --data "$payload" "$FLZU_BASE_URL/index.php/apps/flzurlaub/api/vacations" --output "$created"
vacation_id="$(php -r '$data=json_decode(file_get_contents($argv[1]),true); echo $data["id"] ?? "";' "$created")"
if [[ -z "$vacation_id" ]]; then
    echo 'Testurlaub wurde nicht angelegt.' >&2
    exit 1
fi

status="$(curl --silent --show-error --insecure --user "$FLZU_USER:$FLZU_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" \
    -H "requesttoken: $token" -H 'Content-Type: application/json' \
    -X POST --data "$payload" --write-out '%{http_code}' \
    "$FLZU_BASE_URL/index.php/apps/flzurlaub/api/vacations" --output "$conflict")"
conflict_message="$(php -r '$data=json_decode(file_get_contents($argv[1]),true); echo $data["error"] ?? "";' "$conflict")"
if [[ "$status" != '409' ]] || [[ "$conflict_message" != *'überschneidet'* ]]; then
    echo "Urlaubsüberschneidung ergab keinen verständlichen HTTP-409-Konflikt." >&2
    exit 1
fi

curl --fail --silent --show-error --insecure --user "$FLZU_USER:$FLZU_PASSWORD" \
    --cookie "$cookies" --cookie-jar "$cookies" \
    -H "requesttoken: $token" -H 'Content-Type: application/json' \
    -X DELETE --data '{}' "$FLZU_BASE_URL/index.php/apps/flzurlaub/api/vacations/$vacation_id" >/dev/null
vacation_id=""

echo "Filzmann Urlaubsplanung HTTP smoke: OK ($FLZU_USER)"
