#!/usr/bin/env bash
# Abre el inicio, prueba el login y entra a la página de cada módulo.
# Falla si alguna da error 500 o muestra un error de PHP.
# Necesita el servidor corriendo en $URL con display_errors activo.
set -uo pipefail

URL="${URL:-http://localhost:8000}"
errores=0

# Revisa una página: código HTTP y errores de PHP en el HTML.
revisar() {
    local nombre="$1" ruta="$2"
    local cuerpo codigo
    cuerpo=$(curl -s -b cookies.txt -w '\n%{http_code}' "$URL/index.php?r=$ruta")
    codigo="${cuerpo##*$'\n'}"
    if [ "$codigo" -ge 500 ] || grep -qE '(Fatal error|Parse error|Warning|Notice|Deprecated)</b>:' <<< "$cuerpo"; then
        echo "::error::$nombre ($ruta) falla con código $codigo:"
        grep -E '(Fatal error|Parse error|Warning|Notice|Deprecated)</b>:' <<< "$cuerpo" | sed 's/<[^>]*>//g' | head -5
        errores=1
    else
        echo "OK $codigo  $nombre ($ruta)"
    fi
}

# Inicio y login sin sesión
revisar "Inicio" "inicio/index"
revisar "Login" "login/index"
revisar "Registro" "login/registro"

# Entrar con el usuario de prueba
curl -s -c cookies.txt -o /dev/null "$URL/index.php?r=login/index"
destino=$(curl -s -b cookies.txt -c cookies.txt -o /dev/null -w '%{redirect_url}' \
    -d 'correo=admin@motocons.com&clave=admin123' "$URL/index.php?r=login/entrar")
if [[ "$destino" != *"r=inicio/index"* ]]; then
    echo "::error::El login con admin@motocons.com / admin123 no funciona (redirige a '$destino')"
    errores=1
fi

# Cada módulo del menú, ya con sesión
revisar "Inicio con sesión" "inicio/index"
for archivo in modulos/*/modulo.php; do
    carpeta=$(basename "$(dirname "$archivo")")
    [[ "$carpeta" == _* ]] && continue
    ruta=$(php -r '$d = require $argv[1]; echo is_array($d) ? ($d["ruta"] ?? "") : "";' "$archivo")
    [ -n "$ruta" ] && revisar "Módulo $carpeta" "$ruta"
done

rm -f cookies.txt
exit "$errores"
