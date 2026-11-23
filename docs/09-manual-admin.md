# Manual de administrador

Guía para mantener la infraestructura en el día a día: cómo entrar en cada máquina, comprobar y reiniciar servicios, actualizar la aplicación, hacer copias de seguridad y resolver los problemas más habituales.

La instalación detallada de cada servidor está en sus documentos (`02` a `07`).

## Índice

1. [Mapa del sistema](#1-mapa-del-sistema)
2. [Acceso a los servidores](#2-acceso-a-los-servidores)
3. [Orden de arranque y apagado](#3-orden-de-arranque-y-apagado)
4. [Comprobar y reiniciar servicios](#4-comprobar-y-reiniciar-servicios)
5. [Actualizar la aplicación web](#5-actualizar-la-aplicación-web)
6. [Copia de seguridad de la base de datos](#6-copia-de-seguridad-de-la-base-de-datos)
7. [Gestionar usuarios FTP](#7-gestionar-usuarios-ftp)
8. [Resolución de problemas](#8-resolución-de-problemas)

---

## 1. Mapa del sistema

| Máquina | Zona | IP | Servicios | Puertos |
|---|---|---|---|---|
| **R-N04** | Router | `10.4.10.1` (DMZ) · `10.4.20.1` (Intranet) | Firewall (iptables), NAT, DHCP y DNS (dnsmasq), SSH | 53, 67, 22 |
| **W-N04** | DMZ | `10.4.10.10` | Apache + PHP, aplicación web, SSH | 80, 22 |
| **F-N04** | DMZ | `10.4.10.20` | FTP (vsftpd), SSH | 21, 40000-40100, 22 |
| **B-N04** | Intranet | `10.4.20.10` | MariaDB (`crud_db`), SSH | 3306, 22 |
| **PC Linux / PC Windows** | Intranet | DHCP | Clientes | — |

Arquitectura completa: [01-arquitectura.md](01-arquitectura.md).

---

## 2. Acceso a los servidores

Todos los equipos tienen el usuario **`bchecker`** con permisos de administrador (`sudo`).

```bash
ssh bchecker@10.4.20.1     # R-N04 (router)
ssh bchecker@10.4.10.10    # W-N04 (web)
ssh bchecker@10.4.10.20    # F-N04 (FTP)
ssh bchecker@10.4.20.10    # B-N04 (base de datos)
```

> El firewall solo permite SSH **desde la Intranet** (o desde el router). Desde la DMZ no se puede entrar en B-N04: es intencionado.

---

## 3. Orden de arranque y apagado

| Arranque | Máquina | Por qué |
|---|---|---|
| 1 | **R-N04** | Sin router no hay red, DHCP ni DNS |
| 2 | **B-N04** | La web necesita la base de datos |
| 3 | **W-N04** y **F-N04** | Servicios de la DMZ |
| 4 | Clientes | Piden IP al DHCP del router al arrancar |

El apagado se hace en el **orden inverso**. Si un cliente arranca antes que el router, se queda sin IP: hay que reiniciar su red o el equipo.

---

## 4. Comprobar y reiniciar servicios

| Servidor | Servicio | Ver estado | Reiniciar |
|---|---|---|---|
| R-N04 | DHCP + DNS | `systemctl status dnsmasq` | `sudo systemctl restart dnsmasq` |
| W-N04 | Web | `systemctl status apache2` | `sudo systemctl restart apache2` |
| F-N04 | FTP | `systemctl status vsftpd` | `sudo systemctl restart vsftpd` |
| B-N04 | Base de datos | `systemctl status mariadb` | `sudo systemctl restart mariadb` |
| Todos | SSH | `systemctl status ssh` | `sudo systemctl restart ssh` |

El estado correcto es **`active (running)`** y **`enabled`** (arranca solo al encender la máquina).

**Ver qué puertos escucha un servidor:**

```bash
sudo ss -tlnp
```

**Ver las reglas del firewall (R-N04):**

```bash
sudo iptables -L FORWARD -v -n
sudo iptables -t nat -L POSTROUTING -v -n
```

**Ver los errores de la web (W-N04):**

```bash
sudo tail -20 /var/log/apache2/error.log
```

---

## 5. Actualizar la aplicación web

Los cambios **nunca se hacen directamente en el servidor**: se suben a GitHub y el servidor los descarga. W-N04 se conecta a GitHub con una clave SSH de solo lectura.

```bash
ssh bchecker@10.4.10.10
cd /var/www/html/app
git pull
```

La conexión a la base de datos está en `app/config.php`, que **no está en el repositorio** (contiene la contraseña). Si se instala la aplicación en un servidor nuevo:

```bash
cp app/config.example.php app/config.php
nano app/config.php     # IP de B-N04, crud_db, usuario_app y su contraseña
```

Más detalle: [07-git-despliegue.md](07-git-despliegue.md).

---

## 6. Copia de seguridad de la base de datos

**Hacer la copia** (en B-N04):

```bash
sudo mysqldump --default-character-set=utf8mb4 crud_db > crud_db_$(date +%F).sql
```

`$(date +%F)` añade la fecha al nombre del archivo (por ejemplo `crud_db_2026-11-23.sql`), así no se sobrescriben las copias anteriores.

**Restaurarla:**

```bash
sudo mysql crud_db < crud_db_2026-11-23.sql
```

En el repositorio hay una copia de los datos importados: `sql/crud_db_equipaments.sql`.

**Comprobar que los datos están:**

```bash
sudo mysql -e "SELECT COUNT(*) FROM crud_db.equipaments_educacio;"
```

Tiene que devolver **2709**.

---

## 7. Gestionar usuarios FTP

Solo pueden entrar los usuarios que están en `/etc/vsftpd.userlist`. Para dar de alta uno nuevo (en F-N04):

```bash
sudo adduser nuevo_usuario
sudo mkdir /home/nuevo_usuario/archivos
sudo chown nuevo_usuario:nuevo_usuario /home/nuevo_usuario/archivos
echo "nuevo_usuario" | sudo tee -a /etc/vsftpd.userlist
sudo systemctl restart vsftpd
```

> Usar siempre `tee -a` (añadir). Sin la `-a` se borra el resto de la lista.

**Dar de baja** a un usuario: quitar su línea de `/etc/vsftpd.userlist` y reiniciar vsftpd. Los usuarios FTP **no se añaden al grupo `sudo`**.

---

## 8. Resolución de problemas

| Problema | Qué comprobar | Solución |
|---|---|---|
| La web no carga | `systemctl status apache2` en W-N04 | `sudo systemctl restart apache2` |
| La web dice "No se puede conectar con la base de datos" | Desde W-N04: `nc -zv -w 5 10.4.20.10 3306` | Si falla: comprobar MariaDB en B-N04 y las reglas del router. Si conecta: revisar usuario y contraseña en `config.php` |
| La web dice que no hay tablas | `SELECT COUNT(*)` en B-N04 | Restaurar la copia de seguridad |
| Un PC no tiene IP | `systemctl status dnsmasq` en el router | Reiniciar dnsmasq y la red del PC |
| Un PC no resuelve nombres (`SERVFAIL` en Linux) | `nslookup R-N04 10.4.20.1` | Si así funciona: `ResolveUnicastSingleLabel=yes` en `/etc/systemd/resolved.conf` del PC |
| No hay Internet en ningún equipo | `ping 8.8.8.8` desde el router | Comprobar la WAN del router y la regla `MASQUERADE` |
| Un servidor de la DMZ no tiene Internet | `ip route` (debe salir `default via 10.4.10.1`) | Revisar el netplan y la regla del firewall DMZ → Internet |
| El FTP se queda parado al hacer `ls` o `put` | El cliente usa modo activo | Conectar en modo pasivo: `ftp -p 10.4.10.20` |
| `git pull` pide contraseña o da `Repository not found` | `git remote -v` en W-N04 | Tiene que ser `git@github.com:...`, no `https://` |
| Las reglas del firewall desaparecen al reiniciar el router | `sudo iptables -L` | `sudo netfilter-persistent save` |
