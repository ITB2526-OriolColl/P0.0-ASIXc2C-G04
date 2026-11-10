# Servidor de base de datos (B-N04)

Servidor MariaDB situado en la **Intranet**. Guarda los datos de la aplicación (base de datos `crud_db`) y solo acepta conexiones remotas de los usuarios creados para ello.

**Responsable:** Mario

## Índice

1. [Datos de la máquina](#1-datos-de-la-máquina)
2. [Configuración de red](#2-configuración-de-red)
3. [Instalación de MariaDB](#3-instalación-de-mariadb)
4. [Nombre de la máquina](#4-nombre-de-la-máquina)
5. [Usuario obligatorio bchecker](#5-usuario-obligatorio-bchecker)
6. [Permitir conexiones remotas](#6-permitir-conexiones-remotas)
7. [Seguridad del motor](#7-seguridad-del-motor-mysql_secure_installation)
8. [Base de datos y usuarios remotos](#8-base-de-datos-y-usuarios-remotos)
9. [Resumen de comprobaciones](#9-resumen-de-comprobaciones)
10. [Importación del CSV de OpenData](#10-importación-del-csv-de-opendata)
11. [Acceso a GitHub por clave SSH y subida de cambios](#11-acceso-a-github-por-clave-ssh-y-subida-de-cambios)
12. [Salida por el router y usuario restringido](#12-salida-por-el-router-y-usuario-restringido)

---

## 1. Datos de la máquina

| Campo | Valor |
|---|---|
| Hostname | B-N04 |
| Sistema operativo | Ubuntu 22.04 LTS |
| Motor de BBDD | MariaDB 10.6.23 |
| Red | Intranet (10.4.20.0/24) |
| Interfaz | `enp1s0` |
| IP | `10.4.20.10/24` (fija) |
| Puerta de enlace | `10.4.20.1` (router R-N04) |
| Puerto del servicio | 3306/TCP |

---

## 2. Configuración de red

El servidor tiene **IP fija** para que el servidor web y el DNS siempre lo encuentren en la misma dirección. Se configura con netplan en `/etc/netplan/01-netcfg.yaml`:

```yaml
network:
  version: 2
  renderer: networkd
  ethernets:
    enp1s0:
      dhcp4: no
      addresses:
        - 10.4.20.10/24
      routes:
        - to: default
          via: 10.4.20.1
      nameservers:
        addresses:
          - 8.8.8.8
```

- `dhcp4: no` → no pide IP al DHCP, usa la fija.
- `routes: default via 10.4.20.1` → todo lo que no sea de la Intranet sale por el router.
- `nameservers` → servidor DNS que usa la máquina.

```bash
sudo netplan apply
```

La máquina solo tiene la tarjeta de la Intranet: sale a Internet a través del router (NAT).

![Configuración de netplan](img/bbdd-12-netplan.png)

---

## 3. Instalación de MariaDB

Se usa **MariaDB**, una versión libre y compatible con MySQL: los comandos, el puerto (3306) y la forma de conectarse desde PHP son los mismos.

```bash
sudo apt install mariadb-server mariadb-client git net-tools -y
```

| Paquete | Para qué sirve |
|---|---|
| `mariadb-server` | El motor de base de datos |
| `mariadb-client` | El comando `mysql` para administrarla |
| `git` | Descargar el repositorio del proyecto |
| `net-tools` | Herramientas de red para comprobaciones |

![Instalación de MariaDB](img/bbdd-01-instalacion-mariadb.png)

Se comprueba que el servicio está habilitado (arranca solo al encender la máquina) y en ejecución:

```bash
sudo systemctl status mariadb
```

Tiene que aparecer **`enabled`** y **`active (running)`**.

![Estado del servicio MariaDB](img/bbdd-02-estado-mariadb.png)

---

## 4. Nombre de la máquina

El enunciado exige el nombre `B-NCC`.

```bash
sudo hostnamectl set-hostname B-N04
hostname
```

![Cambio de hostname](img/bbdd-13-hostname.png)

Al abrir una nueva terminal, el prompt ya muestra el nombre nuevo:

![Prompt con el hostname B-N04](img/bbdd-14-hostname-prompt.png)

---

## 5. Usuario obligatorio bchecker

El enunciado pide un usuario `bchecker` (contraseña `bchecker121`) en todos los equipos, para que el profesorado pueda revisarlos.

```bash
sudo adduser bchecker
sudo usermod -aG sudo bchecker
```

- `adduser` crea el usuario con su carpeta personal.
- `usermod -aG sudo` lo añade al grupo `sudo` para que tenga permisos de administrador. La `-a` es importante: añade el grupo sin quitarle los que ya tenía.

Durante la creación, el sistema avisa de que la contraseña es débil porque contiene el nombre del usuario. Al repetirla, la acepta igualmente.

![Creación del usuario bchecker](img/bbdd-03-crear-bchecker.png)

![bchecker añadido al grupo sudo](img/bbdd-04-bchecker-sudo.png)

---

## 6. Permitir conexiones remotas

Por defecto, MariaDB solo escucha en `127.0.0.1` (la propia máquina). Como la aplicación web está en **otro servidor** (W-N04, en la DMZ), hay que permitir conexiones desde la red.

Archivo: `/etc/mysql/mariadb.conf.d/50-server.cnf`

| Antes | Después |
|---|---|
| `bind-address = 127.0.0.1` | `bind-address = 0.0.0.0` |

`0.0.0.0` significa "escuchar en todas las interfaces de red".

**Antes:**

![bind-address antes del cambio](img/bbdd-05-bind-address-antes.png)

**Después:**

![bind-address después del cambio](img/bbdd-06-bind-address-despues.png)

Se reinicia el servicio para aplicar el cambio y se comprueba en qué dirección escucha:

```bash
sudo systemctl restart mariadb
sudo ss -tlnp | grep 3306
```

`ss -tlnp` muestra los puertos **T**CP en escucha (**l**isten), en formato **n**umérico y con el **p**roceso que los usa. El resultado `0.0.0.0:3306` confirma que MariaDB ya acepta conexiones remotas.

![MariaDB escuchando en 0.0.0.0:3306](img/bbdd-07-puerto-3306.png)

> **Seguridad:** abrir el puerto en todas las interfaces no significa que cualquiera pueda entrar. El router solo deja pasar el puerto 3306 desde el servidor web (W-N04), y además MariaDB pide usuario y contraseña.

---

## 7. Seguridad del motor (mysql_secure_installation)

Script que viene con MariaDB para quitar la configuración "de pruebas" que trae por defecto.

```bash
sudo mysql_secure_installation
```

| Pregunta | Respuesta | Por qué |
|---|---|---|
| Switch to unix_socket authentication | n | root ya estaba protegido |
| Change the root password | n | root ya estaba protegido |
| Remove anonymous users | Y | Evita que alguien entre sin usuario |
| Disallow root login remotely | Y | root solo puede entrar desde la propia máquina |
| Remove test database | Y | Base de datos de pruebas accesible por cualquiera |
| Reload privilege tables | Y | Aplica los cambios al momento |

![mysql_secure_installation (1/2)](img/bbdd-08-secure-installation-1.png)

![mysql_secure_installation (2/2)](img/bbdd-09-secure-installation-2.png)

---

## 8. Base de datos y usuarios remotos

Se entra en MariaDB como administrador:

```bash
sudo mysql -u root
```

![Acceso a MariaDB](img/bbdd-15-acceso-mysql.png)

Se crea la base de datos **`crud_db`** con codificación **`utf8mb4`**, que admite todos los caracteres (acentos, `ç`, `ñ`…). Es necesaria porque los datos de Open Data BCN tienen nombres en catalán. Después se crean los usuarios remotos y se les dan permisos:

```sql
CREATE DATABASE crud_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'usuario_app'@'%' IDENTIFIED BY '********';
GRANT ALL PRIVILEGES ON crud_db.* TO 'usuario_app'@'%';

CREATE USER 'bchecker'@'%' IDENTIFIED BY 'bchecker121';
GRANT ALL PRIVILEGES ON *.* TO 'bchecker'@'%' WITH GRANT OPTION;

FLUSH PRIVILEGES;
EXIT;
```

| Usuario | Desde dónde | Permisos | Para qué |
|---|---|---|---|
| `usuario_app` | `%` (cualquier equipo) | Solo sobre `crud_db` | Lo usa la aplicación del servidor web |
| `bchecker` | `%` (cualquier equipo) | Todas las bases de datos | Revisión por parte del profesorado |

- `'usuario'@'%'` → el `%` indica que el usuario puede conectarse desde cualquier IP.
- `crud_db.*` → todas las tablas de esa base de datos, y ninguna más.
- `FLUSH PRIVILEGES` → recarga los permisos para que se apliquen.

> La contraseña de `usuario_app` no se publica porque el repositorio es público. Se ha difuminado en la captura.

![Creación de la base de datos y los usuarios](img/bbdd-17-crear-bbdd-usuarios.png)

Comprobación final de que MariaDB sigue escuchando en el puerto 3306:

```bash
sudo ss -tlnp | grep mariadb
```

![Comprobación del puerto 3306](img/bbdd-16-comprobacion-puerto.png)

---

## 9. Resumen de comprobaciones

| Comprobación | Comando | Resultado esperado |
|---|---|---|
| Servicio activo | `systemctl status mariadb` | `active (running)` |
| Escucha en red | `ss -tlnp \| grep 3306` | `0.0.0.0:3306` |
| Seguridad aplicada | `mysql_secure_installation` | `All done!` |
| Hostname | `hostname` | `B-N04` |
| BBDD y usuarios | `CREATE DATABASE` / `CREATE USER` | `Query OK` |
| Datos importados | `SELECT COUNT(*) FROM equipaments_educacio;` | `2709` |

---

## 10. Importación del CSV de OpenData

Se carga el *Llistat d'equipaments d'educació* de OpenData Barcelona en `crud_db`.

```bash
curl -L -o equipaments.csv "https://opendata-ajuntament.barcelona.cat/data/dataset/f36b60f2-9541-4d08-b0f9-b0a9313fab3d/resource/29d9ff10-6892-4f16-9012-d5c4997857e7/download"
file -i equipaments.csv
wc -l equipaments.csv
```

![Descarga del CSV](img/bbdd-18-descarga-csv.png)

![Codificación original UTF-16](img/bbdd-19-csv-utf16.png)

![2710 líneas en el CSV](img/bbdd-20-csv-lineas.png)

Con la codificación original, los acentos salen rotos (`Formaci�`, `Montju�c`):

![CSV original con los acentos rotos](img/bbdd-21-csv-original.png)

El archivo viene en **UTF-16** (`charset=utf-16le`), y MariaDB mostraría los acentos rotos. Se convierte a UTF-8 y se eliminan los retornos de carro (`\r`):

```bash
iconv -f UTF-16 -t UTF-8 equipaments.csv | sed 's/\r$//' > equipaments_utf8.csv
file -i equipaments_utf8.csv
```

![Conversión a UTF-8](img/bbdd-22-conversion-utf8.png)

Se activa la carga local de archivos al entrar en MariaDB, y se crea la tabla con una columna por campo del CSV (tipo `TEXT`) más un `id` autoincremental como clave primaria, porque `register_id` se repite en varias filas:

```bash
sudo mysql --local-infile=1 -u root
```

```sql
USE crud_db;
SHOW VARIABLES LIKE 'local_infile';
```

![local_infile activado](img/bbdd-23-local-infile.png)

La tabla `equipaments_educacio` y la carga con `LOAD DATA LOCAL INFILE` están en el script `sql/02-importar-csv.sql`.

![Creación de la tabla](img/bbdd-24-crear-tabla.png)

![Carga de los datos y recuento](img/bbdd-25-carga-datos.png)

![Script de importación](img/bbdd-28-script-importar.png)

| Comprobación | Resultado |
|---|---|
| Filas importadas | **2709** (2710 líneas del CSV menos la cabecera) |
| Filas omitidas / avisos | 0 / 0 |
| Acentos | Correctos (`Formació`, `Sants-Montjuïc`) |

![Datos importados con los acentos correctos](img/bbdd-26-datos-acentos.png)

Copia de seguridad de la base de datos (con `sudo`, porque `root` de MariaDB se autentica con el usuario del sistema):

```bash
sudo mysqldump --default-character-set=utf8mb4 crud_db > sql/crud_db_equipaments.sql
```

Para restaurarla: `sudo mysql crud_db < sql/crud_db_equipaments.sql`.

![Copia de seguridad con mysqldump](img/bbdd-27-copia-seguridad.png)

---

## 11. Acceso a GitHub por clave SSH y subida de cambios

El servidor se autentica en GitHub por **clave pública/privada**, sin contraseña.

```bash
ssh-keygen -t ed25519 -C "bchecker@B-N04"
cat ~/.ssh/id_ed25519.pub
```

![Generación de la clave SSH](img/bbdd-29-ssh-keygen.png)

La clave pública se registra en GitHub (*Settings > SSH and GPG keys > New SSH key*) y se comprueba la conexión:

```bash
ssh -T git@github.com
```

Respuesta esperada: `Hi USUARIO! You've successfully authenticated, but GitHub does not provide shell access.`

Antes de registrar la clave en GitHub, la conexión se rechaza (`Permission denied (publickey)`):

![Conexión rechazada sin la clave registrada](img/bbdd-30-ssh-denegado.png)

![Clave registrada en GitHub](img/bbdd-31-github-ssh-key.png)

![Autenticación correcta](img/bbdd-32-ssh-autenticado.png)

Con la clave registrada, se clona el repositorio y se suben los cambios:

```bash
git clone git@github.com:ITB2526-OriolColl/P0.0-ASIXc2C-G04.git
git pull origin main
git add sql/ docs/
git commit -m "Dump de crud_db y script de importación del CSV"
git push origin main
```

![Clonado del repositorio](img/bbdd-33-git-clone.png)

![Subida del script y la copia de seguridad](img/bbdd-34-git-push.png)

> El usuario que sube los cambios debe ser colaborador del repositorio con permiso de escritura.

### Cambio de contraseña de `usuario_app`

La contraseña se cambió con `ALTER USER` y se aplicó con `FLUSH PRIVILEGES`. Se verificó la conexión desde W-N04. La contraseña no se documenta porque el repositorio es público.

---

## 12. Salida por el router y usuario restringido

### 12.1 Internet a través del router

Se quita la tarjeta Default de IsardVDI. B-N04 queda solo en la Intranet y sale a Internet a través del router (NAT):

```bash
ping -c 2 8.8.8.8
ping -c 2 github.com
```

Las dos responden: hay salida a Internet y el DNS resuelve nombres.

![Salida a Internet a través del router](img/bbdd-35-internet-router.png)

> Con la tarjeta Default, las respuestas a W-N04 salían por IsardVDI en lugar de por el router y se perdían. Sin ella, todo el tráfico pasa por R-N04.

### 12.2 `usuario_app` solo desde el servidor web

`usuario_app` podía conectarse desde cualquier IP (`'%'`). Se restringe a la IP de W-N04 (`10.4.10.10`):

```sql
USE crud_db;
DROP USER IF EXISTS 'usuario_app'@'%';
CREATE USER 'usuario_app'@'10.4.10.10' IDENTIFIED BY '********';
GRANT ALL PRIVILEGES ON crud_db.* TO 'usuario_app'@'10.4.10.10';
FLUSH PRIVILEGES;
```

![usuario_app restringido a W-N04](img/bbdd-36-usuario-app-restringido.png)

Es una **segunda capa de seguridad**: aunque el firewall del router fallara, MariaDB solo aceptaría a `usuario_app` desde el servidor web.

| Usuario | Desde dónde | Permisos |
|---|---|---|
| `usuario_app` | `10.4.10.10` (W-N04) | Solo sobre `crud_db` |
| `bchecker` | `%` (cualquier equipo) | Todas las bases de datos |
