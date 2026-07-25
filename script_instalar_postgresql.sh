#!/bin/bash

echo "==============================================="
echo " Instalación de PostgreSQL 17"
echo " Sistema de Gestión Hospitalaria"
echo "==============================================="

# Actualizar repositorios
sudo apt update
sudo apt upgrade -y

# Instalar PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Habilitar e iniciar servicio
sudo systemctl enable postgresql
sudo systemctl start postgresql

echo ""
echo "Versión instalada:"
psql --version

echo ""
echo "Estado del servicio:"
sudo systemctl status postgresql --no-pager

echo ""
echo "==============================================="
echo "Creando usuario y base de datos..."
echo "==============================================="

sudo -u postgres psql <<EOF

CREATE USER hospital_admin WITH PASSWORD 'Hospital2026';

CREATE DATABASE sigh_hospital
OWNER hospital_admin;

GRANT ALL PRIVILEGES ON DATABASE sigh_hospital TO hospital_admin;

EOF

echo ""
echo "==============================================="
echo "Verificando Base de Datos"
echo "==============================================="

sudo -u postgres psql -c "\l"

echo ""
echo "==============================================="
echo "Usuarios registrados"
echo "==============================================="

sudo -u postgres psql -c "\du"

echo ""
echo "==============================================="
echo "Instalación finalizada correctamente."
echo ""
echo "Base de datos : sigh_hospital"
echo "Usuario       : hospital_admin"
echo "Contraseña    : Hospital2026"
echo ""
echo "Para ejecutar el script SQL:"
echo ""
echo "psql -U hospital_admin -d sigh_hospital -f ScriptBD.sql"
echo ""
echo "==============================================="