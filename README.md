# Onboarding-Tool-fur-Arbeitssicherheit
Digitales Onboarding-System für Arbeitssicherheit
🚀 Arbeitssicherheit-Prüfungsportal (Unterweisungstraining)

Dieses Projekt ist eine webbasierte Anwendung zur Durchführung von Sicherheitsunterweisungen für neue Mitarbeiter. Es ersetzt den herkömmlichen Prozess von statischen PowerPoint-Präsentationen und manuellen PDF-Fragebögen durch ein dynamisches, datenbankgestütztes Testportal.

📋 Test-Spezifikationen & Regeln
Das System wurde nach folgenden Kriterien entwickelt, um eine benutzerfreundliche Lernumgebung zu schaffen:

Multiple-Choice-Verfahren: Ein Test besteht aus 5 gezielten Fachfragen.

Erfolgsquote: Zum Bestehen müssen mindestens 70 % der Fragen korrekt beantwortet werden (mindestens 4 von 5).

Unbegrenzte Versuche: Mitarbeiter können den Test beliebig oft wiederholen, bis das Lernziel erreicht ist.

Anonymität & Datenschutz: Die individuellen Ergebnisse werden nicht mit dem Arbeitgeber geteilt, um eine druckfreie Lernatmosphäre zu gewährleisten.

🚀 Warum dieses Projekt?
Die Herausforderung bei herkömmlichen PDF-Tests ist der hohe administrative Aufwand. Dieses Tool automatisiert den gesamten Prozess:

Automatisierung: Ergebnisse werden sofort berechnet, kein manuelles Korrigieren mehr.

Effizienz: Mitarbeiter erhalten direktes Feedback zu ihrem Wissensstand.

Modernisierung: Ein sauberer Web-Ansatz statt loser Dokumente.

🛠 Tech Stack
Backend: PHP (Native)

Datenbank: MySQL

Frontend: HTML5, CSS3, JavaScript (Bootstrap Framework)

Server: Apache (Kompatibel mit XAMPP / WAMP)

✨ Kernfunktionen
Direkter Testzugriff: Keine personalisierte Anmeldung erforderlich (vereinfachter Zugang für Onboarding-Prozesse).

Interaktives Quiz-Modul: Dynamische Abfrage von Sicherheitswissen (Brandschutz, Erste Hilfe, Arbeitsschutz).

Sofort-Auswertung: Anzeige der Punktzahl und des Status (Bestanden/Nicht bestanden) unmittelbar nach Abgabe.

Datenbank-Integration: Speicherung der aggregierten Testdaten zur Optimierung der Schulungsinhalte.

🔧 Installation
1- Repository klonen:

Bash
git clone https://github.com/dein-benutzername/arbeitssicherheit-test](https://github.com/mehmetince2019/Onboarding-Tool-fur-Arbeitssicherheit.git

2- Erstellen Sie eine Datenbank in MySQL (+ user anlegen) und importieren Sie die database.sql. (database = Sql.txt)

3- Passen Sie die config.php an.

##################

Öffnen Sie Ihr Datenbank-Verwaltungstool (z. B. phpMyAdmin).
Erstellen Sie eine neue Datenbank (z. B. arbeitssicherheit_db).
Importieren Sie die bereitgestellte database.sql Datei, um die Tabellenstruktur zu erstellen.
Konfiguration anpassen:
Suchen Sie die Verbindungsdatei (config.php ) und passen Sie die Parameter an Ihre lokale Umgebung an:

############

Starten Sie die Anwendung über Ihren lokalen Webserver (localhost).

demo : https://www.narter.online/
admin:admin123
