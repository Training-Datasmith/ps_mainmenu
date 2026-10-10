#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCKER="${DOCKER:-sudo docker}"
PHP_IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"
MARIADB_IMAGE="mariadb@sha256:13db7fcdebbd63680989a9a70b8dc4a2eb82e55d4d3878b4bf8c825eb8c7a4a5"
PHP_EXT_IMAGE="ps-mainmenu-php56-mysqli:local"
ARTIFACT_DIR="$(mktemp -d)"
RUNTIME_DIR="$(mktemp -d)"
TOOLING_DIR="${REPO_ROOT}/tests/tooling"
PHPUNIT_VERSION="4.8.36"
SMARTY_REF="2fc443806cdcaee4441be4d0bb09f8fa56a17f2c"
SMARTY_VERSION="3.1.48"

cleanup() {
  ${DOCKER} rm -f ps-mainmenu-mariadb >/dev/null 2>&1 || true
}
trap cleanup EXIT

if ! ${DOCKER} image inspect "${PHP_EXT_IMAGE}" >/dev/null 2>&1; then
  ${DOCKER} build -t "${PHP_EXT_IMAGE}" -f - "${REPO_ROOT}" <<'EOF'
FROM php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143
RUN docker-php-ext-install mysqli
EOF
fi

${DOCKER} rm -f ps-mainmenu-mariadb >/dev/null 2>&1 || true
${DOCKER} run -d --name ps-mainmenu-mariadb \
  -e MYSQL_DATABASE=ps_mainmenu_test \
  -e MYSQL_USER=ps \
  -e MYSQL_PASSWORD=ps_test \
  -e MYSQL_ROOT_PASSWORD=root_test \
  "${MARIADB_IMAGE}" \
  --character-set-server=utf8mb4 \
  --collation-server=utf8mb4_general_ci \
  --bind-address=127.0.0.1

for _ in $(seq 1 60); do
  if ${DOCKER} exec ps-mainmenu-mariadb mysqladmin ping -uroot -proot_test --silent; then
    break
  fi
  sleep 1
done
${DOCKER} exec ps-mainmenu-mariadb mysqladmin ping -uroot -proot_test --silent

DB_HOST="127.0.0.1"
DB_PORT="3306"

PHPUNIT_PHAR="${ARTIFACT_DIR}/phpunit-${PHPUNIT_VERSION}.phar"
PHPUNIT_ASC="${ARTIFACT_DIR}/phpunit-${PHPUNIT_VERSION}.phar.asc"
curl -fsSL "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar" -o "${PHPUNIT_PHAR}"
curl -fsSL "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar.asc" -o "${PHPUNIT_ASC}"
GNUPGHOME="$(mktemp -d)"
chmod 700 "${GNUPGHOME}"
export GNUPGHOME
gpg --batch --keyserver hkps://keyserver.ubuntu.com --recv-keys D8406D0D82947747293778314AA394086372C20A >/dev/null
gpg --batch --status-fd 1 --verify "${PHPUNIT_ASC}" "${PHPUNIT_PHAR}" >"${ARTIFACT_DIR}/gpg-verify.log" 2>&1
grep -q '\[GNUPG:\] VALIDSIG D8406D0D82947747293778314AA394086372C20A' "${ARTIFACT_DIR}/gpg-verify.log"
ACTUAL_SHA="$(sha256sum "${PHPUNIT_PHAR}" | awk '{print $1}')"
EXPECTED_SHA="$(cat "${TOOLING_DIR}/phpunit-${PHPUNIT_VERSION}.phar.sha256")"
test "${ACTUAL_SHA}" = "${EXPECTED_SHA}"

SMARTY_TGZ="${ARTIFACT_DIR}/smarty-${SMARTY_VERSION}.tar.gz"
curl -fsSL "https://github.com/smarty-php/smarty/archive/${SMARTY_REF}.tar.gz" -o "${SMARTY_TGZ}"
SMARTY_SHA="$(sha256sum "${SMARTY_TGZ}" | awk '{print $1}')"
test "${SMARTY_SHA}" = "$(cat "${TOOLING_DIR}/smarty-${SMARTY_VERSION}.tar.gz.sha256")"
tar -xzf "${SMARTY_TGZ}" -C "${ARTIFACT_DIR}"
SMARTY_ROOT="${ARTIFACT_DIR}/smarty-${SMARTY_REF}"
SMARTY_COMPILE="$(mktemp -d)"

SHUFFLED_XML="$(mktemp)"
${DOCKER} run --rm --network "container:ps-mainmenu-mariadb" \
  -v "${REPO_ROOT}:/workspace" \
  -v "${SHUFFLED_XML}:/shuffled.xml" \
  -w /workspace/tests \
  "${PHP_EXT_IMAGE}" \
  php -d error_reporting=-1 generate-shuffled-phpunit.php /shuffled.xml

run_phpunit() {
  local config="$1"
  local log="$2"
  local config_mount=()
  if [[ "${config}" != /workspace/* ]]; then
    config_mount=(-v "${config}:${config}:ro")
  fi
  ${DOCKER} run --rm --network "container:ps-mainmenu-mariadb" \
    "${config_mount[@]}" \
    -e PS_MAINMENU_DB_HOST="${DB_HOST}" \
    -e PS_MAINMENU_DB_PORT="${DB_PORT}" \
    -e PS_MAINMENU_DB_USER=ps \
    -e PS_MAINMENU_DB_PASS=ps_test \
    -e PS_MAINMENU_DB_NAME=ps_mainmenu_test \
    -e PS_MAINMENU_RUNTIME="${RUNTIME_DIR}" \
    -e PS_MAINMENU_SMARTY_ROOT="${SMARTY_ROOT}" \
    -e PS_MAINMENU_SMARTY_COMPILE="${SMARTY_COMPILE}" \
    -v "${REPO_ROOT}:/workspace" \
    -v "${PHPUNIT_PHAR}:/phpunit.phar:ro" \
    -v "${SMARTY_ROOT}:${SMARTY_ROOT}:ro" \
    -v "${RUNTIME_DIR}:${RUNTIME_DIR}" \
    -v "${SMARTY_COMPILE}:${SMARTY_COMPILE}" \
    -w /workspace/tests \
    "${PHP_EXT_IMAGE}" \
    php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 /phpunit.phar \
      --configuration "${config}" | tee "${log}"
  if grep -E 'PHPUnit .*Warning:' "${log}" >/dev/null; then
    echo "PHPUnit warnings detected in ${log}" >&2
    exit 1
  fi
  if grep -E '(Notice:|Warning:|Deprecated:|Strict Standards:|Parse error:|Fatal error:|Recoverable fatal error:)' "${log}" >/dev/null; then
    echo "PHP diagnostics detected in ${log}" >&2
    exit 1
  fi
  grep -q 'OK (' "${log}"
  grep -q 'tests' "${log}"
  if grep -E 'Skipped|Risky|WARNINGS' "${log}" >/dev/null; then
    echo "Non-clean PHPUnit summary in ${log}" >&2
    exit 1
  fi
}

LOG1="${ARTIFACT_DIR}/phpunit-default.log"
LOG2="${ARTIFACT_DIR}/phpunit-shuffled.log"
run_phpunit /workspace/tests/phpunit.xml "${LOG1}"
run_phpunit "${SHUFFLED_XML}" "${LOG2}"

PHP_VERSION="$(${DOCKER} run --rm "${PHP_EXT_IMAGE}" php -r 'echo PHP_VERSION;')"
MARIADB_VERSION="$(${DOCKER} exec ps-mainmenu-mariadb mysql -uroot -proot_test -Nse 'SELECT VERSION();')"

echo "SUITE_ARTIFACT_DIR=${ARTIFACT_DIR}"
echo "PHP_VERSION=${PHP_VERSION}"
echo "MARIADB_VERSION=${MARIADB_VERSION}"
