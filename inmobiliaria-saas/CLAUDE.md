# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Idioma

Responde siempre en español. Cuando expliques cambios de código, errores o soluciones, usa español claro y directo. Si necesitas mostrar código, comentarios o nombres técnicos, puedes mantenerlos en inglés cuando sea lo normal en programación, pero la explicación debe estar en español. Antes de modificar archivos, explica brevemente qué vas a cambiar. No respondas en inglés salvo que el usuario lo pida explícitamente.

---

## Comandos del proyecto

El proyecto corre en **Laravel Sail (Docker)** desde **WSL Ubuntu** en Windows. Todos los comandos se ejecutan desde PowerShell apuntando a WSL.

### Levantar servicios (arranque diario)

```powershell
# 1. Abrir Docker Desktop y esperar que esté corriendo

# 2. Levantar contenedores
wsl -d Ubuntu -- bash -c "cd /home/jupazago/Documentos/construccion/inmobiliaria-saas && ./vendor/bin/sail up -d"

# 3. Limpiar hot y arrancar Vite dentro del contenedor
wsl -d Ubuntu -- bash -c "cd /home/jupazago/Documentos/construccion/inmobiliaria-saas && rm -f public/hot"
wsl -d Ubuntu -- bash -c "docker exec -d inmobiliaria-saas-laravel.test-1 bash -c 'cd /var/www/html && npm run dev > /tmp/vite.log 2>&1'"

# 4. Verificar que Vite levantó (debe mostrar "VITE vX ready")
wsl -d Ubuntu -- bash -c "docker exec inmobiliaria-saas-laravel.test-1 cat /tmp/vite.log"
```

La app queda disponible en: **http://localhost:8081/login**

### Comandos artisan frecuentes

```powershell
# Migraciones pendientes
wsl -d Ubuntu -- docker exec inmobiliaria-saas-laravel.test-1 php artisan migrate --force

# Limpiar caché
wsl -d Ubuntu -- docker exec inmobiliaria-saas-laravel.test-1 php artisan optimize:clear

# Tinker (REPL)
wsl -d Ubuntu -- docker exec -it inmobiliaria-saas-laravel.test-1 php artisan tinker

# Tests
wsl -d Ubuntu -- bash -c "cd /home/jupazago/Documentos/construccion/inmobiliaria-saas && ./vendor/bin/sail artisan test"

# Un solo test
wsl -d Ubuntu -- bash -c "cd /home/jupazago/Documentos/construccion/inmobiliaria-saas && ./vendor/bin/sail artisan test --filter NombreDelTest"
```

### Compilar assets para producción / celular

```powershell
wsl -d Ubuntu -- bash -c "cd /home/jupazago/Documentos/construccion/inmobiliaria-saas && ./vendor/bin/sail npm run build"
```

### Usuarios de prueba

| Rol | Usuario | Clave |
|---|---|---|
| SuperAdmin | superadmin | 123456 |
| CompanyAdmin | camilomorales | 123456 |

---

## Arquitectura

### Stack

- **Laravel 13** (PHP), **PostgreSQL**, **Redis**, **Laravel Sail** (Docker)
- **Alpine.js** para interactividad en frontend
- **Tailwind CSS** (v3) para estilos, compilado con **Vite**
- **Spatie Laravel Permission** para roles y permisos
- **Spatie Laravel Activitylog** para auditoría

### Multi-tenancy

Toda la data está aislada por `company_id`. Las queries siempre filtran por `company_id` del usuario autenticado. El SuperAdmin puede ver y gestionar todas las empresas. Los demás roles solo ven su propia empresa.

### Roles (`App\Enums\SystemRole`)

`SuperAdmin` → `CompanyAdmin` → `Operator` → `Viewer` → `BuyerUser`

- `user->isSuperAdmin()`: acceso global a todas las empresas
- `user->canAccessCompany($id)`: verifica si el usuario puede operar sobre una empresa
- Los permisos granulares (e.g. `assets.view`, `assets.manage`, `audit.revert`) se usan en Policies

### Estado de entidades (`App\Enums\EntityStatus`)

No hay `SoftDeletes` de Eloquent. El estado se guarda en la columna `status` con tres valores: `active`, `inactive`, `deleted`. Archivar/eliminar un registro equivale a poner `status = 'deleted'`. Las queries excluyen registros deleted con `where('status', '!=', EntityStatus::Deleted->value)`.

### Sistema de modales AJAX (`crudTable` en app.js)

La UI usa un único componente Alpine.js `crudTable` que maneja todos los modales. El flujo es:

1. Botones en la tabla tienen `data-action="create|edit|delete"` y `data-url`
2. Al hacer clic, `openModal(url, title)` hace GET AJAX al servidor
3. El servidor retorna HTML renderizado de la vista `_modal_form.blade.php`
4. El HTML se inyecta en `x-ref="modalContent"` y se inicializa con `Alpine.initTree()`
5. Al guardar, `submitForm()` hace POST/PATCH AJAX y el servidor responde JSON con `row_html` y/o `summary_html` para actualizar la tabla sin recargar

El modal tiene dos niveles: modal principal (`modalOpen`) y modal anidado (`nestedModalOpen`, z-index mayor). Se usa para submodales tipo gestión de tipos dentro del formulario principal.

### Sistema de borradores (drafts)

Cuando se cierra un modal de **creación** sin guardar, los valores del formulario se guardan en `localStorage` con clave `form-draft:<url-sin-query>`. Al volver a abrir ese mismo modal, se restauran automáticamente con un toast. Los modales de **edición** (PATCH/PUT) no restauran borrador.

### Auditoría

El trait `LogsAuditActivity` (en `App\Traits`) envuelve `spatie/laravel-activitylog`. Todos los modelos principales lo usan. Los logs tienen `company_id`, `project_id` y `expires_at`. Para excluir campos del log se define `protected array $auditExcept` en el modelo.

### Convención de respuestas del controlador

Los controladores responden diferente según si la petición es AJAX:
- `$request->ajax()` → retorna `view(...)->render()` (string HTML) para cargar en el modal
- `$request->expectsJson()` → retorna `JsonResponse` con `row_html`, `summary_html`, `message`, y opcionalmente `modal_html`
- Sin AJAX → `redirect()->route(...)`

### Assets (Activos)

Hay dos módulos paralelos de activos: **Asset** (`assets`, tabla `assets`) y **Asset2** (`assets2`, tabla `assets2`). Tienen la misma estructura funcional: tipos propios por empresa, novedades, media (fotos/videos). Los tipos de novedad (`asset_novelty_types`) son **compartidos** entre Asset y Asset2 dentro de la misma empresa.

### Estructura de vistas

Las vistas de lista siguen el patrón:
- `index.blade.php` — página completa con tabla y resumen
- `_row.blade.php` — fila de tabla, incluida vía AJAX tras mutaciones
- `_summary.blade.php` — tarjetas de totales, incluida vía AJAX tras mutaciones
- `_modal_form.blade.php` — formulario dentro del modal
- `_novelty_modal_form.blade.php` — formulario de novedad dentro del modal

### Almacenamiento de archivos

Media (fotos/videos) se sube a **Cloudflare R2** con fallback a disco `public`. La ruta de storage es `companies/{company_id}/assets/{asset_id}/media` (o `assets2`). Las previews se sirven via el controlador (no URL directa) para mantener control de acceso.

### Iconos

Se usan **Heroicons v1 solid** de 20×20 px (`viewBox="0 0 20 20"`). Los botones de acción en tablas usan clases `p-2.5` con iconos `h-5 w-5`.
