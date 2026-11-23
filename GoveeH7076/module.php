<?php

class GoveeH7076 extends IPSModule
{
    private const API = 'https://openapi.api.govee.com/router/api/v1';

    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('APIKey', '');
        $this->RegisterPropertyString('Device', '');
        $this->RegisterPropertyString('SKU', 'H7076');
        $this->RegisterVariableBoolean('Power', 'Ein/Aus', '~Switch', 10);
        $this->EnableAction('Power');
        $this->RegisterVariableInteger('Brightness', 'Helligkeit', '~Intensity.100', 20);
        $this->EnableAction('Brightness');
        $this->RegisterVariableInteger('Color', 'Gesamtfarbe', '~HexColor', 30);
        $this->EnableAction('Color');
        $this->RegisterVariableInteger('TopColor', 'Farbe oben', '~HexColor', 40);
        $this->EnableAction('TopColor');
        $this->RegisterVariableInteger('BottomColor', 'Farbe unten', '~HexColor', 50);
        $this->EnableAction('BottomColor');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $ready = trim($this->ReadPropertyString('APIKey')) !== '' &&
            trim($this->ReadPropertyString('Device')) !== '';
        $this->SetStatus($ready ? 102 : 104);
    }

    public function GetConfigurationForm()
    {
        return json_encode([
            'elements' => [
                ['type' => 'ValidationTextBox', 'name' => 'APIKey', 'caption' => 'Govee API-Key'],
                ['type' => 'ValidationTextBox', 'name' => 'Device', 'caption' => 'Geräte-ID (device)'],
                ['type' => 'Label', 'caption' => 'Geräte-ID aus dem separaten Gerätelisten-Skript übernehmen. Jede Leuchte benötigt eine eigene Instanz.']
            ],
            'actions' => [
                ['type' => 'Button', 'caption' => 'Verbindung testen', 'onClick' => 'GH76_TestConnection($id);']
            ],
            'status' => [
                ['code' => 102, 'icon' => 'active', 'caption' => 'Bereit'],
                ['code' => 104, 'icon' => 'inactive', 'caption' => 'API-Key oder Geräte-ID fehlt'],
                ['code' => 201, 'icon' => 'error', 'caption' => 'API-Fehler']
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Power':
                $this->Control('devices.capabilities.on_off', 'powerSwitch', (int)(bool)$Value);
                $this->SetValue('Power', (bool)$Value);
                break;
            case 'Brightness':
                $brightness = max(0, min(100, (int)$Value));
                // Keine Farbbefehle senden: Segmentfarben bleiben gespeichert.
                $this->Control('devices.capabilities.range', 'brightness', $brightness);
                $this->SetValue('Brightness', $brightness);
                break;
            case 'Color':
                $rgb = max(0, min(0xFFFFFF, (int)$Value));
                $this->Control('devices.capabilities.color_setting', 'colorRgb', $rgb);
                $this->SetValue('Color', $rgb);
                break;
            case 'TopColor':
            case 'BottomColor':
                $rgb = max(0, min(0xFFFFFF, (int)$Value));
                $index = ($Ident === 'TopColor') ? 0 : 1;
                $this->Control('devices.capabilities.segment_color_setting', 'segmentedColorRgb', [
                    'segment' => [$index], 'rgb' => $rgb
                ]);
                $this->SetValue($Ident, $rgb);
                break;
            default:
                throw new Exception('Unbekannte Aktion: ' . $Ident);
        }
    }

    public function TestConnection(): string
    {
        $result = $this->ApiRequest('GET', '/user/devices');
        foreach (($result['data'] ?? []) as $item) {
            if (($item['sku'] ?? '') === 'H7076' &&
                ($item['device'] ?? '') === $this->ReadPropertyString('Device')) {
                return 'Verbindung erfolgreich: ' . ($item['deviceName'] ?? 'H7076');
            }
        }
        throw new Exception('Konfigurierte H7076 nicht in der Geräteliste gefunden.');
    }

    private function Control(string $type, string $instance, $value): void
    {
        $device = trim($this->ReadPropertyString('Device'));
        if ($device === '') {
            throw new Exception('Geräte-ID fehlt.');
        }
        $body = [
            'requestId' => sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6))),
            'payload' => [
                'sku' => 'H7076',
                'device' => $device,
                'capability' => ['type' => $type, 'instance' => $instance, 'value' => $value]
            ]
        ];
        $this->ApiRequest('POST', '/device/control', $body);
    }

    private function ApiRequest(string $method, string $path, ?array $body = null): array
    {
        $key = trim($this->ReadPropertyString('APIKey'));
        if ($key === '') {
            throw new Exception('API-Key fehlt.');
        }
        $ch = curl_init(self::API . $path);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Govee-API-Key: ' . $key, 'Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false) {
            throw new Exception('Netzwerkfehler: ' . $error);
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new Exception('Ungültige API-Antwort (HTTP ' . $http . ').');
        }
        if ($http !== 200 || (int)($decoded['code'] ?? -1) !== 200) {
            throw new Exception('Govee API-Fehler (HTTP ' . $http . ', Code ' . ($decoded['code'] ?? '?') . '): ' . ($decoded['msg'] ?? 'Unbekannt'));
        }
        return $decoded;
    }
}
