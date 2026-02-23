# Fax Stack

Dockerized fax server solution based on **AvantFAX** with **HylaFAX+** and **IAXmodem**, providing a complete web-based fax management system.

## What's Included

- **AvantFAX** - Web-based front-end for managing faxes, users, and settings
- **HylaFAX+** - Fax server software for sending/receiving faxes
- **IAXmodem** - Software modem emulator for VoIP
- **MariaDB** - Database backend
- **Alpine Linux** - Lightweight container base

## Quick Setup

1. **Create environment file**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` and configure your database credentials.

2. **Create templates directory**
   ```bash
   cp -r templates_example templates
   ```
   You likely want to edit
    - `config.ttyIAX` and set `FAXNumber`, `LocalIdentifier` and `TagLineFormat`
    - `ttyIAX` and set `server`, `peername`, `secret`, `cidname` and `cidnumber`

3. **Build and start services**
   ```bash
   docker compose build
   docker compose up -d
   ```

4. **Access the web interface**
   - URL: http://localhost:8888
   - Default credentials: 
        - username: admin
        - password: password (on first login you will be prompted to change. New password has to contain at least one uppercase letter)


On windows you can install https://sourceforge.net/projects/wphf-reloaded/ which will create virtual printer that can send faxes. You need to use active mode for connecting to hylafax, as ftp doesn't play nice with docker's NAT

## Asterisk configuration
In `iax.conf`
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

In `extensions.conf` (you can use whatever extension you like)
```
exten => 3500,1,Dial(IAX2/iaxmodem2/3500,60)
```

## Ports

- `8888` - AvantFAX web interface
- `4559` - HylaFAX server

## Data Persistence

- Fax files: `faxes` volume
- Database: `db_data` volume

## License

AvantFAX is licensed under GPL v2. See [COPYING.txt](avantfax/COPYING.txt) for details.
