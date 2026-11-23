# Configuracions

Còpies dels fitxers de configuració de cada servidor, **sense contrasenyes**. Cada fitxer indica a la primera línia on va dins la màquina.

| Carpeta | Fitxer | Ruta a la màquina |
|---|---|---|
| `router-r-n04/` | `01-network-manager-all.yaml` | `/etc/netplan/01-network-manager-all.yaml` |
| `router-r-n04/` | `01-network-manager-all.yaml` | `/etc/netplan/01-network-manager-all.yaml` |
| `router-r-n04/` | `intranet.conf` | `/etc/dnsmasq.d/intranet.conf` (DHCP i DNS) |
| `router-r-n04/` | `iptables.sh` | Regles de firewall i NAT (`iptables`) |
| `web-w-n04/` | `01-w-n04.yaml` | `/etc/netplan/01-w-n04.yaml` |
| `web-w-n04/` | `config.example.php` | `/var/www/html/app/app/config.php` (sense contrasenya) |
| `ftp-f-n04/` | `01-f-n04.yaml` | `/etc/netplan/01-f-n04.yaml` |
| `ftp-f-n04/` | `vsftpd.conf` | Línies afegides a `/etc/vsftpd.conf` |
| `ftp-f-n04/` | `vsftpd.userlist` | `/etc/vsftpd.userlist` |
| `bbdd-b-n04/` | `01-netcfg.yaml` | `/etc/netplan/01-netcfg.yaml` |
| `bbdd-b-n04/` | `50-server.cnf` | Línia modificada a `/etc/mysql/mariadb.conf.d/50-server.cnf` |

Els scripts SQL de la base de dades són a [`sql/`](../sql/).
