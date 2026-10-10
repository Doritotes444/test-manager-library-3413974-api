# Manager Library - Backend API (Laravel 11 / PHP 8.2)

> **Arquitectura Hexagonal (Puertos y Adaptadores)**  
> **Servicio Nacional de Aprendizaje (SENA) - Ficha ADSO 3413974**  
> **Desarrollador:** Juan Pablo Doria ([`@Doritotes444`](https://github.com/Doritotes444))  

---

## 🏛️ Organización Arquitectónica Hexagonal

Conforme a la especificación oficial del proyecto (`architecture.md` y `constitution.md`), este backend implementa rigurosamente el patrón de **Arquitectura Hexagonal (Puertos y Adaptadores)**:

```
                      [ Adaptador de Entrada (HTTP) ]
                       adapters/inbound/http/
                        (Controllers, Middleware)
                                    │
                                    ▼
       ┌────────────────────────────────────────────────────────┐
       │                   NÚCLEO DE LA APLICACIÓN               │
       │                                                        │
       │   application/services/                                │
       │   (LendBooksService, ReturnItemService, etc.)          │
       │                            │                           │
       │                            ▼                           │
       │   domain/                                              │
       │   (Book, Loan, Member, User; Isbn, Quantity, etc.)    │
       │                                                        │
       │   application/ports/outbound/                          │
       │   (BookRepo, LoanRepo, MemberRepo, Clock, Security)    │
       └────────────────────────────┬───────────────────────────┘
                                    │ implementan puertos
                                    ▼
                     [ Adaptadores de Salida ]
                      adapters/outbound/
                       ├── persistence/  (Eloquent Repositories, Models, Mappers)
                       ├── security/     (Argon2id Hasher, JWT Issuer)
                       ├── storage/      (Local Cover Storage)
                       └── clock/        (SystemClock)

                      [ Punto Único de Composición ]
                       bootstrap/composition/
                        PortBindings.php (Service Provider)
```

### Cumplimiento Constitucional:
* **Artículo I:** `app/Domain/` tiene **0 dependencias externas**. Solo tipos puros de PHP (`grep -rn "Illuminate" app/Domain` = 0).
* **Artículo II:** `app/Application/` no importa adaptadores ni frameworks. Declara sus puertos en `Ports/Outbound/`.
* **Artículo III:** `app/Bootstrap/Composition/PortBindings.php` es el único punto de composición donde los puertos se encuentran con sus adaptadores.

---

## 📋 Endpoints de la API (`/api/v1`)

| Método | Endpoint | Rol Mínimo | Descripción |
|---|---|---|---|
| `GET` | `/health` | Público | Verificación de estado del servicio y base de datos |
| `POST` | `/auth/login` | Público | Autenticación con email/password y generación de JWT |
| `POST` | `/users` | `admin` | Creación de nuevos usuarios bibliotecarios |
| `GET` | `/categories` | Autenticado | Catálogo de 5 categorías del sistema |
| `GET` | `/books` | Autenticado | Listado de libros con filtros y cálculo de `available_copies` |
| `POST` | `/books` | `admin` | Registro de libros con subida de carátula |
| `GET` | `/books/{id}` | Autenticado | Detalle del libro |
| `PUT` | `/books/{id}` | `admin` | Edición de título, autor, stock y carátula |
| `DELETE` | `/books/{id}` | `admin` | Baja lógica (Soft delete); bloqueada si hay préstamos activos (409) |
| `GET` | `/members` | Autenticado | Listado y búsqueda de socios de la biblioteca |
| `POST` | `/members` | Autenticado | Registro de nuevo socio |
| `GET` | `/members/{id}` | Autenticado | Detalle del socio e historial |
| `PUT` | `/members/{id}` | Autenticado | Actualización de datos del socio |
| `GET` | `/loans` | Autenticado | Listado de préstamos con estado calculado (`open`, `overdue`, `closed`) |
| `POST` | `/loans` | Autenticado | Creación de préstamo (1 a 3 libros, validación de mora y cupo) |
| `GET` | `/loans/{id}` | Autenticado | Detalle del préstamo con sus ítems |
| `POST` | `/loans/{id}/return` | Autenticado | Devolución línea por línea de libros prestados |
| `GET` | `/reports/most-borrowed`| `admin` | Reporte agregado de libros más prestados y categorías |

---

## 🧪 Pruebas Automatizadas

```bash
php artisan test
# o con vendor/bin/phpunit
```
