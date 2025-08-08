# 🔍 Debug GitHub Actions Workflow

## 🚨 Mulige årsaker til at ingen release blir laget:

### 1. **Workflow triggeres ikke**
- Sjekk at filer ikke er i `paths-ignore` listen
- Verifiser at push er til `main` eller `master` branch

### 2. **Versjonsnummer ikke funnet**
- Workflow leter etter `Version: X.X.X` i PHP-filer
- Sjekk at format er korrekt i plugin-headeren

### 3. **Release eksisterer allerede**
- Workflow sjekker om tag `v1.3.0` allerede eksisterer
- Hvis ja, hopper den over release-opprettelse

## 🔧 Debug-steg:

### Steg 1: Sjekk GitHub Actions logs
1. Gå til repository → **Actions** tab
2. Se om workflow har kjørt
3. Klikk på workflow run for å se logs

### Steg 2: Manuell test av versjonsnummer
```bash
# Test kommando som workflow bruker:
grep -E "Version:\s*[0-9]+\.[0-9]+(\.[0-9]+)?" *.php | head -1 | grep -oE "[0-9]+\.[0-9]+(\.[0-9]+)?"
```

### Steg 3: Sjekk eksisterende tags
```bash
git tag
# Eller på GitHub: repository → Tags
```

## 🚀 Løsning: Forenklet workflow test

La meg lage en enkel test-workflow som alltid kjører: