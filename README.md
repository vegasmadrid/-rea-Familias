# Área Familias - Colegio Casa de la Virgen

Plugin de WordPress para el Área de Familias del Colegio Casa de la Virgen (`familias.casadelavirgen.es`). Es el "hermano" del plugin **Área Profesores** (`profesores.casadelavirgen.es`) y se comunica con él.

## Qué hace

### Área Familias › Ajustes
- **Página de login**: se elige una página y su contenido se sustituye por la pantalla de acceso (logo, tribanda corporativa y botón "Accede con tu cuenta del colegio" de *Nextend Social Login*, sin logo de Google). Siempre es pública.
- **Página de inicio**: a dónde van las familias al iniciar sesión (o, si se marca, a la página que intentaban abrir).
- **Solo cuentas del colegio**: únicamente las cuentas `@casadelavirgen.es` (dominio configurable) pueden registrarse y entrar:
  - Nextend Social Login no crea cuentas de otros dominios.
  - El acceso con usuario y contraseña de otros dominios se rechaza.
  - Si una cuenta de otro dominio tiene sesión abierta, se cierra y se muestra el aviso en la página de login.
  - Los editores y administradores de la web pueden usar cualquier cuenta.
- **Restringir toda la web**: sin sesión solo se ven la página de login y las páginas marcadas como públicas. También se cierran la API REST y los feeds.
- Ocultar la barra de WordPress y bloquear `/wp-admin` a las familias.
- Textos de la pantalla de acceso, logotipo y tribanda.

### Área Familias › Espacios de aula
- Se elige la **página** donde se muestran los espacios de Google Chat de cada aula (se puede marcar como pública).
- Los datos se **leen del Área de Profesores** (cursos, tutores con foto, promociones anteriores), así que siempre están actualizados. Se guardan en caché (60 minutos por defecto) y, si el Área de Profesores no responde, se muestra la última copia recibida.
- Usa la cabecera y el título de la propia web (tema / Elementor); el plugin solo genera el contenido: introducción, buscador, cursos por etapa y promociones anteriores, con la estética de la web del colegio.
- Opciones: introducción, tutor/a y su foto, promociones anteriores, otros espacios y recuadro de ayuda.

### Página de inicio de las familias (Área Familias › Panel de familias)
- En la **Página de inicio** (Ajustes) se muestra el panel de la familia: saludo ("Hola, …") y sus opciones:
  - **Mis hijos**: curso, etapa, tutor/a (con foto) y botón a su espacio de aula.
  - **Mis datos**: dirección de cada padre/madre y **teléfono de emergencia**, editables. Al cambiarlos, el Área de Profesores avisa por correo al tutor/a y a Secretaría.
  - **Reservar un servicio** (muy pronto), **Enlaces de interés** (configurables) y acceso a **Espacios de aula**.
- Los datos de alumnos y familias viven en el Área de Profesores; aquí solo se piden los de la familia que ha iniciado sesión (por su correo).

## Instalación

1. Sube la carpeta del plugin a `wp-content/plugins/area-familias` y actívalo.
2. Necesita **Nextend Social Login** con el proveedor de Google configurado (igual que el Área de Profesores).
3. En **Área Familias › Ajustes** elige la página de login y la de inicio.
4. En el **Área de Profesores** (versión 1.6.0 o posterior): *Área Profesores › Espacios de Chat › Conexión con el Área Familias* › **Activar y generar clave**.
5. En **Área Familias › Espacios de aula** pega la dirección del Área de Profesores y la clave, elige la página y pulsa **Guardar y comprobar la conexión**.

## Conexión con el Área de Profesores

- `GET https://profesores.casadelavirgen.es/?rest_route=/area-profesores/v1/espacios-aula` con la cabecera `X-Area-Key: <clave>`.
- Solo devuelve los datos de la página de espacios (nombre del curso, promoción, enlace al espacio y tutor/a). Nunca devuelve los webhooks.
- La clave se puede regenerar o desactivar en el Área de Profesores en cualquier momento.
