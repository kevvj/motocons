#!/usr/bin/env bash
# Revisa que un PR solo toque lo de su grupo.
# Uso: carpetas_pr.sh <rama_destino> <rama_origen> <autor>
set -euo pipefail

destino="$1"
origen="$2"
autor="$3"
DUENO="kevvj"

# A main solo se llega desde dev, sin excepciones.
if [ "$destino" = "main" ] && [ "$origen" != "dev" ]; then
    echo "::error::A main solo entran PRs desde dev. Cambia la rama destino (base) a dev."
    exit 1
fi

# El dueño del repositorio puede tocar todo (core, login, inicio, .github...).
if [ "$autor" = "$DUENO" ]; then
    echo "PR del dueño del repositorio: no se restringen carpetas."
    exit 0
fi

# La rama debe llamarse grupo-<modulo>
if [[ ! "$origen" =~ ^grupo-([a-z][a-z0-9_]*)$ ]]; then
    echo "::error::La rama debe llamarse grupo-<modulo> (ej.: grupo-garantia). La tuya es '$origen'."
    exit 1
fi
modulo="${BASH_REMATCH[1]}"

if [ "$modulo" = "inicio" ] || [ "$modulo" = "login" ]; then
    echo "::error::Los módulos inicio y login son comunes; los cambia solo el dueño del repositorio."
    exit 1
fi

errores=0
while IFS= read -r archivo; do
    case "$archivo" in
        "modulos/$modulo/"*) ;;
        "public/assets/css/$modulo.css") ;;
        "public/assets/js/$modulo.js") ;;
        "public/assets/img/$modulo/"*) ;;
        "database/motocons.sql") ;;
        *)
            echo "::error file=$archivo::No puedes cambiar este archivo desde la rama de '$modulo'. Solo tu módulo, tus assets y database/motocons.sql."
            errores=1
            ;;
    esac
done < <(git diff --name-only "origin/$destino...HEAD")

if [ "$errores" -ne 0 ]; then
    echo "Si necesitas un cambio fuera de tu módulo, pídeselo al dueño del repositorio."
    exit 1
fi
echo "OK: el PR solo toca lo del módulo '$modulo'."
