# 🧪 Testing Instructions - GitHub Actions Release System

## 🎯 Hvordan teste at systemet fungerer

### Steg 1: Debug Workflow
Jeg har laget en debug-workflow som gir oss detaljert informasjon om hva som skjer.

**Fil**: [`.github/workflows/debug-release.yml`](.github/workflows/debug-release.yml:1)

### Steg 2: Kjør Debug Test

#### Automatisk test (ved push):
1. **Gjør en liten endring** (f.eks. legg til en kommentar i README.md)
2. **Commit og push** endringen
3. **Gå til GitHub** → repository → **Actions** tab
4. **Se debug-output** fra workflow

#### Manuell test:
1. **Gå til GitHub** → repository → **Actions** tab
2. **Velg "Debug Release Test"** workflow
3. **Klikk "Run workflow"** → **Run workflow**
4. **Se resultatet** i logs

### Steg 3: Analyser Debug Output

Debug-workflow vil vise:
- ✅ Alle PHP-filer som finnes
- ✅ Innhold av hovedplugin-fil
- ✅ Versjonsnummer som blir funnet
- ✅ Eksisterende Git tags
- ✅ Om release ville bli opprettet

### Steg 4: Vanlige problemer og løsninger

#### Problem 1: "No version found"
**Løsning**: Sjekk at plugin-headeren har riktig format:
```php
/*
Plugin Name: BD Product Sheet Editor Pro
Version: 1.3.0
*/
```

#### Problem 2: "Tag already exists"
**Løsning**: Oppdater versjonsnummer til en ny versjon (f.eks. 1.3.1)

#### Problem 3: "Workflow doesn't run"
**Løsning**: 
- Sjekk at repository er public ELLER
- Sett opp Personal Access Token (se [`PRIVATE-REPO-SOLUTION.md`](PRIVATE-REPO-SOLUTION.md:1))

#### Problem 4: "Permission denied"
**Løsning**: Workflow har `permissions: contents: write` - dette skal fungere for public repos

### Steg 5: Når debug viser at alt er OK

Hvis debug-workflow viser at:
- ✅ Versjonsnummer blir funnet
- ✅ Tag ikke eksisterer
- ✅ Workflow kjører uten feil

Da skal hovedworkflow [`.github/workflows/release.yml`](.github/workflows/release.yml:1) også fungere.

## 🔧 Feilsøking

### Sjekk GitHub Actions Logs

1. **Gå til Actions tab** på GitHub
2. **Klikk på workflow run**
3. **Klikk på job name** (f.eks. "debug" eller "release")
4. **Se detaljerte logs** for hvert steg

### Vanlige feilmeldinger:

#### "Resource not accessible by integration"
- **Årsak**: Repository er private eller mangler permissions
- **Løsning**: Gjør repo public eller bruk Personal Access Token

#### "Tag already exists"
- **Årsak**: Versjonsnummer er ikke oppdatert
- **Løsning**: Oppdater versjon i plugin-fil

#### "No version found"
- **Årsak**: Plugin header format er feil
- **Løsning**: Sjekk at `Version: X.X.X` er korrekt formatert

## 🚀 Når alt fungerer

Når debug-workflow kjører uten feil og finner versjonsnummer, vil:

1. **Hovedworkflow** lage automatisk releases
2. **WordPress** motta oppdateringsnotifikasjoner
3. **Brukere** kunne oppdatere med ett klikk

## 📞 Hvis du fortsatt har problemer

Send meg:
1. **Screenshot** av GitHub Actions logs
2. **Første 20 linjer** av plugin-filen
3. **Repository visibility** (public/private)

Da kan jeg hjelpe deg med spesifikk feilsøking!

---

**🧪 Test debug-workflow først - den vil vise oss nøyaktig hva som skjer!**