# 🚀 Guía para Subir el Proyecto a Git

Esta guía te ayudará a subir tu proyecto **Inventario Inteligente** a GitHub, GitLab o cualquier otro repositorio Git.

## 📋 Pasos para Subir a Git

### 1. Inicializar el Repositorio Git

```bash
# Inicializar Git en el proyecto
git init

# Agregar todos los archivos (excepto los que están en .gitignore)
git add .

# Hacer el primer commit
git commit -m "Initial commit: Sistema de Inventario Inteligente"
```

### 2. Crear un Repositorio en GitHub/GitLab

1. Ve a [GitHub](https://github.com) o [GitLab](https://gitlab.com)
2. Crea una nueva cuenta si no tienes una
3. Haz clic en "New Repository" o "Nuevo Repositorio"
4. Completa los datos:
   - **Nombre**: `InventarioInteligente` (o el que prefieras)
   - **Descripción**: "Sistema completo de gestión de inventario con Laravel y React"
   - **Visibilidad**: Público o Privado (según prefieras)
   - **NO marques** "Initialize with README" (ya tenemos uno)
5. Haz clic en "Create repository"

### 3. Conectar el Repositorio Local con el Remoto

Después de crear el repositorio, GitHub/GitLab te dará una URL. Úsala en estos comandos:

**Para HTTPS:**
```bash
git remote add origin https://github.com/TU_USUARIO/InventarioInteligente.git
```

**Para SSH:**
```bash
git remote add origin git@github.com:TU_USUARIO/InventarioInteligente.git
```

### 4. Subir el Código

```bash
# Verificar que el remote esté configurado
git remote -v

# Subir el código a la rama main/master
git branch -M main
git push -u origin main
```

Si es la primera vez que usas Git, puede que necesites configurar tu identidad:

```bash
git config --global user.name "Tu Nombre"
git config --global user.email "tu.email@ejemplo.com"
```

## 🔐 Autenticación

### GitHub (HTTPS)
Si usas HTTPS, GitHub te pedirá autenticación. Puedes usar:
- **Personal Access Token** (recomendado)
- **GitHub CLI**

Para crear un Personal Access Token:
1. Ve a GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Genera un nuevo token con permisos `repo`
3. Úsalo como contraseña cuando Git te lo pida

### GitHub (SSH)
Si prefieres usar SSH:
1. Genera una clave SSH: `ssh-keygen -t ed25519 -C "tu.email@ejemplo.com"`
2. Agrega la clave a tu agente SSH: `ssh-add ~/.ssh/id_ed25519`
3. Copia la clave pública: `cat ~/.ssh/id_ed25519.pub`
4. Agrega la clave en GitHub → Settings → SSH and GPG keys

## 📝 Comandos Útiles

### Ver el estado del repositorio
```bash
git status
```

### Agregar cambios
```bash
# Agregar todos los cambios
git add .

# Agregar archivos específicos
git add archivo1.js archivo2.php
```

### Hacer commit
```bash
git commit -m "Descripción de los cambios"
```

### Subir cambios
```bash
git push
```

### Ver el historial
```bash
git log --oneline
```

### Crear una nueva rama
```bash
git checkout -b nombre-de-la-rama
```

## ⚠️ Archivos que NO se Suben

El archivo `.gitignore` está configurado para NO subir:
- ✅ Archivos `.env` (con contraseñas y configuraciones sensibles)
- ✅ `node_modules/` (dependencias de Node.js)
- ✅ `vendor/` (dependencias de PHP)
- ✅ Archivos de build
- ✅ Logs
- ✅ Archivos temporales

## 🔄 Actualizar el Repositorio

Cada vez que hagas cambios:

```bash
# Ver qué archivos cambiaron
git status

# Agregar los cambios
git add .

# Hacer commit
git commit -m "Descripción de los cambios realizados"

# Subir los cambios
git push
```

## 📦 Estructura Recomendada de Commits

Usa mensajes descriptivos:

```bash
git commit -m "feat: Agregar gráficos al dashboard"
git commit -m "fix: Corregir error en cálculo de ventas"
git commit -m "docs: Actualizar README con nuevas características"
git commit -m "refactor: Mejorar estructura de componentes React"
```

## 🆘 Solución de Problemas

### Error: "remote origin already exists"
```bash
git remote remove origin
git remote add origin TU_URL
```

### Error: "failed to push some refs"
```bash
# Primero hacer pull de los cambios remotos
git pull origin main --rebase
# Luego hacer push
git push
```

### Olvidaste agregar algo al commit anterior
```bash
git add archivo-olvidado.js
git commit --amend --no-edit
git push --force
```

## 📚 Recursos Adicionales

- [Documentación de Git](https://git-scm.com/doc)
- [Guía de GitHub](https://guides.github.com/)
- [Git Cheat Sheet](https://education.github.com/git-cheat-sheet-education.pdf)

---

**¡Listo!** Tu proyecto ahora está en Git y puedes compartirlo con otros desarrolladores. 🎉
