# Servidor web (W-N04)

Servidor Apache con PHP situado en la **DMZ**. Sirve la aplicación web que muestra los datos de Open Data BCN guardados en el servidor de base de datos (B-N04). El código se despliega desde el repositorio de GitHub con `git clone` y `git pull`.

**Responsable:** Oriol Coll Llorens

> **De un vistazo**
>
> | | |
> |---|---|
> | 🌐 Aplicación | `http://W-N04/app/app/` |
> | 📍 Zona | DMZ · `10.4.10.10` |
> | 🗄️ Base de datos | B-N04 · `10.4.20.10:3306` (a través del router) |
> | 🔑 Despliegue | `git pull` con clave SSH (deploy key de solo lectura) |

## Índice

1. [Datos de la máquina](#1-datos-de-la-máquina)
2. [Creación de la máquina y comprobación de red](#2-creación-de-la-máquina-y-comprobación-de-red)
3. [Usuario obligatorio bchecker](#3-usuario-obligatorio-bchecker)
4. [Instalación de Apache y PHP](#4-instalación-de-apache-y-php)
5. [Verificación de Apache y PHP](#5-verificación-de-apache-y-php)
6. [Despliegue del repositorio con git](#6-despliegue-del-repositorio-con-git)
7. [Migración a la DMZ](#7-migración-a-la-dmz)
8. [Aplicación web](#8-aplicación-web)
9. [Conexión con la base de datos a través del router](#9-conexión-con-la-base-de-datos-a-través-del-router)
10. [Problemas encontrados y soluciones](#10-problemas-encontrados-y-soluciones)
11. [Resumen de comprobaciones](#11-resumen-de-comprobaciones)

---

## 1. Datos de la máquina

| Campo | Valor |
|---|---|
| Hostname | W-N04 |
| Sistema operativo | Ubuntu Server 22.04.4 LTS (plantilla mínima de IsardVDI) |
| Servidor web | Apache 2.4 |
| Lenguaje | PHP 8.1 |
| Interfaces | `enp1s0` (Default de IsardVDI) · `enp2s0` (DMZ) |
| Puertos del servicio | 80/TCP (web), 22/TCP (SSH) |
| Ruta de la aplicación | `/var/www/html/app/app` → `http://W-N04/app/app/` |

**Red**

| Fase | Red | IP |
|---|---|---|
| Instalación | Default de IsardVDI (NAT) | `192.168.121.177/22` por DHCP |
| DMZ | DMZ (`10.4.10.0/24`) | `10.4.10.10/24` fija, router `10.4.10.1` |

Durante la instalación la máquina usa la red **Default** de IsardVDI para tener salida a Internet y descargar paquetes. Después se conecta a la **DMZ** con IP fija (ver [apartado 7](#7-migración-a-la-dmz)).

---

## 2. Creación de la máquina y comprobación de red

Se crea un escritorio en IsardVDI con la plantilla de **Ubuntu Server 22.04** y la red **Default**. Después se cambia el nombre de la máquina, que lo exige el enunciado (`W-NCC`):

```bash
sudo hostnamectl set-hostname W-N04
exec bash
```

`exec bash` abre una nueva terminal para que el prompt muestre el nombre nuevo sin cerrar sesión.

La plantilla es **mínima** y no trae herramientas básicas, así que se instalan:

```bash
sudo apt update
sudo apt install nano iputils-ping
```

| Paquete | Para qué sirve |
|---|---|
| `nano` | Editor de texto para modificar archivos de configuración |
| `iputils-ping` | Comando `ping` para comprobar la conectividad |

Se comprueba la red:

```bash
ip a
ping -c 3 google.com
```

- `ip a` muestra la interfaz `enp1s0` con la IP `192.168.121.177`, asignada por el DHCP de IsardVDI.
- El `ping` a `google.com` responde, así que la máquina tiene Internet y resuelve nombres (DNS).

![IP de la máquina y ping a Internet](img/web-01-red-ip-ping.png)

---

## 3. Usuario obligatorio bchecker

El enunciado pide un usuario `bchecker` (contraseña `bchecker121`) en todos los equipos, para que el profesorado pueda revisarlos.

```bash
sudo adduser bchecker
sudo usermod -aG sudo bchecker
```

- `adduser` crea el usuario, su grupo y su carpeta personal (`/home/bchecker`).
- `usermod -aG sudo` lo añade al grupo `sudo` para que tenga permisos de administrador. La `-a` añade el grupo sin quitarle los que ya tenía.

![Creación del usuario bchecker](img/web-02-usuario-bchecker.png)

Para comprobarlo:

```bash
id bchecker
```

En la lista de grupos tiene que aparecer `sudo`.

---

## 4. Instalación de Apache y PHP

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql
```

| Paquete | Para qué sirve |
|---|---|
| `apache2` | Servidor web: recibe las peticiones del navegador y devuelve las páginas |
| `php` | Intérprete que ejecuta el código PHP de la aplicación |
| `libapache2-mod-php` | Módulo que conecta Apache con PHP, para que ejecute los `.php` en vez de mostrarlos como texto |
| `php-mysql` | Extensión que permite a PHP conectarse a MySQL/MariaDB (la base de datos de B-N04) |

Se instala PHP **8.1**, la versión de Ubuntu 22.04.

![Instalación de Apache y PHP](img/web-03-instalacion-apache-php.png)

Al acabar, el sistema pregunta qué servicios reiniciar porque algunas librerías se han actualizado. Se reinician los tres (`1 2 3`): son servicios internos y no afecta a nada.

Se comprueba que Apache está habilitado y en ejecución:

```bash
systemctl status apache2
```

Tiene que aparecer **`enabled`** (arranca solo al encender la máquina) y **`active (running)`**.

![Estado del servicio Apache](img/web-04-estado-apache.png)

> **Aviso AH00558:** Apache avisa de que no sabe el nombre completo del servidor y usa la IP. No es un error; el servicio funciona igual. Se puede quitar el aviso añadiendo `ServerName W-N04` al final de `/etc/apache2/apache2.conf` y reiniciando con `sudo systemctl restart apache2`.

---

## 5. Verificación de Apache y PHP

La máquina no tiene entorno gráfico y está detrás del NAT de IsardVDI, así que la comprobación se hace desde la propia máquina con **`curl`**, que pide la página igual que un navegador.

**Página por defecto de Apache**

```bash
curl -s http://localhost | grep -i "<title>"
```

Devuelve `Apache2 Ubuntu Default Page: It works`, así que Apache sirve páginas.

![Página por defecto de Apache](img/web-05-pagina-por-defecto.png)

**Página de prueba de PHP**

Se crea `info.php`, que muestra toda la configuración de PHP:

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

![Creación de info.php](img/web-06-crear-info-php.png)

Se comprueba que PHP se ejecuta y que tiene el módulo de MySQL:

```bash
curl -s http://localhost/info.php | grep -i -o "PHP Version [0-9]*" | head -1
curl -s http://localhost/info.php | grep -i -o "mysqli" | head -1
```

- Sale `PHP Version 8`: Apache **ejecuta** el código PHP (si no, devolvería el texto `<?php phpinfo(); ?>`).
- Sale `mysqli`: la aplicación podrá conectarse a la base de datos.

![PHP funcionando con el módulo mysqli](img/web-07-php-mysqli.png)

**Eliminar info.php**

```bash
sudo rm /var/www/html/info.php
```

![info.php eliminado](img/web-11-borrar-info-php.png)

`info.php` muestra información interna del servidor (versiones, rutas, módulos) que un atacante podría usar. Solo se usa para la prueba y después se borra.

---

## 6. Despliegue del repositorio con git

Todo el código y la documentación del proyecto están en GitHub:
[ITB2526-OriolColl/P0.0-ASIXc2C-G04](https://github.com/ITB2526-OriolColl/P0.0-ASIXc2C-G04)

El servidor web **descarga** el repositorio; los cambios se hacen en GitHub (o en el PC) y el servidor se actualiza con `git pull`. Así, lo que hay en producción es siempre lo que está en el repositorio.

**Permisos de la carpeta web**

```bash
sudo chown -R bchecker:www-data /var/www/html
```

`/var/www/html` pertenece a root. Con este cambio, el usuario puede clonar y actualizar sin `sudo`, y Apache (que funciona con el usuario `www-data`) puede leer los archivos.

**Clonar el repositorio**

```bash
cd /var/www/html
git clone https://github.com/ITB2526-OriolColl/P0.0-ASIXc2C-G04 app
```

El repositorio se clona en la carpeta `app`, para no mezclarlo con la página por defecto de Apache. La aplicación queda en `http://W-N04/app/app/`.

![Clonado del repositorio](img/web-08-git-clone.png)

Se comprueba el contenido y el último commit:

```bash
ls -l /var/www/html/app
cd /var/www/html/app && git log --oneline -3
```

En ese momento el repositorio solo tenía el `README.md` del commit inicial.

![Contenido inicial y último commit](img/web-09-git-log.png)

**Actualizar desde GitHub (subida a producción)**

Después de crear en GitHub la estructura de carpetas del proyecto, el servidor la descarga:

```bash
cd /var/www/html/app
git pull
ls -la
```

Aparecen las carpetas `app`, `config`, `docs` y `sql`. Este es el flujo de despliegue del proyecto: **cambio en GitHub → `git pull` en el servidor**.

![Estructura del repositorio después del git pull](img/web-10-git-pull-estructura.png)

---

## 7. Migración a la DMZ

Se añade a la máquina una tarjeta en la red **DMZ** de IsardVDI (la misma que la interfaz `enp4s0` del router) y se le da IP fija con netplan.

Archivo `/etc/netplan/01-w-n04.yaml`:

```yaml
network:
  version: 2
  ethernets:
    enp1s0:
      dhcp4: true
    enp2s0:
      addresses:
        - 10.4.10.10/24
      routes:
        - to: 10.4.20.0/24
          via: 10.4.10.1
```

- `enp1s0` (Default de IsardVDI) da salida a Internet.
- `enp2s0` es la DMZ: el tráfico hacia la Intranet (`10.4.20.0/24`) pasa por el router.

```bash
sudo chmod 600 /etc/netplan/01-w-n04.yaml
sudo netplan try
```

`netplan try` aplica la configuración y la deshace sola si no se confirma en 120 segundos, así no se pierde la conexión si algo falla.

![Netplan de W-N04](img/web-12-netplan-dmz.png)

**Comprobación:** con `tcpdump` en el router se ve por qué tarjeta llega el tráfico de W-N04. El ping entra y sale por `enp4s0`, la interfaz de la DMZ.

```bash
# En R-N04
sudo tcpdump -ni any icmp
# En W-N04
ping -c 3 10.4.10.1
```

![El tráfico de W-N04 entra por la DMZ del router](img/web-13-tcpdump-dmz.png)

---

## 8. Aplicación web

Aplicación PHP que muestra las tablas de la base de datos con un buscador. Está en la carpeta `app/` del repositorio:

| Archivo | Contenido |
|---|---|
| `index.php` | La aplicación: se conecta con PDO, lista las tablas y muestra los registros |
| `config.example.php` | Plantilla de conexión, sin contraseña (está en el repositorio) |
| `config.php` | Datos reales de conexión (no se sube: está en `.gitignore`) |

```bash
cd /var/www/html/app
git pull
cp app/config.example.php app/config.php
nano app/config.php
```

| Dato | Valor |
|---|---|
| Servidor | `10.4.20.10` (B-N04) |
| Base de datos | `crud_db` |
| Usuario | `usuario_app` |
| Contraseña | No se publica |

![Cadena de conexión con la IP de B-N04](img/web-14-config-db-host.png)

![config.php sin la línea de la contraseña](img/web-18-config-sin-password.png)

Desde el PC Linux de la Intranet, la aplicación muestra los **2709 registros** del CSV de Open Data BCN. Los acentos y la `ç` se ven bien gracias a la codificación `utf8mb4` de la base de datos.

![Aplicación con los datos desde el PC Linux](img/web-19-app-con-datos.png)

---

## 9. Conexión con la base de datos a través del router

| Prueba | Puerto | Resultado | Significado |
|---|---|---|---|
| Web → BBDD | 3306 (MariaDB) | ✅ `succeeded` | La aplicación puede usar la base de datos |
| Web → BBDD | 22 (SSH) | ⛔ `timed out` | El firewall bloquea todo lo demás |

**Puerto 3306: permitido**

```bash
nc -zv -w 5 10.4.20.10 3306
```

`nc` comprueba si un puerto está abierto en otra máquina (se instala con `sudo apt install netcat-openbsd`).

![Conexión al puerto 3306 de B-N04](img/web-15-nc-3306.png)

En el router, `tcpdump` muestra el camino completo: la petición entra por la DMZ (`enp4s0`), sale hacia la Intranet (`enp3s0`) y la respuesta de B-N04 vuelve por el mismo camino. (Las dos primeras líneas son un intento anterior a `10.4.20.1`, el router, que no tiene MariaDB.)

![tcpdump del tráfico web → BBDD en el router](img/web-16-tcpdump-3306.png)

**Puerto 22: bloqueado**

```bash
nc -zv -w 3 10.4.20.10 22
```

El firewall del router solo deja pasar el puerto 3306 de la DMZ a la Intranet. Si alguien comprometiera el servidor web, no podría llegar a nada más de la base de datos.

![El puerto 22 de B-N04 está bloqueado desde la DMZ](img/web-17-nc-22-bloqueado.png)

---

## 10. Problemas encontrados y soluciones

| Problema | Causa | Solución |
|---|---|---|
| `nano` y `ping` no existen | La plantilla de IsardVDI es mínima | `sudo apt install nano iputils-ping` |
| El prompt no muestra el nombre nuevo tras `hostnamectl` | El prompt se genera al abrir la terminal | `exec bash` o volver a iniciar sesión |
| Error `404 Not Found` al instalar `git` | Lista de paquetes desactualizada: pedía una versión que ya no existe | `sudo apt update` y volver a instalar |
| Pregunta de reinicio de servicios tras instalar Apache | `needrestart` detecta servicios con librerías antiguas | Reiniciarlos (`1 2 3`) |
| Aviso AH00558 en Apache | Falta la directiva `ServerName` | No afecta; se quita con `ServerName W-N04` |
| `git log --online` da error | Error de escritura | La opción correcta es `--oneline` |
| El tráfico de W-N04 entraba y salía por `enp3s0` del router | La tarjeta estaba en la red de IsardVDI de la Intranet, no en la DMZ | Detectado con `tcpdump`; se cambió la tarjeta a la red de la DMZ |
| `nc` al 3306 se quedaba parado | B-N04 respondía por su tarjeta Default de IsardVDI y no por el router | Detectado con `ip route get`; se quitó la tarjeta Default de B-N04 |
| `nc` no existe | Plantilla mínima | `sudo apt install netcat-openbsd` |
| `301 Moved Permanently` al pedir `/app/app` | Falta la barra final | Usar `/app/app/` |

---

## 11. Resumen de comprobaciones

| Comprobación | Comando | Resultado esperado |
|---|---|---|
| Red e Internet | `ping -c 3 google.com` | 3 respuestas, 0 % de pérdida |
| Usuario bchecker | `id bchecker` | Grupo `sudo` en la lista |
| Apache activo | `systemctl status apache2` | `active (running)` |
| Apache sirve páginas | `curl -s http://localhost` | `It works` |
| PHP con MySQL | `curl -s http://localhost/info.php` | `PHP Version 8` y `mysqli` |
| Repositorio desplegado | `git log --oneline` | Último commit de `main` |
| Está en la DMZ | `tcpdump` en el router + `ping 10.4.10.1` | Tráfico por `enp4s0` |
| Acceso a la BBDD | `nc -zv 10.4.20.10 3306` | `succeeded` |
| Firewall | `nc -zv 10.4.20.10 22` | `timed out` |
| Aplicación | `http://W-N04/app/app/` | Tabla con 2709 registros |
