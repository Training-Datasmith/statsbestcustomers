#!/usr/bin/env bash
set -euo pipefail

PHPUNIT_PHAR="${1:?phpunit phar path required}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

DB_HOST='127.0.0.1'

DOCKER_BIN="${DOCKER_BIN:-docker}"
read -r -a DOCKER_ARR <<< "${DOCKER_BIN}"
run_docker() {
  "${DOCKER_ARR[@]}" "$@"
}

run_single_test() {
  local workspace="$1"
  local filter="$2"
  run_docker run --rm \
    --network container:sbc-mariadb-phpunit \
    -v "${workspace}:/repo:rw" \
    -v "${PHPUNIT_PHAR}:/opt/phpunit/phpunit.phar:ro" \
    -e SBC_DB_HOST="${DB_HOST}" \
    -e SBC_DB_NAME=statsbestcustomers \
    -e SBC_DB_USER=sbc \
    -e SBC_DB_PASSWORD=sbc \
    -w /repo/tests/phpunit \
    statsbestcustomers-phpunit:local \
    php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
      /opt/phpunit/phpunit.phar --strict --colors=never \
      --bootstrap bootstrap.php \
      --filter "${filter}" \
      ModuleTestSuite.php \
    >"${workspace}/proof.out" 2>&1 && return 0 || return 1
}

assert_proof_fails_on_assertion() {
  local label="$1"
  local output="$2"
  if grep -q 'FAILURES!' "${output}" || grep -q 'Failed asserting' "${output}"; then
    if grep -E '^(PHP )?(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error):' "${output}" \
      | grep -v 'Deprecated option "--strict" used' | grep -q .; then
      echo "Proof ${label}: failed with PHP diagnostic instead of assertion" >&2
      cat "${output}" >&2
      exit 1
    fi
    echo "Proof ${label}: OK (assertion failure observed)"
    return 0
  fi
  echo "Proof ${label}: expected assertion failure" >&2
  cat "${output}" >&2
  exit 1
}

run_proof() {
  local label="$1"
  local filter="$2"
  local workspace
  workspace="$(mktemp -d)"
  chmod 0700 "${workspace}"

  (cd "${REPO_ROOT}" && tar cf - --exclude='.git' .) | (cd "${workspace}" && tar xf -)

  case "${label}" in
    upgrade-revert-broken-return)
      perl -0pi -e 's/return \(bool\) \$module->registerHook\([^\)]+\);/return true;/s' "${workspace}/upgrade/upgrade-2.0.4.php"
      ;;
    install-wrong-hook)
      perl -pi -e "s/registerHook\('displayAdminStatsModules'\)/registerHook('wrongHookName')/" "${workspace}/statsbestcustomers.php"
      ;;
    visit-count-regression)
      perl -pi -e 's/, o\.`id_order`//' "${workspace}/statsbestcustomers.php"
      ;;
    *)
      echo "Unknown proof label ${label}" >&2
      rm -rf "${workspace}"
      exit 1
      ;;
  esac

  if run_single_test "${workspace}" "${filter}"; then
    echo "Proof ${label}: suite unexpectedly passed" >&2
    cat "${workspace}/proof.out" >&2
    rm -rf "${workspace}"
    exit 1
  fi
  assert_proof_fails_on_assertion "${label}" "${workspace}/proof.out"
  rm -rf "${workspace}"
}

run_proof upgrade-revert-broken-return testUpgradeReturnsFalseWhenRegisterHookFails
run_proof install-wrong-hook testInstallRegistersDisplayAdminStatsModulesHook
run_proof visit-count-regression testVisitsAreNotMultipliedByOrders

echo 'All revert proofs passed.'
