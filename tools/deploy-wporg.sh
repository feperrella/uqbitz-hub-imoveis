#!/usr/bin/env bash
#
# Deploy do plugin para o SVN do WordPress.org.
#
# Sincroniza trunk/ e cria a tag da versão atual (lida de trunk/readme.txt),
# depois faz o commit no SVN do wp.org.
#
# O commit final é AUTENTICADO: o svn vai pedir seu usuário/senha do
# WordPress.org (ou usar o Keychain). Rode você mesmo — o script nunca
# recebe nem guarda a senha.
#
# Uso:
#   tools/deploy-wporg.sh            # publica a versão que está no trunk
#   DRY_RUN=1 tools/deploy-wporg.sh  # prepara e mostra o diff, sem commitar
#
set -euo pipefail

SLUG="uqbitz-hub-imoveis"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}"

# Raiz do repositório = pasta acima deste script (funciona de qualquer lugar).
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TRUNK="${REPO}/trunk"

# --- A versão vem do Stable tag do readme e precisa bater nos três lugares. ---
version_from() { grep -m1 "$1" "$2" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1; }
VERSION="$(version_from 'Stable tag:'      "${TRUNK}/readme.txt")"
HDR="$(version_from 'Version:'             "${TRUNK}/${SLUG}.php")"
CONST="$(version_from "UQBHI_VERSION',"    "${TRUNK}/${SLUG}.php")"

if [[ -z "${VERSION}" ]]; then
	echo "ERRO: não encontrei 'Stable tag:' em trunk/readme.txt" >&2
	exit 1
fi
if [[ "${VERSION}" != "${HDR}" || "${VERSION}" != "${CONST}" ]]; then
	echo "ERRO: versões divergentes — alinhe os três antes de publicar:" >&2
	echo "  Stable tag (readme.txt) : ${VERSION}" >&2
	echo "  Version (header php)     : ${HDR}" >&2
	echo "  UQBHI_VERSION            : ${CONST}" >&2
	exit 1
fi
echo "==> Publicando ${SLUG} versão ${VERSION}"

# Checkout temporário, removido ao sair.
WORK="$(mktemp -d "/tmp/${SLUG}-svn.XXXXXX")"
trap 'rm -rf "${WORK}"' EXIT

echo "==> Checkout do SVN (raso: só trunk + tags)"
svn co "${SVN_URL}" "${WORK}" --depth immediates
svn up "${WORK}/trunk" --set-depth infinity

echo "==> Sincronizando trunk/"
rsync -a --delete --exclude='.svn' --exclude='.DS_Store' "${TRUNK}/" "${WORK}/trunk/"

cd "${WORK}"
svn add --force trunk >/dev/null
# Registrar remoções, se houver (arquivos que sumiram do trunk).
svn status trunk | awk '/^!/ {print $2}' | while read -r f; do svn rm "${f}"; done

if svn ls "${SVN_URL}/tags/${VERSION}" >/dev/null 2>&1; then
	echo "AVISO: a tag ${VERSION} já existe no wp.org — não será recriada (só o trunk é atualizado)."
else
	echo "==> Criando tag ${VERSION}"
	svn cp trunk "tags/${VERSION}"
fi

echo
echo "==> Mudanças a publicar:"
svn status
echo

if [[ "${DRY_RUN:-0}" == "1" ]]; then
	echo "DRY_RUN=1 — nada foi commitado. Checkout em ${WORK} (removido ao sair)."
	exit 0
fi

read -r -p "Publicar no WordPress.org como versão ${VERSION}? (digite 'yes') " ANS
if [[ "${ANS}" != "yes" ]]; then
	echo "Abortado. Nada foi commitado."
	exit 0
fi

svn ci -m "Release ${VERSION}"
echo "==> Publicado. O wp.org e os sites podem levar até ~12h para oferecer a atualização."
echo "    Para forçar num site: Painel -> Atualizações -> \"Verificar novamente\"."
