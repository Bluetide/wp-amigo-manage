# WP Amigo Manage

Auditor de inventario y vulnerabilidades para el core, plugins y temas de WordPress, desarrollado por **BlueTide** como parte de la suite **WP Amigo**.

---

**WP Amigo Manage** automatiza auditorías directamente desde cada sitio WordPress, y centraliza los resultados hacia un webhook, para:

- Detectar proactivamente qué sitios tienen componentes vulnerables, sin visitarlos uno por uno.
- Saber si existe un parche disponible antes de escalar la alerta al cliente.
- Priorizar por severidad (CVSS) en vez de revisar vulnerabilidades sin orden.

## ¿Qué hace?

Por cada sitio donde está instalado, el plugin:

1. **Audita el core de WordPress** — versión instalada vs. última disponible, y vulnerabilidades conocidas para esa versión de core.
2. **Audita todos los plugins instalados** — activos o no, comparando la versión instalada contra los rangos de versiones afectadas reportados por la API de vulnerabilidades.
3. **Audita todos los temas instalados** — mismo criterio que plugins, marcando cuál es el tema activo del sitio.
4. Para cada componente, determina:
   - Si está en su última versión disponible (`maybe_latest`).
   - Si existe parche para las vulnerabilidades encontradas (`maybe_patch`).
   - La lista de vulnerabilidades aplicables, **ordenada por severidad (CVSS) descendente**, con la fuente más reciente de cada una.
5. Arma un **reporte consolidado** (core + plugins + temas) y lo envía a un webhook para su procesamiento centralizado.
6. Expone un comando de **WP-CLI** (`wp amigo audit`) para ejecutar la auditoría manualmente, con la opción de solo generar un log local o también enviarlo al webhook.
7. Corre automáticamente en un **cron programado** (WP-Cron) sin intervención manual.

## ¿Cómo lo hace?

El flujo de una auditoría completa:

```
Plugin::generate_audit()
        │
        ├── AmigoCoreAuditor   ─┐
        ├── AmigoPluginAuditor ─┼─→ WpVulnerabilityProvider ─→ HttpFetcher ─→ wpvulnerability.net
        └── AmigoThemeAuditor  ─┘         │
                                          ├─ VersionMatcher::filter()      (filtra vulnerabilidades aplicables a la versión instalada)
                                          └─ VulnerabilityFormatter::format() (arma DTOs, ordena por CVSS, resuelve parche)
        │
        ▼
   AmigoReport (DTO)
        │
        ├── Command (WP-CLI)  → log local en /logs, opcionalmente reenvía
        └── Dispatcher        → POST al webhook de n8n
```

**Arquitectura:**

- **Separación estricta entre generación y transporte del error**: el `HttpFetcher` solo lanza excepción cuando falla la comunicación (`is_wp_error`); quien llama decide qué status HTTP amerita qué tipo de excepción. Ningún nivel "traga" un error sin que quien esté un nivel arriba se entere — las excepciones se loguean una sola vez, en el origen, y burbujean hasta el punto de entrada que decide el fallback.
- **Cada auditor aísla sus propios fallos**: si la consulta de un plugin específico falla (timeout, 5xx), el resto de la auditoría continúa — el fallo queda registrado en el campo `error` de esa entrada puntual, sin abortar el proceso completo.
- **DTOs tipados en vez de arrays sueltos** para representar vulnerabilidades, fuentes e impacto — con constructores nombrados (`from_array`) que mapean la respuesta cruda del API a objetos con propiedades `readonly`.
- **Filtrado de versiones vía `version_compare()`** nativo de PHP: los operadores que expone la API (`lt`, `le`, `eq`, `ne`, `gt`, `ge`) son literalmente los strings de operador que PHP acepta, sin necesidad de mapeo.
- **Selección de CVSS por prioridad de tier**: `cvss4` → `cvss3` → `cvss2` → `cvss` (legacy), tomando el primero disponible con score distinto de `0.0`.

## ¿Con qué lo hace?

| Componente                          | Herramienta / Tecnología                                                          |
| ----------------------------------- | --------------------------------------------------------------------------------- |
| Lenguaje                            | PHP 8.0+, `declare(strict_types=1)` en todo el código propio                      |
| Autoload                            | Composer, PSR-4                                                                   |
| Plataforma                          | Plugin de WordPress (6.0+)                                                        |
| Fuente de datos de vulnerabilidades | API pública de [wpvulnerability.net](https://www.wpvulnerability.net)             |
| Programación de tareas              | WP-Cron (vía `Scheduler`)                                                         |
| Interfaz de línea de comandos       | WP-CLI (`wp amigo audit [--send]`)                                                |
| Transporte del reporte              | Webhook HTTP hacia n8n (`n8n.cloud`)                                              |
| Análisis estático                   | PHPStan (nivel 5) + `szepeviktor/phpstan-wordpress` + `php-stubs/wordpress-stubs` |
| Comparación de versiones            | `version_compare()` nativo de PHP                                                 |

## Estructura del proyecto

```
wp-amigo-manage/
├── wp-amigo-manage.php          # Bootstrap del plugin (header de WP, defines, hooks de activación)
├── composer.json                 # Autoload PSR-4 y dependencias de desarrollo
├── phpstan.neon                  # Configuración de análisis estático
│
├── core/                         # Orquestación del plugin
│   ├── Plugin.php                 # Singleton — punto de entrada, arma el reporte y lo despacha
│   ├── Command.php                 # Comando WP-CLI (`wp amigo audit`)
│   ├── Scheduler.php               # Registro/limpieza del cron de WP
│   ├── Settings.php                 # Configuración del plugin (opciones admin)
│   ├── Dispatcher.php               # Envío del reporte al webhook de n8n
│   └── Logger.php                   # Logging centralizado de errores
│
├── http/                         # Capa de transporte HTTP, agnóstica de dominio
│   ├── HttpFetcher.php              # Wrapper sobre wp_remote_request()
│   └── HttpResponse.php             # Value object de respuesta (status, body crudo, headers)
│
├── exceptions/                   # Jerarquía de excepciones de dominio
│   ├── WpAmigoException.php         # Base abstracta (status, reason, details)
│   ├── NotFoundException.php
│   ├── RequestTimeoutException.php
│   └── BadGatewayException.php
│
├── providers/                    # Integración con APIs externas
│   └── WpVulnerabilityProvider.php  # Cliente de wpvulnerability.net (core/plugin/theme)
│
├── helpers/                      # Lógica de dominio sin estado
│   ├── VersionMatcher.php           # Filtra vulnerabilidades por versión instalada
│   └── VulnerabilityFormatter.php   # Ordena por CVSS y resuelve disponibilidad de parche
│
├── dto/                          # Objetos de transferencia de datos (readonly)
│   ├── AmigoVulnerabilitySource.php
│   ├── AmigoVulnerabilityImpact.php
│   ├── AmigoVulnerability.php
│   └── AmigoReport.php
│
├── includes/                     # Auditores por tipo de componente
│   ├── AmigoBaseAuditor.php         # Contrato abstracto + lógica compartida (safe_lookup, transients)
│   ├── AmigoCoreAuditor.php
│   ├── AmigoPluginAuditor.php
│   └── AmigoThemeAuditor.php
│
├── logs/                         # Logs locales generados por WP-CLI (protegidos vía .htaccess)
└── vendor/                       # Dependencias de Composer (autoload, stubs de análisis estático)
```

### Convenciones del proyecto

- **Nombres de método**: los métodos de clases de excepción usan `camelCase` (`getReason()`, `getStatus()`); todo el resto del código usa `snake_case` (`extract_slug()`, `from_array()`, `has_patch()`).
- **Inmutabilidad**: DTOs y excepciones usan propiedades `readonly`, construidas vía constructor o named constructors (`from_array`).

