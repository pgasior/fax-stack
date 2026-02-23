# Fax Stack

Zintegrowane rozwiązanie serwera faksów w kontenerze Docker, oparte na **AvantFAX** z **HylaFAX+** i **IAXmodem**, zapewniające kompletny system zarządzania faksami przez przeglądarkę.

## Co zawiera

- **AvantFAX** - Interfejs webowy do zarządzania faksami, użytkownikami i ustawieniami
- **HylaFAX+** - Oprogramowanie serwera faksów do wysyłania/odbierania faksów
- **IAXmodem** - Emulator modemu programowego dla VoIP
- **MariaDB** - Baza danych
- **Alpine Linux** - Lekki system bazowy kontenera

## Szybka instalacja

1. **Utwórz plik środowiskowy**
   ```bash
   cp .env.example .env
   ```
   Edytuj `.env` i skonfiguruj dane dostępowe do bazy danych.

2. **Utwórz katalog z szablonami**
   ```bash
   cp -r templates_example templates
   ```
   Prawdopodobnie będziesz chciał edytować:
    - `config.ttyIAX` i ustawić `FAXNumber`, `LocalIdentifier` oraz `TagLineFormat`
    - `ttyIAX` i ustawić `server`, `peername`, `secret`, `cidname` oraz `cidnumber`

3. **Zbuduj i uruchom usługi**
   ```bash
   docker compose build
   docker compose up -d
   ```

4. **Dostęp do interfejsu webowego**
   - URL: http://localhost:8888
   - Domyślne dane logowania: 
        - nazwa użytkownika: admin
        - hasło: password (przy pierwszym logowaniu zostaniesz poproszony o zmianę. Nowe hasło musi zawierać przynajmniej jedną wielką literę)


W systemie Windows możesz zainstalować https://sourceforge.net/projects/wphf-reloaded/, który utworzy wirtualną drukarkę do wysyłania faksów. Musisz użyć trybu aktywnego dla połączenia z hylafax, ponieważ ftp nie współpracuje dobrze z NAT-em Dockera.

## Konfiguracja Asterisk
W pliku `iax.conf`
```
[iaxmodem2]
type=friend
host=dynamic
auth=md5
secret=iaxmodempass
context=internal
sendani=yes
disallow=all
allow=ulaw
allow=alaw
jitterbuffer=no
requirecalltoken=no
trunk=no
```

W pliku `extensions.conf` (możesz użyć dowolnego numeru wewnętrznego)
```
exten => 3500,1,Dial(IAX2/iaxmodem2/3500,60)
```

## Porty

- `8888` - Interfejs webowy AvantFAX
- `4559` - Serwer HylaFAX

## Trwałość danych

- Pliki faksów: wolumen `faxes`
- Baza danych: wolumen `db_data`

## Licencja

AvantFAX jest licencjonowany na licencji GPL v2. Zobacz [COPYING.txt](avantfax/COPYING.txt) po szczegóły.
