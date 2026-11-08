#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

PHP_IMAGE='php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143'
MARIADB_IMAGE='mariadb@sha256:a530aeeefd82f4fa5150f391b6c75462140904780338766f6b03acecb1cca3ce'
PHPUNIT_VERSION='4.8.36'
PHPUNIT_SHA256='ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e'
PHPUNIT_KEY='4AA394086372C20A'
PHPUNIT_FPR='D8406D0D82947747293778314AA394086372C20A'
EXPECTED_TESTS=28
DB_HOST='127.0.0.1'

DOCKER_BIN="${DOCKER_BIN:-docker}"
read -r -a DOCKER_ARR <<< "${DOCKER_BIN}"
if ! command -v "${DOCKER_ARR[0]}" >/dev/null 2>&1; then
  echo 'docker is not available in PATH' >&2
  exit 1
fi
run_docker() {
  "${DOCKER_ARR[@]}" "$@"
}

TMP_RUN="$(mktemp -d)"
chmod 0700 "${TMP_RUN}"
cleanup() {
  run_docker rm -f sbc-mariadb-phpunit >/dev/null 2>&1 || true
  rm -rf "${TMP_RUN}"
}
trap cleanup EXIT

SORTED_TESTS=(
  "${SCRIPT_DIR}/DirectoryGuardTest.php"
  "${SCRIPT_DIR}/StatsBestCustomersInstallTest.php"
  "${SCRIPT_DIR}/StatsBestCustomersMetadataTest.php"
  "${SCRIPT_DIR}/StatsBestCustomersHookTest.php"
  "${SCRIPT_DIR}/StatsBestCustomersGetDataTest.php"
  "${SCRIPT_DIR}/UpgradeModule204Test.php"
)

for test_file in "${SORTED_TESTS[@]}"; do
  if [[ ! -f "${test_file}" ]]; then
    echo "Missing planned test file: ${test_file}" >&2
    exit 1
  fi
done
mapfile -t DISCOVERED < <(find "${SCRIPT_DIR}" -maxdepth 1 -name '*Test.php' -type f | sort)
if ((${#DISCOVERED[@]} != ${#SORTED_TESTS[@]})); then
  echo "Test file count drift: expected ${#SORTED_TESTS[@]} got ${#DISCOVERED[@]}" >&2
  printf '%s\n' "${DISCOVERED[@]}" >&2
  exit 1
fi
for test_file in "${SORTED_TESTS[@]}"; do
  if ! printf '%s\n' "${DISCOVERED[@]}" | grep -Fxq "${test_file}"; then
    echo "Planned test file missing from directory scan: ${test_file}" >&2
    exit 1
  fi
done

run_docker pull "${PHP_IMAGE}" >/dev/null
run_docker pull "${MARIADB_IMAGE}" >/dev/null

curl -fsSL -o "${TMP_RUN}/phpunit.phar" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"
curl -fsSL -o "${TMP_RUN}/phpunit.phar.asc" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar.asc"
echo "${PHPUNIT_SHA256}  ${TMP_RUN}/phpunit.phar" | sha256sum -c -

export GNUPGHOME="${TMP_RUN}/gnupg"
mkdir -p "${GNUPGHOME}"
chmod 0700 "${GNUPGHOME}"
gpg --batch --homedir "${GNUPGHOME}" --keyserver hkps://keyserver.ubuntu.com --recv-keys "${PHPUNIT_KEY}" >/dev/null
gpg_output="$(gpg --batch --status-fd 1 --homedir "${GNUPGHOME}" --verify "${TMP_RUN}/phpunit.phar.asc" "${TMP_RUN}/phpunit.phar" 2>&1)"
echo "${gpg_output}" | grep -q "VALIDSIG ${PHPUNIT_FPR}"
echo "${gpg_output}" | grep -q "GOODSIG"

run_docker rm -f sbc-mariadb-phpunit >/dev/null 2>&1 || true

run_docker run -d --name sbc-mariadb-phpunit \
  -e MYSQL_DATABASE=statsbestcustomers \
  -e MYSQL_USER=sbc \
  -e MYSQL_PASSWORD=sbc \
  -e MYSQL_ROOT_PASSWORD=root \
  "${MARIADB_IMAGE}" >/dev/null

for _ in $(seq 1 90); do
  if run_docker exec sbc-mariadb-phpunit mysqladmin ping -uroot -proot --silent >/dev/null 2>&1; then
  run_docker exec sbc-mariadb-phpunit mysql -uroot -proot -e "SELECT 1" >/dev/null 2>&1 && break
  fi
  sleep 1
done
run_docker exec sbc-mariadb-phpunit mysqladmin ping -uroot -proot --silent >/dev/null

run_docker build -t statsbestcustomers-phpunit:local "${SCRIPT_DIR}" >/dev/null

for _ in $(seq 1 30); do
  if run_docker run --rm --network container:sbc-mariadb-phpunit \
    -e SBC_DB_HOST="${DB_HOST}" \
    -e SBC_DB_NAME=statsbestcustomers \
    -e SBC_DB_USER=sbc \
    -e SBC_DB_PASSWORD=sbc \
    statsbestcustomers-phpunit:local \
    php -r '$pdo = new PDO("mysql:host=".getenv("SBC_DB_HOST").";dbname=".getenv("SBC_DB_NAME"), getenv("SBC_DB_USER"), getenv("SBC_DB_PASSWORD"));' >/dev/null 2>&1; then
    break
  fi
  sleep 1
done
run_docker run --rm --network container:sbc-mariadb-phpunit \
  -e SBC_DB_HOST="${DB_HOST}" \
  -e SBC_DB_NAME=statsbestcustomers \
  -e SBC_DB_USER=sbc \
  -e SBC_DB_PASSWORD=sbc \
  statsbestcustomers-phpunit:local \
  php -r '$pdo = new PDO("mysql:host=".getenv("SBC_DB_HOST").";dbname=".getenv("SBC_DB_NAME"), getenv("SBC_DB_USER"), getenv("SBC_DB_PASSWORD"));' >/dev/null

assert_clean_summary() {
  local log="$1"
  if ! grep -q "OK (${EXPECTED_TESTS} tests" "${log}"; then
    echo 'Missing OK summary with expected test count' >&2
    cat "${log}" >&2
    exit 1
  fi
  if grep -qiE 'skipped|risky|incomplete' "${log}"; then
    echo 'Unexpected skipped/risky/incomplete in summary' >&2
    exit 1
  fi
  if grep -E '^(PHP )?(Notice|Warning|Deprecated|Strict Standards|Parse error|Fatal error):' "${log}" \
    | grep -v 'Deprecated option "--strict" used' | grep -q .; then
    echo 'PHP diagnostic in PHPUnit output' >&2
    exit 1
  fi
}

run_phpunit() {
  local test_order="${1:-}"
  local -a extra_env=()
  if [[ -n "${test_order}" ]]; then
    extra_env+=(-e "SBC_TEST_FILE_ORDER=${test_order}")
  fi
  run_docker run --rm \
    --network container:sbc-mariadb-phpunit \
    -v "${REPO_ROOT}:/repo:ro" \
    -v "${TMP_RUN}/phpunit.phar:/opt/phpunit/phpunit.phar:ro" \
    -e SBC_DB_HOST="${DB_HOST}" \
    -e SBC_DB_NAME=statsbestcustomers \
    -e SBC_DB_USER=sbc \
    -e SBC_DB_PASSWORD=sbc \
    "${extra_env[@]}" \
    -w /repo/tests/phpunit \
    statsbestcustomers-phpunit:local \
    php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
      /opt/phpunit/phpunit.phar --strict --colors=never \
      --bootstrap bootstrap.php ModuleTestSuite.php 2>&1 | tee "${TMP_RUN}/phpunit-last.log"
}

echo '--- PHPUnit default order ---'
run_phpunit ""
assert_clean_summary "${TMP_RUN}/phpunit-last.log"

FILES_ARG=""
for f in "${SORTED_TESTS[@]}"; do
  rel="${f#${REPO_ROOT}/}"
  FILES_ARG="${FILES_ARG}:/repo/${rel}"
done
FILES_ARG="${FILES_ARG:1}"
SHUFFLED_ORDER="$(run_docker run --rm -e FILES="${FILES_ARG}" statsbestcustomers-phpunit:local php -r '$files = explode(":", getenv("FILES")); srand(20261108); shuffle($files); echo implode(":", $files);')"

echo '--- PHPUnit shuffled order ---'
run_phpunit "${SHUFFLED_ORDER}"
assert_clean_summary "${TMP_RUN}/phpunit-last.log"

echo '--- Revert proofs ---'
DOCKER_BIN="${DOCKER_BIN}" bash "${SCRIPT_DIR}/run-proofs.sh" "${TMP_RUN}/phpunit.phar"

echo "Suite passed (${EXPECTED_TESTS} tests, default + shuffled)."
