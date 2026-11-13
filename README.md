# ProyectoPhp_Gestionprofesoral

## Prompt utilizado para la V1

Aquí tienes el prompt *corregido y actualizado, ajustado 100% a todo lo que trabajamos para la **v1 de Gestión Profesoral* (api_gestion / Programa), con los puertos correctos, las entidades del dominio de investigación/profesoral y las reglas de PowerShell/Windows:

text
Actúa como mi asistente de programación para construir la VERSIÓN 1 de un
proyecto universitario, partiendo de cero. Te adjunto 8 documentos: una
constitución (reglas permanentes) y el spec kit de la versión 1 (spec, plan,
research con las decisiones, modelo de datos, contratos, quickstart y tareas).

El proyecto es PHP 8.3 puro (sin frameworks ni Composer) + MariaDB — así lo
fija 3_plan.md. Son DOS programas que se construyen en esta misma versión:
la API (api_gestion) y su front (front_php), que habla con ella por HTTP. Si en tu respuesta aparece OTRO lenguaje o framework (Python,
Java, Node, Laravel, Symfony…), significa que no leíste los documentos
adjuntos: detente y dímelo en vez de continuar.

REGLAS DE TRABAJO (no negociables):

1. La especificación manda. No agregues NADA que los documentos no pidan:
   ni frameworks, ni Composer, ni librerías, ni tablas extra, ni motores
   extra, ni fábricas "por si acaso", ni mejoras de tu cosecha. Si crees que
   falta algo, pregúntame antes.
2. Vamos a seguir 8_tasks.md FASE POR FASE, en orden. En cada fase:
   a. Me explicas en 3-5 líneas qué vamos a hacer y por qué.
   b. Me entregas los archivos de la fase DE A UNO: primero la ruta exacta
      y el contenido COMPLETO de UN solo archivo (listo para copiar y
      pegar, con los comentarios didácticos en español que exige la
      constitución). Esperas mi "listo" y solo entonces me das el
      siguiente archivo de la fase.
   c. Al cerrar la fase me dices su comando de verificación y QUÉ salida
      esperar. Correr esa verificación en el momento es OPCIONAL: puedo
      dejarla para el final — tú sigues con la fase siguiente cuando yo
      diga "listo".
   NOTA: la estructura de carpetas y los archivos vacíos YA EXISTEN en mi
   proyecto — no me des comandos para crearlos; tu trabajo es dictarme el
   CONTENIDO de cada archivo.
3. Los errores NO nos frenan. Si te pego un error, lo diagnosticas y me
   das el archivo completo corregido; si no sale rápido, seguimos con las
   fases y lo retomamos al final. Al terminar todas las fases me guías
   para correr el smoke test de 7_quickstart.md y corregimos juntos todo
   lo que salga.
4. El código debe cumplir los contratos de 6_contracts.md al pie de la letra:
   mismos verbos, mismas rutas, mismos códigos de estado, mismos formatos de
   respuesta (incluido el contraste PUT=reemplazo completo vs PATCH=parcial,
   y el 422 con la lista de errores de validación). Del lado del front, las
   direcciones y las pantallas de la sección 9 del mismo documento.
5. El front NO toca la base de datos: no escribas ningún new PDO en
   front_php, y NO le hagas require de ningún archivo de api_gestion
   aunque los dos estén en PHP y funcionaría. Lo único que comparten es el
   JSON: el front trabaja con los arrays que devuelve json_decode.
6. Ninguna pantalla le habla al usuario en jerga: nada de PUT, PATCH,
   DELETE, /api/, códigos 404/422/500, PDO ni MariaDB en el texto que se
   ve. Los errores de la API se traducen a español en un solo archivo
   (cliente_api.php), no en cada vista.
7. Todo en español: nombres, comentarios y mensajes. PHP 8.3 con
   declare(strict_types=1) en cada archivo.
8. Yo trabajo en Windows con un IDE (VS Code, usando su terminal integrada
   de PowerShell) y Docker Desktop. Dame los comandos para ese entorno.
9. La base de datos YA VIENE DADA en db/init.sql — se monta tal cual en el
   compose; no escribas ni modifiques SQL de creación de tablas. Bootstrap
   también viene dado en front_php/publico/: enlaza esos archivos locales
   (/publico/bootstrap.min.css), NUNCA un CDN, y no me dictes su contenido.
10. En mi máquina TAMBIÉN corre el proyecto clonado del curso con sus
   puertos originales. Para que ambos convivan, MI proyecto:
   a. Publica los puertos del host con +100: en el docker-compose.yml el
      front va "8120:8020", la API va "8122:8022" (o 8113:8113) y MariaDB va
      "13426:3306" (adentro de los contenedores todo queda igual que en
      los documentos — incluido URL_API, que sigue apuntando a
      http://api-gestion:8113 porque esa es la red interna).
   b. El docker-compose.yml empieza con la línea `name: mi_v1_programa`
      (antes de services:) — así Docker lo trata como un proyecto
      distinto al del curso, con sus propios contenedores y volúmenes,
      aunque las carpetas se llamen parecido.
   El DSN por defecto del ensamblador (para correr sin Docker) apunta a
   localhost:13426. Cuando me des URLs o comandos de prueba, usa
   localhost:8120 (la pantalla), localhost:8122 (API) y localhost:13426 (BD).

Al final, la versión 1 está TERMINADA solo cuando pasan los 10 criterios de
aceptación de 2_spec.md, verificados con el smoke test de 7_quickstart.md —
incluidos los cuatro que solo se pueden comprobar desde la pantalla.

Empieza: resume en máximo 10 líneas qué vamos a construir (para confirmar que
entendiste el alcance) y luego arranca con la Fase 0.