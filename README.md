# declara·gt

Herramienta libre (MIT) para calcular el **ISR Régimen Opcional Simplificado
Mensual** de Guatemala a partir de los archivos de facturación electrónica
(FEL) que exporta la Agencia Virtual del SAT.

Es **local-first**: corre en tu propia máquina, no requiere cuenta ni
autenticación, no envía datos a ningún servidor externo, y usa SQLite por
defecto. Tus facturas, tus clientes y tus cálculos se quedan en tu equipo.

> _(Captura de pantalla del dashboard próximamente.)_

## Qué hace

- Importa el `.xls`/`.xlsx` que exporta el SAT y detecta automáticamente qué
  documentos emitiste y cuáles recibiste, comparando el NIT del emisor con
  el de tu empresa.
- Calcula el ISR del Régimen Opcional Simplificado por período (5% hasta
  Q30,000.00, 7% sobre el excedente), restando notas de crédito y
  acreditando tus retenciones.
- Calcula el IVA Régimen General mensual (débito, crédito fiscal y remanente
  que se arrastra de un período al siguiente), respetando las reglas que no
  son negociables: las facturas de Pequeño Contribuyente (FPEQ) nunca
  generan crédito fiscal, y una factura con IDP (impuesto al petróleo)
  siempre es combustible.
- Desglosa el débito y el crédito por casilla (ventas de bienes vs.
  servicios, combustibles vs. otras compras vs. servicios adquiridos), listo
  para llenar el formulario **SAT-2237**, y el ingreso por bienes/servicios
  para el **SAT-1311**.
- Muestra los números tal como los piden ambos formularios.
- Guarda un historial de tasas vigentes por fecha, para que los períodos
  viejos siempre se calculen con la tasa que aplicaba en su momento.

## Qué NO hace (todavía)

- No es para Régimen de Pequeño Contribuyente (solo calcula para quien
  tributa en el Opcional Simplificado + IVA general).
- No soporta múltiples contribuyentes ni tiene una versión hospedada.
- No presenta ni declara nada directamente ante el SAT ni en Declaraguate —
  solo te da los números para que tú (o tu contador) los ingreses ahí.

## Instalación local

Requiere **PHP 8.4+**, Composer y Node.js.

1. Clona el repositorio:
   ```bash
   git clone https://github.com/HEMBAT/declara-gt.git
   cd declara-gt
   ```
2. Instala las dependencias:
   ```bash
   composer install
   npm install
   ```
3. Copia el archivo de entorno:
   ```bash
   cp .env.example .env
   ```
4. Genera la llave de la aplicación y corre las migraciones con los seeders:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   ```
5. Compila los assets y levanta el servidor:
   ```bash
   npm run build
   php artisan serve
   ```
   Si usas [Laravel Herd](https://herd.laravel.com), el sitio ya está
   disponible en `http://declara-gt.test` sin necesidad de `php artisan serve`
   — solo asegúrate de aislar el proyecto en PHP 8.4 (`herd isolate php@8.4`).

Al entrar por primera vez, configura el NIT de tu empresa en
**Configuración** antes de importar tu primer archivo.

## Flujo multi-cliente (contadores)

Cada instalación de declara·gt trabaja con **un solo NIT a la vez**: en
cuanto importas el primer documento, el NIT del contribuyente queda
bloqueado (candado en **Configuración**) para evitar mezclar datos de
distintas empresas en la misma base.

Si eres contador y llevas varios clientes con esta misma instalación:

1. **Respaldar**: en Configuración → *Zona de peligro* → **Descargar
   respaldo**, descarga una copia de la base de datos del cliente actual
   (`declara-gt_{nit}_{fecha}.sqlite`).
2. **Reiniciar**: en la misma pantalla, **Reiniciar datos** borra
   documentos, clientes, retenciones, declaraciones y el contribuyente
   configurado — conserva los catálogos (tipos de DTE y parámetros de
   impuesto) para que no tengas que volver a cargarlos. Requiere escribir
   el NIT actual para confirmar.
3. **Cargar el siguiente cliente**: configura el NIT del nuevo
   contribuyente e importa su archivo del SAT como de costumbre.

Para **restaurar** un cliente anterior, reemplaza el archivo
`database/database.sqlite` por el respaldo que descargaste en el paso 1.

El remanente de crédito fiscal de IVA vive dentro de cada declaración, así
que viaja junto con el resto de los datos en cada respaldo — no hay un
remanente global fuera del archivo que debas trasladar aparte.

## Ejecutar los tests

```bash
php artisan test
```

## Disclaimer fiscal

Esta es una **herramienta de apoyo — no constituye asesoría fiscal**.
Verifica siempre los resultados con tu contador antes de presentar cualquier
declaración. Las tasas y tramos vienen precargados como referencia; te
recomendamos confirmarlos con un profesional antes de usarlos en producción.

**Importante sobre Declaraguate**: cuando entras a presentar el SAT-2237,
Declaraguate ya pre-carga sus propias casillas con lo que el SAT registró de
tus DTE — no las llena en blanco. La pantalla **Formulario** de declara·gt
te da los mismos subtotales por casilla (ventas/servicios, combustibles/
otras compras/servicios adquiridos) para que puedas armar el período *antes*
de entrar a Declaraguate, pero **siempre verifica que tus números cuadren
con la pre-carga que te muestra el sistema del SAT** antes de presentar — si
no cuadran, confía en la pre-carga del SAT e investiga la diferencia (documentos
que falten importar, mal clasificados, etc.) antes de continuar.

## Contribuir

Los pull requests son bienvenidos. La regla de oro de este proyecto: **el
cálculo fiscal solo se modifica junto con un test que respalde el cambio**.
Otras convenciones: todo en español (código, tablas, columnas, UI), Blade +
JS vanilla + CSS propio sin frameworks de frontend, dinero siempre en
`DECIMAL` (nunca `float`), y cero llamadas a servicios externos en runtime —
los datos del usuario no salen de su equipo.

## Licencia

MIT. Ver [LICENSE](LICENSE).

## Proyectos Libres de HEMBAT

declara·gt es parte de los Proyectos Libres de HEMBAT: herramientas de
código abierto hechas para la comunidad guatemalteca. Conoce el resto en
[hembat.com](https://hembat.com).
