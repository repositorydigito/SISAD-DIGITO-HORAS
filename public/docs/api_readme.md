# API de Proyectos para BI

Esta API proporciona acceso a los datos de proyectos para su integración con herramientas de Business Intelligence (BI).

## Autenticación

La API utiliza Laravel Sanctum para la autenticación basada en tokens. Para acceder a los endpoints protegidos, debes seguir estos pasos:

1. Obtener un token de acceso mediante el endpoint de login
2. Incluir el token en el encabezado `Authorization` de tus solicitudes como `Bearer {token}`

### Ejemplo de login

```
POST /api/login
Content-Type: application/json

{
    "email": "usuario@ejemplo.com",
    "password": "contraseña",
    "device_name": "BI Tool"
}
```

Respuesta:

```json
{
    "token": "1|abcdefghijklmnopqrstuvwxyz123456789",
    "user": {
        "id": 1,
        "name": "Usuario",
        "email": "usuario@ejemplo.com"
    }
}
```

## Endpoints disponibles

### Proyectos

#### Listar proyectos

```
GET /api/projects
```

Parámetros de consulta:
- `category`: Filtrar por categoría
- `state`: Filtrar por estado
- `phase`: Filtrar por fase
- `entity_id`: Filtrar por entidad
- `business_line_id`: Filtrar por línea de negocio
- `start_date_from`: Filtrar por fecha de inicio (desde)
- `start_date_to`: Filtrar por fecha de inicio (hasta)
- `end_date_from`: Filtrar por fecha de finalización (desde)
- `end_date_to`: Filtrar por fecha de finalización (hasta)
- `per_page`: Número de resultados por página (predeterminado: 15)
- `sort_field`: Campo para ordenar (predeterminado: created_at)
- `sort_direction`: Dirección de ordenamiento (asc/desc, predeterminado: desc)

#### Ver proyecto

```
GET /api/projects/{id}
```

#### Estadísticas de proyectos

```
GET /api/projects/statistics
```

Devuelve estadísticas generales de proyectos:
- Total de proyectos
- Proyectos activos
- Proyectos completados
- Proyectos suspendidos
- Proyectos por categoría
- Proyectos por fase

## Campos disponibles

Los proyectos incluyen los siguientes campos:

- `id`: Identificador único del proyecto
- `name`: Nombre del proyecto
- `code`: Código del proyecto
- `entity`: Información de la entidad asociada
- `business_line`: Información de la línea de negocio
- `category`: Categoría del proyecto
- `state`: Estado del proyecto (Activo, Inactivo, Completado, Suspendido)
- `start_date`: Fecha de inicio
- `end_date`: Fecha de finalización
- `end_date_projected`: Fecha de finalización proyectada
- `end_date_real`: Fecha de finalización real
- `real_progress`: Progreso real (porcentaje)
- `phase`: Fase del proyecto (Inicio, Planificación, Ejecución, Control, Cierre)
- `description`: Descripción del proyecto
- `description_incidence`: Descripción de incidencias
- `reason_incidence`: Motivo de incidencias
- `description_risk`: Descripción de riesgos
- `state_risk`: Estado del riesgo (Alto, Medio, Bajo, Controlado)
- `description_change_control`: Descripción del control de cambios
- `billing`: Porcentaje de facturación
- `delay_days`: Días laborables de desfase
- `users`: Usuarios asignados al proyecto
- `created_at`: Fecha de creación
- `updated_at`: Fecha de última actualización
- `created_by`: Usuario que creó el proyecto
- `updated_by`: Usuario que actualizó por última vez el proyecto
- `planned_progress`: Progreso planificado (calculado)
- `pending_billing`: Facturación pendiente (calculado)

## Colección de Postman

Para facilitar las pruebas, se proporciona una colección de Postman con todos los endpoints disponibles. Puedes descargarla desde:

`/docs/projects_api_collection.json`

## Integración con herramientas de BI

Esta API está diseñada para integrarse fácilmente con herramientas de BI como:

- Power BI
- Tableau
- QlikView
- Looker
- Metabase

Para la integración, utiliza el endpoint de listado de proyectos con los filtros adecuados para obtener los datos necesarios para tus dashboards y reportes.
