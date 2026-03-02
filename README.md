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



#######
to-do s
1- rodo bölümü gelirsitirelecek . test kandidatlara link butona tiklaninca otomatik yxollanacak 
2-  ergebnis sayfasi > test basarili ise pdf olarak indirilebilen sertifika / katiim belgesi 
3- girthub sayfasi duzenleencek 
4- sql data son hali yüklenecek 
5- kodun son hali yüklenecek 




#########
dusunulen / hedeflenen/ planlanan  / fikirler : 
1- test adaylari listesi excelden yüklensin 
2- sorular excelden yuklensin 
3- admin ergebnisi detyli görüyor -- ilk dusunceye göre isveren gibi o da sadece basarili sonucu görsün. ayrinitiyi o da görmesin.  
4- admin anasayfaya egitim presantationunu ekleyebilsin - egirim kismini da ekle ???


#####
Bugs & Fehler:
1- übersicht te  silme hata veriyor - database e kayitlar 0 olarak kaydediliyor 
2-  


#################################

To-Do Liste (Prioritäten)
1-Erweiterung des "todo"-Bereichs: Die Funktionalität für Testkandidaten muss ausgebaut werden. Bei Klick auf den Button soll automatisch ein individueller Test-Link an die Kandidaten versendet werden.
2-Ergebnisseite & Zertifizierung: Wenn ein Test erfolgreich bestanden wurde, soll auf der Ergebnisseite automatisch ein Zertifikat / eine Teilnahmebescheinigung generiert werden, die als PDF heruntergeladen werden kann.
3- GitHub Bereinigung: Das Repository muss strukturiert und die README-Datei sowie die Ordnerhierarchie aktualisiert werden.
4- SQL-Daten: Der aktuellste Stand der Datenbankstruktur und der Testdaten (Dump) muss hochgeladen werden.
5- Code-Finalisierung: Der letzte Stand des Quellcodes (Bugfixes inklusive) muss in das System eingepflegt werden.

Geplante Funktionen / Ideen
1- Excel-Import für Kandidaten: Implementierung einer Funktion, um Testkandidaten-Listen direkt aus einer Excel-Datei in die Datenbank zu importieren.
2- Excel-Import für Fragen: Die Fragen und Antworten des Quiz sollen bequem über eine Excel-Vorlage hochgeladen werden können.
3- Eingeschränkte Ergebnisansicht (Admin): Anpassung der Admin-Rechte – Der Administrator (Arbeitgeber) soll, genau wie der Nutzer, nur das Endergebnis (Bestanden/Nicht bestanden) sehen. Detaillierte Fehleranalysen sollen aus Datenschutz- oder Vereinfachungsgründen ausgeblendet werden.
4- admin - homagege -prasantationu .. kan - hochladen -  4- admin anasayfaya egitim preantationunu ekleyebilsin - egirim kismini da ekle ?



Fehlerliste (Bug-Log)
1- Löschfehler in der Übersicht: Beim Löschen eines Datensatzes in der Übersicht tritt ein Fehler auf. Der Datensatz wird in der Datenbank nicht korrekt entfernt, sondern stattdessen mit dem Wert 0 überschrieben oder als 0 gespeichert.
2-
