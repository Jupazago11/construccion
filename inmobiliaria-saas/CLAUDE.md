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

### Copiar la base de datos de Railway (preproducción) a local

Preproducción vive en el proyecto Railway **`zippy-magic`**, servicio **`Postgres`**, ambiente **`production`** (la app se llama `construccion`, dominio `construccion-production-faa3.up.railway.app`). El repo Git raíz es `Jupazago11/construccion` (un solo repo para todo, `inmobiliaria-saas` es subcarpeta).

**Setup único por máquina** (ya hecho en este equipo, no repetir salvo que se pierda la sesión):

```bash
# Instalar Railway CLI (queda en ~/.railway/bin/railway, no en PATH)
curl -fsSL https://railway.app/install.sh | sh

# Login (abre navegador con un código; browserless si no hay navegador a mano)
~/.railway/bin/railway login --browserless

# Vincular esta carpeta al proyecto/servicio de Railway (queda guardado, no hay que repetirlo)
cd /home/jupazago/Documentos/construccion/inmobiliaria-saas
~/.railway/bin/railway link -p zippy-magic --service Postgres --environment production
```

**Cada vez que se quiera refrescar la base local con datos de preproducción:**

```bash
cd /home/jupazago/Documentos/construccion/inmobiliaria-saas

# 1. Obtener la URL pública de Postgres (cambia si Railway rota la contraseña)
~/.railway/bin/railway variables --service Postgres --kv | grep DATABASE_PUBLIC_URL
# → postgresql://postgres:PASSWORD@HOST.proxy.rlwy.net:PUERTO/railway

# 2. Dump desde Railway usando el pg_dump del propio contenedor local (misma versión de Postgres)
docker exec inmobiliaria-saas-pgsql-1 pg_dump "postgresql://postgres:PASSWORD@HOST.proxy.rlwy.net:PUERTO/railway" \
  -Fc --no-owner --no-acl -f /tmp/railway_dump.dump

# 3. Restaurar sobre la base local (sail / laravel), reemplazando lo que haya
docker exec -e PGPASSWORD=password inmobiliaria-saas-pgsql-1 pg_restore \
  -U sail -d laravel --clean --if-exists --no-owner --no-acl /tmp/railway_dump.dump

# 4. Limpiar caché de Laravel (por si quedó config/cache vieja en sesión)
docker exec inmobiliaria-saas-laravel.test-1 php artisan optimize:clear
```

Notas:
- Esto **sobrescribe completamente** la base local (`--clean --if-exists`). Los usuarios de prueba (`superadmin`/`camilomorales` con clave `123456`) quedarán reemplazados por los usuarios reales de preproducción con sus contraseñas reales — hay que resetear su clave localmente si se necesita volver a entrar con la clave de prueba (ver sección "Usuarios de prueba" o usar `php artisan tinker` con `User::where('username', '...')->update(['password' => bcrypt('123456')])`).
- Los archivos (fotos/videos de activos, adjuntos) **no se copian**: la base referencia rutas en Cloudflare R2 (bucket de preproducción), no se descargan a disco local.
- Si `railway variables` no devuelve nada, correr `railway status` primero para confirmar que el link (`railway link`) sigue apuntando al proyecto/servicio correcto.
- `DATABASE_URL` (sin `_PUBLIC`) es el host interno de Railway (`postgres.railway.internal`) y **no** es alcanzable desde fuera de Railway — siempre usar `DATABASE_PUBLIC_URL` para esto.

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

### Módulo público: Vehículo (`/vehiculo`)

Módulo **totalmente aparte** del resto de la app: no comparte tenant, usuarios, ni datos con los módulos de empresas/proyectos/gastos/compras que usan los usuarios logueados. Es para **trabajadores externos que no tienen cuenta en el sistema** — por eso no hay login, ni multiempresa, ni policies. Solo reutiliza componentes visuales (Blade/Alpine/Tailwind) y el bundle de JS/Chart.js ya compilado; a nivel de datos y autorización es independiente. Registra gastos/compras de un solo vehículo implícito (no hay selector ni alta de vehículos).

- Rutas: `routes/web.php`, grupo `Route::prefix('vehiculo')->name('vehiculo.')` **fuera** del grupo `auth`+`active.user` (justo antes de `require __DIR__.'/auth.php';`).
- Controller: `app/Http/Controllers/VehicleRecordController.php` — `index` (con paginación AJAX igual al resto de módulos), `create`/`store`/`edit`/`update`/`destroy` (archivar, no borrado físico), `dashboard` (indicadores).
- Modelo: `app/Models/VehicleRecord.php` → tabla `vehicle_records`:
  - `record_date`: **automática**, se fija a `today()` solo al crear (`store()`); no es un campo del formulario y no se toca al editar.
  - `category`: `ingreso` | `gasto` (select fijo de 2 opciones).
  - `concept`: depende de `category`, lista fija en `VehicleRecord::CONCEPTS_BY_CATEGORY` — `ingreso` → `FLETE`; `gasto` → `MANTENIMIENTO`, `COMBUSTIBLE`, `UREA`, `VIATICOS`, `PEAJES`, `PARQUEADERO`, `SALARIO`. Si se necesita agregar/quitar conceptos, es el único lugar que hay que tocar (los Requests y la vista del modal lo leen de ahí).
  - `description`: libre, opcional.
  - `amount`: valor, obligatorio.
  - `status`: `EntityStatus` igual que el resto del sistema (archivar, no borrar).
- Requests: `VehicleRecordStoreRequest` / `VehicleRecordUpdateRequest`, ambos `authorize() => true` (público). Validan que `concept` pertenezca a la lista permitida para la `category` enviada (`Rule::in($allowedConcepts)` calculado dinámicamente en `rules()`).
- El select de "Concepto" en `_modal_form.blade.php` es dependiente del de "Categoría" vía Alpine inline (`x-data` local al formulario, no toca `app.js` global) — al cambiar categoría, si el concepto actual ya no aplica, se resetea al primero de la nueva lista. **Ojo**: `concepts` debe ser un array plano reasignado explícitamente en un método (`selectCategory(value)`), **no un `get concepts()` computado** — un getter ahí causó un bucle infinito de re-inicialización de Alpine (el formulario se veía "congelado" en la categoría por defecto). Los radios usan `x-model="category"` (no un `:checked` manual) porque solo `x-model` deja la propiedad `checked` nativa correcta para la validación `required` del navegador.
- Formato de moneda en vivo (puntos de miles mientras se escribe) en el campo "Valor": el form tiene `data-vehiculo-form`, inicializado por `window.initializeVehiculoForms` en `app.js` (mismo patrón que `initializeAssetForms`, reutiliza `syncFormattedMoneyInput`/`normalizeIntegerAmount`). Se registra en `openModal()`'s `$nextTick`, junto a los otros `window.initializeXForms?.()`.
- Vistas: `resources/views/vehiculo/` (`index`, `_table_body`, `_row`, `_modal_form`, `_summary`, `dashboard`).
- Filtros del listado (`index`): búsqueda, categoría y rango de fechas (`date_from`/`date_to`), dentro de una sección **colapsable** (`x-data="{ filtersOpen: ... }"`, mismo patrón que `reports/index.blade.php`) — abierta por defecto solo si ya hay algún filtro activo.
- Layout propio, sin navegación del sistema logueado ni dependencia de `auth()->user()` para funcionar: `resources/views/layouts/public.blade.php` + componente `app/View/Components/PublicLayout.php` (`<x-public-layout>`), para no reutilizar `layouts.app` (que asume usuario autenticado). Único punto de contacto con la sesión (opcional, ver más abajo): un `@auth` que muestra un botón "Volver al inicio" si el visitante sí tiene sesión iniciada.
- **Acceso desde el sistema logueado** (para que un usuario con cuenta no tenga que cerrar sesión para entrar a Vehículo): la tarjeta "Vehículo" en `resources/views/home/index.blade.php` y el link "Vehículo" en `resources/views/layouts/navigation.blade.php` (desktop y móvil) apuntan a `/vehiculo` sin ningún `@can` — es visible para **cualquier** usuario logueado, sin importar rol, porque el módulo no tiene policies propias que evaluar. Dentro de `layouts/public.blade.php`, el bloque `@auth` agrega el botón "Volver al inicio" apuntando a `route(auth()->user()->homeRouteName(), [], false)` (`dashboard` para SuperAdmin, `home` para el resto) — solo aparece si había sesión iniciada al entrar; para un visitante anónimo la página se ve exactamente igual que antes. Las rutas `/vehiculo` siguen **fuera** del middleware `auth`, así que un usuario sin cuenta sigue entrando igual sin loguearse.
- Dashboard de indicadores (`VehicleRecordController::dashboard()`):
  - **Selector de semana** (`?week=YYYY-MM-DD`, el lunes de esa semana): el `<select>` dispara `onchange="this.form.submit()"` (sin botón buscar). Las opciones son las semanas que **sí tienen registros** (`date_trunc('week', record_date)` en Postgres) más **siempre la semana actual**, aunque esté vacía — así el dashboard nunca se abre sin una semana seleccionada. Etiqueta: `"S{semana ISO} - {día} de {mes} a {día} de {mes}"` (ej. `"S27 - 29 de junio a 5 de julio"`), semana Lunes-Domingo.
  - Orden de la página: Semana seleccionada (arriba) → 3 gráficos mensuales (Ingresos, Gastos, Rentabilidad = ingresos − gastos) → torta Ingresos vs Gastos → **"Histórico"** (totales de todos los registros, al final de la página).
  - La torta y su tabla `conceptTotals` son **dinámicas**: se agrupan por el valor real de `category`/`concept` en la BD (`GROUP BY category` / `GROUP BY category, concept`), no están hardcodeadas a "ingreso"/"gasto" — si se agrega una categoría nueva directo en la BD, aparece sola en la torta. Las series mensuales (`monthlyIngresos`/`monthlyGastos`/`monthlyBalance`) sí siguen fijas a `ingreso`/`gasto` porque "rentabilidad" solo tiene sentido con esas dos.
  - Clic en un color de la torta (o en su leyenda) abre un modal (`#concept-modal`, JS plano sin Alpine) con una segunda torta del desglose por **concepto** dentro de esa categoría, usando `conceptTotals[categoryKey]` ya embebido en la página (sin request nuevo).
- Usa Chart.js (ya cargado globalmente) con la misma paleta pastel de `reports/index.blade.php`: `['#93c5e8', '#b8a9dc', '#f4a7bb', '#f8c4a0', '#f5dc9a', '#a8d5b5', '#99d4d0', '#a0b4e8']`.
- **No tiene** rate limiting, CAPTCHA ni ninguna protección anti-abuso — quedó así a propósito ("sin restricción por ahora"). Si se expone públicamente en internet, revisar esto antes.
- Reutiliza el componente Alpine `crudTable` global de `app.js` (mismo patrón `data-action`/`data-url` que `assets2`), por eso no hace falta JS adicional propio salvo `initializeVehiculoForms`.
- **Cuidado al probar este módulo**: la tabla `vehicle_records` puede contener datos reales cargados a propósito (no solo datos de prueba). **Nunca correr `VehicleRecord::truncate()` u operaciones destructivas sin confirmar antes con el usuario** — ya pasó una vez que se perdieron registros reales por asumir que la tabla solo tenía datos de prueba de una sesión anterior.
