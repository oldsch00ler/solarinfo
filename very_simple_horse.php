<?php

# AUTOR: Lukáš Zuzaňák
# VERZE: 20202503-1644
# POZNAMKA: Největší borec vole

# MQTT knihovna
require("../mqtt.php");

# NASTAVENI
$server = "mqtt.mqtt.cz";                 // MQTT server
$port = 1883;                             // MQTT port
$username = "";                           // MQTT uzivatel
$password = "";                           // MQTT heslo
$client_id = "vsh_solarinfo";             // MQTT ID pro tohoto klienta
$messageHead = "vsh_solarinfo/solarinfo"; // MQTT hlavicka
$delay = 10;                              // Pauza mezi posíláním
$solarServer = "";                        // Solar server
$solarPort = "";                          // Solar port
$solarTimeout = 10;                       // Solar timeout
$solarString = "2b0104db11855b0f0a";      // Solar string na poslani

# HEX -> FLOAT funkce
function hexTo32Float($strHex) {
    $v = hexdec($strHex);
    $x = ($v & ((1 << 23) - 1)) + (1 << 23) * ($v >> 31 | 1);
    $exp = ($v >> 23 & 0xFF) - 127;
    return $x * pow(2, $exp - 23);
}

# PROGRAMMMMMMM
while(1) { // Nekonecna smycka
    # MQTT pripojeni
    $mqtt = new phpMQTT($server, $port, $client_id);

    # Pripojeni na solar server (ss)
    $ss = fsockopen($solarServer, $solarPort, $errno, $errstr, $solarTimeout);

    # Kdyz se MQTT spoji
    if ($mqtt->connect(true, NULL, $username, $password)) {

        ## Kdyz se ss spojí
        if ($fp) {
            fwrite($fp, $solarString);
            while (!feof($fp)) {
                ### Odpoved od ss
                $solarResp = fgets($fp, 128);
            }

            ### Zavreni spojeni
            fclose($fp);
        } else {
            echo "$errstr ($errno)<br />\n";
        }

        # Orez
        $solarHex = substr($solarResp, 8, -4);

        # HEX -> FLOAT
        $solarValue = hexTo32Float($solarHex);

        ## Pushni zprávu do MQTT
        $mqtt->publish($messageHead, $solarValue . date("r"), 0);

        ## Zavři spojení
    	$mqtt->close();
    } else {
        echo "Neco je spatne, vole! A nereknu co!\n";
    }

    # Pockat par sekund
    sleep($delay);
}