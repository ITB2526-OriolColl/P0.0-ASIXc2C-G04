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

<img width="536" height="244" alt="image" src="https://github.com/user-attachments/assets/35b5bb4e-7052-4aec-a582-78a6b61df468" />


## 4. Usuari Obligatori `bchecker`

Es crea l'usuari utilitzat per a l'avaluació i l'auditoria automàtica de la configuració del router amb privilegis d'administració.

1. Creació de l'usuari al **sistema:**

<img width="624" height="108" alt="image" src="https://github.com/user-attachments/assets/b0648f3b-879d-4456-9f28-77f58b4cdc44" />

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

   <img width="730" height="109" alt="image" src="https://github.com/user-attachments/assets/d58f86c4-4192-45e7-b598-9740b98a9222" />

   
   ```

## 6. Seguretat i Encaminament (IP Forwarding, NAT i Iptables)

Configuració del reenviament de paquets, NAT per sortir a Internet i regles de seguretat entre les subxarxes Intranet i DMZ.

1. **Activació de l'IP Forwarding:** Editar `/etc/sysctl.conf` i afegir/descomentar:

   ```
   net.ipv4.ip_forward = 1
   
   ```

   Aplicar els canvis:

   ```

   <img width="490" height="109" alt="image" src="https://github.com/user-attachments/assets/433e169f-f0b1-4168-be09-a18c6616b596" />

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
 <img width="729" height="201" alt="image" src="https://github.com/user-attachments/assets/fa8a5fa3-8f29-46ba-8ec7-d3c926e90c68" />

   <img width="732" height="131" alt="image" src="https://github.com/user-attachments/assets/e284db24-c96d-49e8-b7d2-db6ac031e096" />
