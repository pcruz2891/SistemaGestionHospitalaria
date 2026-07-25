#!/bin/bash

clear

echo "==============================================="
echo " Sistema de Gestión Hospitalaria"
echo " Instalación de Visual Studio Code"
echo "==============================================="

echo ""
echo "Actualizando repositorios..."
sudo apt update

echo ""
echo "Instalando dependencias..."
sudo apt install wget gpg apt-transport-https software-properties-common curl -y

echo ""
echo "Importando la clave GPG de Microsoft..."

wget -qO- https://packages.microsoft.com/keys/microsoft.asc | \
gpg --dearmor | \
sudo tee /usr/share/keyrings/packages.microsoft.gpg >/dev/null

echo ""
echo "Agregando el repositorio oficial de Visual Studio Code..."

echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/packages.microsoft.gpg] https://packages.microsoft.com/repos/code stable main" | \
sudo tee /etc/apt/sources.list.d/vscode.list >/dev/null

echo ""
echo "Actualizando repositorios..."

sudo apt update

echo ""
echo "Instalando Visual Studio Code..."

sudo apt install code -y

echo ""
echo "==============================================="
echo "Versión instalada"
echo "==============================================="

code --version

echo ""
echo "==============================================="
echo "Instalación finalizada correctamente"
echo "==============================================="