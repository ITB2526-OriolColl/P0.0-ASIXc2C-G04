# Router (R-N04)

Router que connecta Internet, la DMZ i la Intranet. Fa de firewall (iptables), NAT, DHCP i DNS (dnsmasq).

**Responsable:** Jordi

## Índex

1. [Dades de la màquina](#1-dades-de-la-màquina)
2. [Configuració de xarxa](#2-configuració-de-xarxa)
3. [DHCP i DNS amb dnsmasq](#3-dhcp-i-dns-amb-dnsmasq)
4. [Usuari obligatori bchecker](#4-usuari-obligatori-bchecker)
5. [Accés remot per SSH](#5-accés-remot-per-ssh)
6. [Encaminament, NAT i firewall](#6-encaminament-nat-i-firewall)
7. [Resum de comprovacions](#7-resum-de-comprovacions)

---

## 1. Dades de la màquina

| Camp | Valor |
|---|---|
| Hostname | `R-N04` (també `R`) |
| Equip / grup | `N04` / `G04` |
| Sistema operatiu | Ubuntu Server 22.04 LTS |
| Serveis | iptables, NAT, dnsmasq (DHCP i DNS), SSH |

---

## 2. Configuració de xarxa

| Interfície | Xarxa | IP |
|---|---|---|
| `enp1s0` | WAN (Default d'Isard) | DHCP d'Isard |
| `enp2s0` | Gestió (WireGuard) | Assignada per la VPN d'Isard |
| `enp3s0` | Intranet | `10.4.20.1/24` |
| `enp4s0` | DMZ | `10.4.10.1/24` |

<!-- TODO Jordi: confirmar les IPs de enp3s0 i enp4s0 i afegir captura del netplan -->

Les IPs `.1` són les portes d'enllaç dels servidors i clients de cada xarxa.

![Interfícies de xarxa del router](https://github.com/user-attachments/assets/92d9abae-b208-45e5-8ad4-7d793a49597b)

---

## 3. DHCP i DNS amb dnsmasq

dnsmasq reparteix IPs per DHCP i resol els noms de les màquines.

```bash
sudo apt install dnsmasq
```

![Instal·lació de dnsmasq](https://github.com/user-attachments/assets/54fb1562-d743-4908-9f42-77074cc825fb)

**Configuració de `/etc/dnsmasq.conf`**

<!-- TODO Jordi: copiar aquí el contingut del fitxer com a text -->

![Configuració de dnsmasq](https://github.com/user-attachments/assets/cd01b477-c775-4c1b-af18-022d23573223)

**Noms locals a `/etc/hosts`** (dnsmasq els fa servir per resoldre `R-N04`, `W-N04`, etc.)

![Registres locals a /etc/hosts](https://github.com/user-attachments/assets/35b5bb4e-7052-4aec-a582-78a6b61df468)

```bash
sudo systemctl restart dnsmasq
```

---

## 4. Usuari obligatori bchecker

```bash
sudo adduser bchecker
sudo usermod -aG sudo bchecker
```

![Creació de l'usuari bchecker](https://github.com/user-attachments/assets/b0648f3b-879d-4456-9f28-77f58b4cdc44)

---

## 5. Accés remot per SSH

```bash
sudo apt update && sudo apt install -y openssh-server
sudo systemctl enable --now ssh
sudo ss -tulpn | grep :22
```

Ha d'aparèixer `sshd` escoltant al port 22.

![SSH escoltant al port 22](https://github.com/user-attachments/assets/d58f86c4-4192-45e7-b598-9740b98a9222)

---

## 6. Encaminament, NAT i firewall

### 6.1 IP forwarding

Linux no reenvia paquets entre xarxes per defecte. A `/etc/sysctl.conf`:

```
net.ipv4.ip_forward = 1
```

```bash
sudo sysctl -p
```

![Activació de l'IP forwarding](https://github.com/user-attachments/assets/433e169f-f0b1-4168-be09-a18c6616b596)

### 6.2 NAT i firewall

```bash
# Netejar regles prèvies
sudo iptables -F
sudo iptables -t nat -F

# Per defecte, es bloqueja el tràfic entre xarxes
sudo iptables -P FORWARD DROP

# NAT: les xarxes internes surten a Internet amb la IP del router
sudo iptables -t nat -A POSTROUTING -o enp1s0 -j MASQUERADE

# Deixar passar les respostes de connexions establertes
sudo iptables -A FORWARD -m state --state RELATED,ESTABLISHED -j ACCEPT

# La Intranet pot anar a Internet i a la DMZ
sudo iptables -A FORWARD -i enp3s0 -j ACCEPT

# DMZ -> Intranet: només el web cap a la BBDD pel port 3306
sudo iptables -A FORWARD -i enp4s0 -s 10.4.10.10 -d 10.4.20.10 -p tcp --dport 3306 -j ACCEPT
```

<!-- TODO Jordi: afegir captura de "sudo iptables -L FORWARD -v -n" -->

---

## 7. Resum de comprovacions

| Comprovació | Comanda | Resultat esperat |
|---|---|---|
| Interfícies | `ip a` | 4 interfícies amb IP |
| dnsmasq | `systemctl status dnsmasq` | `active (running)` |
| DNS | `nslookup R-N04 10.4.20.1` | IP del router |
| SSH | `ss -tulpn \| grep :22` | `sshd` escoltant |
| Forwarding | `sysctl net.ipv4.ip_forward` | `= 1` |
| Firewall | `iptables -L FORWARD -v -n` | Política `DROP` i regles |
