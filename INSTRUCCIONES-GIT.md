# 📤 Instrucciones para Subir el Proyecto a Git

## 🚀 Opción 1: Usar el Script Automático (Recomendado)

### Windows:
```bash
subir-git.bat
```

### Linux/Mac:
```bash
chmod +x subir-git.sh
./subir-git.sh
```

## 📝 Opción 2: Pasos Manuales

### Paso 1: Configurar Git (Solo la primera vez)

```bash
git config --global user.name "Tu Nombre"
git config --global user.email "tu.email@ejemplo.com"
```

### Paso 2: Crear Repositorio en GitHub/GitLab

1. Ve a [GitHub](https://github.com) o [GitLab](https://gitlab.com)
2. Crea una cuenta si no tienes una
3. Haz clic en "New Repository"
4. Nombre: `InventarioInteligente`
5. Descripción: "Sistema de gestión de inventario con Laravel y React"
6. **NO marques** "Initialize with README"
7. Haz clic en "Create repository"

### Paso 3: Conectar con el Repositorio Remoto

Copia la URL que te da GitHub/GitLab y ejecuta:

```bash
git remote add origin https://github.com/TU_USUARIO/InventarioInteligente.git
```

O si prefieres SSH:
```bash
git remote add origin git@github.com:TU_USUARIO/InventarioInteligente.git
```

### Paso 4: Agregar Archivos y Hacer Commit

```bash
# Agregar todos los archivos
git add .

# Hacer commit
git commit -m "Initial commit: Sistema de Inventario Inteligente"
```

### Paso 5: Subir al Repositorio

```bash
# Subir a la rama main
git branch -M main
git push -u origin main
```

Si GitHub usa "master" en lugar de "main":
```bash
git push -u origin master
```

## 🔐 Autenticación con GitHub

### Opción A: Personal Access Token (HTTPS)

1. Ve a GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Genera nuevo token con permisos `repo`
3. Cuando Git pida contraseña, usa el token

### Opción B: SSH (Recomendado)

1. Genera clave SSH:
```bash
ssh-keygen -t ed25519 -C "tu.email@ejemplo.com"
```

2. Agrega la clave al agente:
```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
```

3. Copia la clave pública:
```bash
cat ~/.ssh/id_ed25519.pub
```

4. Agrega la clave en GitHub → Settings → SSH and GPG keys → New SSH key

## ✅ Verificar que Todo Esté Bien

```bash
# Ver el estado
git status

# Ver el historial
git log --oneline

# Ver el remoto configurado
git remote -v
```

## 🔄 Actualizar el Repositorio (Después del Primer Push)

Cada vez que hagas cambios:

```bash
git add .
git commit -m "Descripción de los cambios"
git push
```

## ⚠️ Archivos que NO se Suben

Gracias al `.gitignore`, estos archivos NO se subirán:
- ✅ `.env` (archivos de configuración con contraseñas)
- ✅ `node_modules/` (dependencias de Node.js)
- ✅ `vendor/` (dependencias de PHP)
- ✅ Archivos de build y logs

## 🆘 Problemas Comunes

### Error: "remote origin already exists"
```bash
git remote remove origin
git remote add origin TU_URL
```

### Error: "failed to push some refs"
```bash
git pull origin main --rebase
git push
```

### Olvidaste agregar algo
```bash
git add archivo-olvidado.js
git commit --amend --no-edit
git push --force
```

## 📚 Más Información

Consulta `GIT-SETUP.md` para una guía más detallada.

---

**¡Listo!** Tu proyecto ahora está en Git. 🎉
