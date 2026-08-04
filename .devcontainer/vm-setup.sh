#!/usr/bin/env bash
set -e

cd /workspace

echo "Instalando dependencias del SDK de Transbank..."
chmod +x config.sh package.sh
./config.sh

cat <<'EOF'

========================================

  Entorno de desarrollo listo.

  Web server: http://localhost:8081/
  Admin:      http://localhost:8081/administrator
    user: admin
    password: password

  La primera vez, instala VirtueMart con datos de prueba
  desde el sitio administrador (ver .devcontainer/README.md).

  Para generar el paquete instalable del plugin:
    ./package.sh

  Luego instala el .zip generado desde:
    Admin > Extensions > Manage > Install

========================================
EOF
