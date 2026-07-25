#!/bin/bash

clear

echo "==============================================="
echo " Sistema de Gestión Hospitalaria"
echo " Configuración de Git y GitHub"
echo "==============================================="

echo ""
echo "Actualizando Ubuntu..."
sudo apt update

echo ""
echo "Instalando Git..."
sudo apt install git -y

echo ""
echo "Instalando Tree..."
sudo apt install tree -y

echo ""
git --version

echo ""
echo "Configurando Git..."

git config --global user.name "pcruz2891"
git config --global user.email "pcruzr2891@gmail.com"

echo ""
echo "Configuración realizada."

echo ""
echo "Generando clave SSH..."

if [ ! -f ~/.ssh/id_ed25519 ]; then
    ssh-keygen -t ed25519 -C "pcruzr2891@gmail.com"
fi

echo ""
echo "Iniciando agente SSH..."

eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519

echo ""
echo "==============================================="
echo "COPIA ESTA CLAVE EN GITHUB"
echo "==============================================="

cat ~/.ssh/id_ed25519.pub

echo ""
echo "GitHub -> Settings -> SSH and GPG Keys"
echo "New SSH Key"
echo ""

read -p "Presiona ENTER cuando hayas agregado la clave..."

echo ""
ssh -T git@github.com

echo ""
echo "Inicializando Git..."

git init

echo "# Sistema de Gestión Hospitalaria" > README.md

touch .gitignore

cat <<EOF > .gitignore
target/
bin/
.vscode/
.env
*.log
EOF

echo ""
echo "Creando archivos .gitkeep..."

touch microservices/ms-consulta/.gitkeep
touch microservices/ms-ehr/.gitkeep
touch microservices/ms-turnos/.gitkeep
touch microservices/ms-farmacia/.gitkeep

touch config/kafka/.gitkeep
touch config/kong/.gitkeep
touch config/postgresql/.gitkeep
touch config/mongodb/.gitkeep
touch config/redis/.gitkeep
touch config/zookeeper/.gitkeep

touch docker/.gitkeep

echo ""
git add .

git commit -m "Initial project structure"

echo ""
echo "==============================================="
echo "CREA EL REPOSITORIO EN GITHUB"
echo ""
echo "Nombre:"
echo "SistemaGestionHospitalaria"
echo ""
echo "NO agregues:"
echo "README"
echo ".gitignore"
echo "License"
echo "==============================================="

read -p "Presiona ENTER cuando el repositorio esté creado..."

git remote remove origin 2>/dev/null

git remote add origin git@github.com:pcruz2891/SistemaGestionHospitalaria.git

git branch -M main

git push -u origin main

echo ""
echo "Creando rama develop..."

git checkout -b develop

git push -u origin develop

echo ""
echo "Creando ramas feature..."

git checkout develop
git checkout -b feature/ms-consulta
git push -u origin feature/ms-consulta

git checkout develop
git checkout -b feature/ms-ehr
git push -u origin feature/ms-ehr

git checkout develop
git checkout -b feature/ms-turnos
git push -u origin feature/ms-turnos

git checkout develop
git checkout -b feature/ms-farmacia
git push -u origin feature/ms-farmacia

git checkout develop

echo ""
echo "==============================================="
echo "Proceso finalizado correctamente."
echo "==============================================="

git branch -a