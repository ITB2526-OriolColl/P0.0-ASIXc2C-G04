# Documentació del Router (R-N04)

## 1. Dades de la Màquina

* **Nom de l'host (Hostname):** `R-N04`

* **Grup de treball:** `G04` / `N04`

* **Sistema Operatiu:** Ubuntu Server 22.04 LTS

* **Funció principal:** Router d'interconnexió, Firewall (Iptables), Gateway (NAT), Servidor DHCP i DNS (Dnsmasq).

* **Ubicació a la xarxa:** Enllaç entre WAN, Intranet (`10.4.20.0/24`), DMZ (`10.4.10.0/24`) i xarxa de gestió Isard.

## 2. Configuració de Xarxa

El router disposa de 4 interfícies de xarxa per separat:

* **`enp1s0` (WAN / Internet):** DHCP automàtic d'Isard (accés a Internet).

* **`enp2s0` (Gestió / WireGuard):** IP assignada per la VPN d'Isard per a administració remota.

<img width="733" height="276" alt="image" src="https://github.com/user-attachments/assets/92d9abae-b208-45e5-8ad4-7d793a49597b" />


## 3. Instal·lació Dnsmasq / Resolució de Noms i Serveis

El router proporciona resolució de nom DNS local i assignació automàtica d'IPs (DHCP) per a la xarxa Intranet.

1. **Instal·lació del servei Dnsmasq:**

  <img width="729" height="267" alt="image" src="https://github.com/user-attachments/assets/54fb1562-d743-4908-9f42-77074cc825fb" />


2. **Configuració del fitxer `/etc/dnsmasq.conf`:**

 <img width="573" height="142" alt="image" src="https://github.com/user-attachments/assets/cd01b477-c775-4c1b-af18-022d23573223" />


3. **Registres locals de noms (`/etc/hosts`):**

   ```
   10.4.20.1    R-N04 router.g04.local
   10.4.20.10   B-N04 db.g04.local
   10.4.10.10   W-N04 web.g04.local
   
   ```

4. **Reinici del servei:**

   ```
   sudo systemctl restart dnsmasq
   
   ```

## 4. Usuari Obligatori `bchecker`

Es crea l'usuari utilitzat per a l'avaluació i l'auditoria automàtica de la configuració del router amb privilegis d'administració.

1. Creació de l'usuari al **sistema:**

   ```
   sudo useradd -m -s /bin/bash bchecker
   echo "bchecker:bchecker121" | sudo chpasswd
   
   ```

2. **Assignació de permisos de superusuari (`sudo`):**

   ```
   sudo usermod -aG sudo bchecker
   
   ```

## 5. Permetre Connexions Remotes

Es configura el servei SSH per permetre l'administració remota del router des de la xarxa de gestió (WireGuard) i la Intranet.

1. **Instal·lació del servidor OpenSSH:**

   ```
   sudo apt update && sudo apt install -y openssh-server
   sudo systemctl enable --now ssh
   
   ```

2. **Comprovació del port SSH (22):**

   ```
   sudo ss -tulpn | grep :22
   
   ```

## 6. Seguretat i Encaminament (IP Forwarding, NAT i Iptables)

Configuració del reenviament de paquets, NAT per sortir a Internet i regles de seguretat entre les subxarxes Intranet i DMZ.

1. **Activació de l'IP Forwarding:** Editar `/etc/sysctl.conf` i afegir/descomentar:

   ```
   net.ipv4.ip_forward = 1
   
   ```

   Aplicar els canvis:

   ```
   sudo sysctl -p
   
   ```

2. **Configuració de Firewall i NAT (Iptables):**

   ```
   # Netejar regles prèvies
   sudo iptables -F
   sudo iptables -t nat -F
   
   # Política per defecte
   sudo iptables -P FORWARD DROP
   
   # Masquerade (NAT) per sortir a Internet per la WAN
   sudo iptables -t nat -A POSTROUTING -o enp1s0 -j MASQUERADE
   
   # Permetre tràfic d'estat establert o relacionat
   sudo iptables -A FORWARD -m state --state RELATED,ESTABLISHED -j ACCEPT
   
   # Permetre accés complet de la Intranet cap a Internet i DMZ
   sudo iptables -A FORWARD -i enp3s0 -j ACCEPT
   
   # Restringir DMZ cap a Intranet: Només accés des del Web (10.4.10.10) a la BBDD (10.4.20.10) port 3306 (MySQL)
   sudo iptables -A FORWARD -i enp4s0 -s 10.4.10.10 -d 10.4.20.10 -p tcp --dport 3306 -j ACCEPT
   
   ```

## 7. Resum de Comprovacions

| Comprovació | Comanda d'execució
