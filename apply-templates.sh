#!/usr/bin/env bash
set -Eeuo pipefail

[ -f versions.json ]

jqt='.jq-template.awk'
if [ -n "${BASHBREW_SCRIPTS:-}" ]; then
	jqt="$BASHBREW_SCRIPTS/jq-template.awk"
elif [ "$BASH_SOURCE" -nt "$jqt" ]; then
	wget -qO "$jqt" 'https://github.com/docker-library/bashbrew/raw/5f0c26381fb7cc78b2d217d58007800bdcfbcfa1/scripts/jq-template.awk'
fi

if [ "$#" -eq 0 ]; then
	versions="$(jq -r 'keys | map(@sh) | join(" ")' versions.json)"
	eval "set -- $versions"
fi

generated_warning() {
	cat <<-EOH
		#
		# NOTA: Arquivo gerado automaticamente
		#
		# Não o edite diretamente.
		#

	EOH
}

for version; do
	export version

	phpVersions="$(jq -r '.[env.version].phpVersions | map(@sh) | join(" ")' versions.json)"
	eval "phpVersions=( $phpVersions )"
	variants="$(jq -r '.[env.version].variants | map(@sh) | join(" ")' versions.json)"
	eval "variants=( $variants )"
	ocBrVersion="$(jq -r '.[env.version].version' versions.json)"

	export ocBrVersion

	for phpVersion in "${phpVersions[@]}"; do
		export phpVersion

		for variant in "${variants[@]}"; do
			export variant

			dir="$version/php$phpVersion/$variant"
			mkdir -p "$dir"

			echo "processing $dir ..."

			{
				generated_warning
				gawk -f "$jqt" Dockerfile.template
			} > "$dir/Dockerfile"

			cp -ar ./scripts "$dir/"
			cp -a docker-entrypoint.sh "$dir/"
		done
	done
done
