# Tronity Fahrzeug 

Dieses Module erstellt eine Instanz welches ein Fahrzeug repräsentiert. 

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Anzeige und Abfrage der letzten Aufzeichnungen
* Starten des Ladevorgangs
* Stoppen des Ladevorgangs

### 2. Voraussetzungen

- IP-Symcon ab Version 6.4

### 3. Software-Installation

* Über den Module Store das 'Tronity'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Tronity'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
Fahrzeug ID   | ID des Fahrzeuges von Tronity
Fahrzeug Name | Anzeigename des Fahrzeuges 

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name                  | Typ     | Beschreibung
--------------------- | ------- | ------------
Status                | Integer | Meldung der letzten Ausgabe der Abfrage
Kilometerzähler       | Integer | Angabe, wie weit das Fahrzeug bis jetzt gefahren ist
Reichweite            | Integer | Angabe, wie weit das Fahrzeug mit der momentanen Ladeleistung fahren kann
Batterie Stand        | Integer | Prozentuale Angabe der Batterie
Ladestatus            | String  | Ladestatus des Fahrzeuges 
Verbleibende Ladezeit | Integer | Verbleibende Ladezeit des Fahrzeuges 
Eingesteckt           | Boolean | Gibt an ob das Fahrzeug gerade angeschlossen ist 
Ladeleistung          | Integer | Angabe mit wie viel das Fahrzeug geladen wird 
Breitengrad           | Float   | Breitengrad, auf welchem das Fahrzeug gerade steht
Längengrad            | Float   | Längengrad, auf welchem das Fahrzeug gerade steht
Zeitstempel           | Integer | Zeitpunkt der letzten Abfrage
Lettzes Update        | Integer | Zeitpunkt der letzten Aufzeichnung des Fahrzeuges 

#### Profile

Name           | Typ
-------------- | -------
TRON.Kilometer | Integer
TRON.Plugged   | Boolean
TRON.Position  | Float
TRON.Time      | Integer
TRON.Status    | Integer
TRON.Charging  | String

### 6. WebFront

Anzeige der Statusvariablen.

### 7. PHP-Befehlsreferenz

`string TRON_RequestLastRecord(integer $InstanzID);`
Fragt die letzten Aufzeichnungen des Fahrzeuges ab.

Beispiel:
`TRON_RequestLastRecord(12345);`


`string TRON_StartCharging(integer $InstanzID);`
Startet den Ladevorgang des Fahrzeuges.

Beispiel:
`TRON_StartCharging(12345);`


`string TRON_StopCharging(integer $InstanzID);`
Stopt den Ladevorgang des Fahrzeuges.

Beispiel:
`TRON_StopCharging(12345);`