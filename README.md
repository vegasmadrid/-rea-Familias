# Área Familias - Colegio Casa de la Virgen

Plugin de WordPress para el Área de Familias del Colegio Casa de la Virgen (`familias.casadelavirgen.es`). Es el "hermano" del plugin **Área Profesores** (`profesores.casadelavirgen.es`) y se comunica con él.

## Qué hace

### Área Familias › Ajustes
- **Página de login**: se elige una página y su contenido se sustituye por la pantalla de acceso (logo, tribanda corporativa y botón "Accede con tu cuenta del colegio" de *Nextend Social Login*, sin logo de Google). Siempre es pública.
- **Página de inicio**: a dónde van las familias al iniciar sesión (o, si se marca, a la página que intentaban abrir). Es también la **portada de la web** (la dirección del dominio), en lugar de la de *Ajustes › Lectura*.
- **Solo cuentas del colegio**: únicamente las cuentas `@casadelavirgen.es` (dominio configurable) pueden registrarse y entrar:
  - Nextend Social Login no crea cuentas de otros dominios.
  - El acceso con usuario y contraseña de otros dominios se rechaza.
  - Si una cuenta de otro dominio tiene sesión abierta, se cierra y se muestra el aviso en la página de login.
  - Los editores y administradores de la web pueden usar cualquier cuenta.
- **Restringir toda la web**: sin sesión solo se ven la página de login y las páginas marcadas como públicas. También se cierran la API REST y los feeds.
- Ocultar la barra de WordPress y bloquear `/wp-admin` a las familias.
- Textos de la pantalla de acceso, logotipo y tribanda.
- **Modo mantenimiento**: mientras está activo, las familias van al iniciar sesión a la página que elijas (por ejemplo, la página de inicio antigua) y en ella no se inserta nada. Los editores y administradores siguen viendo el panel nuevo, con un aviso, para poder probar los cambios.

### Área Familias › Espacios de aula
- Los espacios de Google Chat de cada aula se muestran como la opción **Espacios de aula** del panel de la familia (ya no es una página aparte).
- Los datos se **leen del Área de Profesores** (cursos, tutores con foto, promociones anteriores), así que siempre están actualizados. Se guardan en caché (60 minutos por defecto) y, si el Área de Profesores no responde, se muestra la última copia recibida.
- Opciones: introducción, tutor/a y su foto, promociones anteriores, otros espacios y recuadro de ayuda.

### Página de inicio de las familias (Área Familias › Panel de familias)
- En la **Página de inicio** (Ajustes) se muestra el panel de la familia: saludo ("Hola, …") y sus opciones:
  - **Mis hijos**: curso, etapa, tutor/a (con foto) y botón a su espacio de aula.
  - **Mis datos**: **teléfono de emergencia y dirección de cada padre/madre**, editables. Se guardan al momento en el Área de Profesores, que avisa por correo al tutor/a y a Secretaría.
  - **Espacios de aula**, **Reservar un servicio** y **Enlaces de interés** (configurables).
- Si falta el teléfono de emergencia de algún padre/madre, se muestra arriba un aviso (texto configurable) con un botón para completar los datos, y la pestaña **Mis datos** se marca hasta que se rellene.
- Los datos de alumnos y familias viven en el Área de Profesores; aquí solo se piden los de la familia que ha iniciado sesión (por su correo).

### Reservar un servicio (Área Familias › Reservas de servicios)
- La familia elige a uno o varios hijos, el servicio, el día (o varios días: desde – hasta y días de la semana) y contesta sus preguntas. Ve un resumen antes de reservar y, en **Mis reservas**, sus reservas pendientes, que puede anular dentro del plazo.
- Servicios: **Ampliación de horario** (7:30 a 9:45, con o sin desayuno), **Comedor** (pregunta si necesita dieta), **Ludoteca** (16:30 a 18:00), **Dieta** (tipo de dieta, instrucciones especiales y motivo opcional), **Salida anticipada** (hora de salida, quién le recoge y motivo opcionales) y **Retraso justificado** (hora de llegada y motivo opcional). Todos pueden llevar observaciones.
- De cada servicio se configura: si se puede reservar, nombre, descripción, horario (u horas permitidas), precio, plazo para reservar y anular (hasta las HH:MM del mismo día o de X días antes), si se pueden reservar varios días de una vez, etapas que lo pueden usar, una nota para las familias, sus preguntas y correos a los que avisar además del tutor/a y Secretaría.
- **Días sin clase** (festivos y vacaciones) y antelación máxima: esos días, y los fines de semana, no se pueden reservar. También se usan los *Días sin clase* del Área de Profesores (Comedor Diario), así que basta con ponerlos allí. Si una familia reserva comedor en Primer Ciclo, Infantil o Primaria, el alumno aparece marcado en la lista de comedor de su tutor/a y se suma a los comensales.
- Las reservas se guardan en el Área de Profesores, que envía los correos (tutor/a, Secretaría y confirmación a la familia; textos en sus *Avisos*) y las muestra en su backend, en la pestaña *Reservas* de cada tutor/a y en el listado de cocina.
- **Portería** puede reservar desde el Área de Profesores cualquier servicio para cualquier familia (cuando lo piden en persona o por teléfono). Para que use los mismos servicios, horarios y preguntas, esta web le envía la configuración de los servicios y los días sin clase al guardar *Reservas de servicios* (y cada hora, si algo ha cambiado). Esas reservas aparecen también en el panel de la familia, con *Reservada por: Portería*. Requiere el Área de Profesores 1.15.0 o posterior.

## Instalación

1. Sube la carpeta del plugin a `wp-content/plugins/area-familias` y actívalo.
2. Necesita **Nextend Social Login** con el proveedor de Google configurado (igual que el Área de Profesores).
3. En **Área Familias › Ajustes** elige la página de login y la de inicio.
4. En el **Área de Profesores** (versión 1.6.0 o posterior): *Área Profesores › Espacios de Chat › Conexión con el Área Familias* › **Activar y generar clave**.
5. En **Área Familias › Espacios de aula** pega la dirección del Área de Profesores y la clave y pulsa **Guardar y comprobar la conexión**.

## Conexión con el Área de Profesores

- `GET https://profesores.casadelavirgen.es/?rest_route=/area-profesores/v1/espacios-aula` con la cabecera `X-Area-Key: <clave>`.
- Solo devuelve los datos de la página de espacios (nombre del curso, promoción, enlace al espacio y tutor/a). Nunca devuelve los webhooks.
- La clave se puede regenerar o desactivar en el Área de Profesores en cualquier momento.
