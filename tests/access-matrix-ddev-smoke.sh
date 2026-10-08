#!/usr/bin/env bash
set -euo pipefail

base_url="${FLZU_BASE_URL:-https://nextcloud-dev.ddev.site}"
ddev_project="${FLZU_DDEV_PROJECT:-$(cd "$(dirname "$0")/../../nextcloud-dev" && pwd)}"
suffix="$(date +%s)-$$"
# Nur im Arbeitsspeicher vorhandenes Einmalpasswort für alle temporären Matrix-Konten.
password="$(php -r 'echo bin2hex(random_bytes(24));')"
team_code="Smoke$$"
team_group="flz-ASN-$team_code"
created_users=()

occ() {
    (cd "$ddev_project" && ddev exec -d /var/www/html/html php occ "$@")
}

cleanup() {
    local uid
    for uid in "${created_users[@]}"; do
        occ user:delete "$uid" >/dev/null 2>&1 || true
    done
    occ group:delete "$team_group" >/dev/null 2>&1 || true
}
trap cleanup EXIT

create_user() {
    local uid="$1"
    shift
    (cd "$ddev_project" && ddev exec -d /var/www/html/html env OC_PASS="$password" php occ user:add --password-from-env "$uid") >/dev/null
    created_users+=("$uid")
    local group
    for group in "$@"; do
        occ group:adduser "$group" "$uid" >/dev/null
    done
}

assert_access() {
    local uid="$1"
    local expected="$2"
    FLZU_BASE_URL="$base_url" FLZU_USER="$uid" FLZU_PASSWORD="$password" FLZU_EXPECTED="$expected" \
        "$(dirname "$0")/access-http-smoke.sh"
}

occ group:add "$team_group" >/dev/null

prefix="flz-vacation-smoke-${suffix}"
pdl="${prefix}-pdl"
bl_now="${prefix}-bl-now"
bo_actor="${prefix}-bo-actor"
pfk_actor="${prefix}-pfk-actor"
eb_actor="${prefix}-eb"
assistant_actor="${prefix}-assistant"
pfk_target="${prefix}-pfk-target"
bo_no="${prefix}-bo-no"
bo_west="${prefix}-bo-west"
bo_south="${prefix}-bo-south"
bo_peer="${prefix}-bo-peer"
pdl_target="${prefix}-pdl-target"
bl_target="${prefix}-bl-target"

create_user "$pdl" flz-PDL
create_user "$bl_now" flz-BL flz-Bereich-Nordost flz-Bereich-West
create_user "$bo_actor" flz-Buero flz-Bereich-Nordost
create_user "$pfk_actor" flz-PFK
create_user "$eb_actor" flz-EB flz-Bereich-West "$team_group"
create_user "$assistant_actor" "$team_group"
create_user "$pfk_target" flz-PFK
create_user "$bo_no" flz-Buero flz-Bereich-Nordost
create_user "$bo_west" flz-Buero flz-Bereich-West
create_user "$bo_south" flz-Buero flz-Bereich-Sued
create_user "$bo_peer" flz-Buero flz-Bereich-Nordost
create_user "$pdl_target" flz-PDL
create_user "$bl_target" flz-BL flz-Bereich-Nordost flz-Bereich-West

assert_access "$pdl" "$pdl=true:true:false,$pfk_target=true:true:true,$bo_no=false:*:*"
assert_access "$bl_now" "$bl_now=true:true:false,$bo_no=true:true:true,$bo_west=true:true:true,$bo_south=false:*:*,$pfk_target=false:*:*"
assert_access "$bo_actor" "$bo_actor=true:true:false,$bo_peer=true:*:*,$bo_west=false:*:*,$bl_target=true:false:false"
assert_access "$pfk_actor" "$pfk_actor=true:true:false,$pfk_target=true:*:*,$pdl_target=false:*:*"
assert_access "$eb_actor" "$eb_actor=true:true:false,$assistant_actor=true:true:true,$bo_west=false:*:*"
assert_access "$assistant_actor" "$assistant_actor=true:true:false,$eb_actor=true:*:*,$pfk_target=false:*:*"

echo "Filzmann Urlaubsplanung DDEV access matrix smoke: OK"
