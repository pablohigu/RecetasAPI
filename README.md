# 4VChef API (RecetasAPI) 

API REST desarrollada con **Symfony** para la gestión de recetas de cocina, cálculo nutricional y sistema de valoraciones. Este proyecto sigue una arquitectura MVC estricta, utiliza DTOs para la validación de entrada/salida y Doctrine ORM para la persistencia de datos.

## Características

* **Gestión de Recetas**: Crear, listar, filtrar y eliminar recetas.
    * Incluye gestión de **Ingredientes** y **Pasos** de preparación.
    * Cálculo de **Valores Nutricionales** por comensal.
* **Datos Maestros**: Endpoints para obtener tipos de recetas (ej. Postre, Principal) y tipos de nutrientes (ej. Proteínas, Calorías).
* **Sistema de Valoraciones**:
    * Votación de recetas (0-5 estrellas).
    * **Validación de IP**: Restricción de un voto por IP para cada receta.
* **Borrado Lógico**: Las recetas no se eliminan físicamente de la base de datos, sino que se marcan como "borradas" (`is_deleted`) para mantener la integridad referencial.
* **Validaciones**: Uso de `Symfony Validator` y DTOs (`RecipeNewDTO`, `IngredientDTO`) para asegurar la integridad de los datos de entrada.

## Tecnologías

* **PHP** 8.2+
* **Symfony** 7.x
* **Doctrine ORM** (Base de datos relacional)
* **Symfony Serializer** & **Validator**
* **Docker** (opcional, configuración incluida en `compose.yaml`)

## Requisitos Previos

* PHP instalado en tu sistema.
* Composer.
* Symfony CLI (recomendado).
* MySQL o MariaDB (o Docker para levantar el servicio).

## Instalación y Configuración

1.  **Clonar el repositorio**
    ```bash
    git clone [https://github.com/tu-usuario/recetasapi.git](https://github.com/tu-usuario/recetasapi.git)
    cd recetasapi
    ```

2.  **Instalar dependencias PHP**
    ```bash
    composer install
    ```

3.  **Configurar Base de Datos**
    Copia el archivo `.env` a `.env.local` y ajusta tu conexión a base de datos en la variable `DATABASE_URL`:
    ```bash
    # .env.local
    DATABASE_URL="mysql://usuario:password@127.0.0.1:3306/4vchef?serverVersion=8.0.32&charset=utf8mb4"
    ```

4.  **Crear la Base de Datos y Migraciones**
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

5.  **Cargar Datos Iniciales (Semillas)**
    Para que la API funcione correctamente (especialmente al crear recetas que dependen de Tipos y Nutrientes), ejecuta estas sentencias SQL en tu gestor de base de datos:

    ```sql
    -- Tipos de Receta
    INSERT INTO recipe_type (id, name, description) VALUES 
    (1, 'Postre', 'Platos dulces'),
    (2, 'Principal', 'Platos fuertes'),
    (3, 'Ensalada', 'Entrantes frescos');

    -- Tipos de Nutrientes
    INSERT INTO nutrient_type (id, name, unit) VALUES 
    (1, 'Proteínas', 'gr'),
    (2, 'Carbohidratos', 'gr'),
    (3, 'Grasas', 'gr'),
    (4, 'Calorías', 'kcal');
    ```

6.  **Levantar el Servidor**
    ```bash
    symfony server:start
    ```
    La API estará disponible en `http://localhost:8000`.

## Documentación de la API

El proyecto incluye la especificación completa en formato **OpenAPI 3.0**.

* Archivo de especificación: [`4VChef2.yaml`](./4VChef2.yaml)
* Puedes importar este archivo directamente en **Postman** o **Swagger UI** para probar los endpoints automáticamente.

### Endpoints Principales

| Método | Endpoint | Descripción |
| :--- | :--- | :--- |
| `GET` | `/recipes` | Listar todas las recetas. Admite filtro `?type={id}`. |
| `POST` | `/recipes` | Crear una nueva receta (Requiere JSON complejo). |
| `DELETE` | `/recipes/{id}` | Borrado lógico de una receta. |
| `POST` | `/recipes/{id}/rating/{rate}` | Votar una receta (0-5). Valida IP única. |
| `GET` | `/recipe-types` | Listar tipos de receta disponibles. |
| `GET` | `/nutrient-types` | Listar tipos de nutrientes disponibles. |

## Ejemplo de JSON para Crear Receta

**POST** `/recipes`

```json
{
  "title": "Tiramisú Casero",
  "number-diner": 4,
  "type-id": 1,
  "ingredients": [
    { "name": "Mascarpone", "quantity": 500, "unit": "gr" },
    { "name": "Huevos", "quantity": 4, "unit": "ud" }
  ],
  "steps": [
    { "order": 1, "description": "Separar las yemas de las claras" },
    { "order": 2, "description": "Mezclar mascarpone con azúcar" }
  ],
  "nutrients": [
    { "type-id": 4, "quantity": 450 }
  ]
}
