Govee H7076 – IP-Symcon 9.1 – Version 1.0 (Testversion)

Inhalt:
  library.json
  GoveeH7076/module.json
  GoveeH7076/module.php
  Geraete_ermitteln.php

INSTALLATION
1. Entpackten Ordner GoveeH7076 (der library.json enthält) in das lokale
   IP-Symcon-Modulverzeichnis kopieren ODER als lokales Modul-Repository
   entsprechend der vorhandenen Symcon-Installation einbinden.
2. IP-Symcon Modulverwaltung aktualisieren und Modul laden.
3. Hilfsskript Geraete_ermitteln.php in Symcon anlegen, API-Key lokal eintragen
   und einmal ausführen. Geräte-IDs niemals öffentlich weitergeben.
4. Zwei Instanzen vom Typ "Govee H7076" erstellen.
5. In jeder Instanz denselben API-Key und jeweils eine andere Geräte-ID
   eintragen. Änderungen übernehmen, Verbindung testen.
6. Ein/Aus, Helligkeit, Gesamtfarbe, Farbe oben und Farbe unten testen.

HINWEISE
- Index 0 = oben, Index 1 = unten, anhand des Praxistests bestätigt.
- Helligkeitsänderungen senden nur einen Helligkeitsbefehl; die Farben
  werden vom Modul nicht verändert. Ob die Hardware sie bei jedem Modus
  beibehält, muss in der Praxis geprüft werden.
- Eine Änderung der Gesamtfarbe kann die Segmentfarben am Gerät überschreiben.
- Variablen zeigen zuletzt vom Modul gesendete Werte, keinen Live-Rückkanal.
- Cloud-API benötigt Internet und kann Rate-Limits haben.
- API-Key bleibt in der lokalen Instanzkonfiguration.
- Testversion: erst mit einer Instanz prüfen.
