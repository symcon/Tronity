<?php

declare(strict_types=1);
    class TronityDevice extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->ConnectParent('{46826B03-733A-17E8-72E8-1ABBB0FF7608}');

            //Properties
            $this->RegisterPropertyString('VehicleID', '');
            $this->RegisterPropertyInteger('UpdateInterval', 15); // Minutes

            //Profiles
            if (!IPS_VariableProfileExists('TRON.Odometer')) {
                IPS_CreateVariableProfile('TRON.Odometer', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileText('TRON.Odometer', '', ' km');
                IPS_SetVariableProfileIcon('TRON.Odometer', 'Car');
            }
            if (!IPS_VariableProfileExists('TRON.Range')) {
                IPS_CreateVariableProfile('TRON.Range', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileText('TRON.Range', '', ' km');
                IPS_SetVariableProfileIcon('TRON.Range', 'Distance');
            }
            if (!IPS_VariableProfileExists('TRON.Plugged')) {
                IPS_CreateVariableProfile('TRON.Plugged', VARIABLETYPE_BOOLEAN);
                IPS_SetVariableProfileAssociation('TRON.Plugged', true, $this->Translate('Plugged'), 'Plug', 0x00FF00);
                IPS_SetVariableProfileAssociation('TRON.Plugged', false, $this->Translate('Unplugged'), 'Plug', 0x00FF00);
                IPS_SetVariableProfileIcon('TRON.Plugged', 'Plug');
            }
            if (!IPS_VariableProfileExists('TRON.Position')) {
                IPS_CreateVariableProfile('TRON.Position', VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileDigits('TRON.Position', 3);
                IPS_SetVariableProfileText('TRON.Position', '', '°');
                IPS_SetVariableProfileIcon('TRON.Position', 'Move');
            }
            if (!IPS_VariableProfileExists('TRON.ChargerPower')) {
                IPS_CreateVariableProfile('TRON.ChargerPower', VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileDigits('TRON.ChargerPower', 1);
                IPS_SetVariableProfileText('TRON.ChargerPower', '', ' kW');
                IPS_SetVariableProfileIcon('TRON.ChargerPower', 'EnergyProduction');
            }
            if (!IPS_VariableProfileExists('TRON.Time')) {
                IPS_CreateVariableProfile('TRON.Time', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileText('TRON.Time', '', ' ' . $this->Translate('seconds'));
                IPS_SetVariableProfileIcon('TRON.Time', 'Clock');
            }
            if (!IPS_VariableProfileExists('TRON.Battery')) {
                IPS_CreateVariableProfile('TRON.Battery', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileText('TRON.Battery', '', ' %');
                IPS_SetVariableProfileValues('TRON.Battery', 0, 100, 0);
                IPS_SetVariableProfileIcon('TRON.Battery', 'EnergyStorage');
            }
            if (!IPS_VariableProfileExists('TRON.Charging')) {
                IPS_CreateVariableProfile('TRON.Charging', VARIABLETYPE_STRING);
                IPS_SetVariableProfileAssociation('TRON.Charging', 'Complete', $this->Translate('Complete'), '', 0x00FF00);
                IPS_SetVariableProfileAssociation('TRON.Charging', 'Error', $this->Translate('Error'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Charging', 'NoPower', $this->Translate('No Power'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Charging', 'Starting', $this->Translate('Starting'), '', 0x0000FF);
                IPS_SetVariableProfileAssociation('TRON.Charging', 'Stopped', $this->Translate('Stopped'), '', 0x0000FF);
                IPS_SetVariableProfileAssociation('TRON.Charging', 'Disconnected', $this->Translate('Disconnected'), '', 0x00FF00);
                IPS_SetVariableProfileAssociation('TRON.Charging', 'Charging', $this->Translate('Charging'), '', 0x0000FF);
                IPS_SetVariableProfileIcon('TRON.Charging', 'Network');
            }

            //Variables
            $this->RegisterVariableInteger('Odometer', $this->Translate('Odometer'), 'TRON.Odometer', 1);
            $this->RegisterVariableInteger('Range', $this->Translate('Range'), 'TRON.Range', 2);
            $this->RegisterVariableInteger('Level', $this->Translate('Battery Level'), 'TRON.Battery', 3);
            $this->RegisterVariableString('Charging', $this->Translate('Charging Status'), 'TRON.Charging', 4);
            $this->RegisterVariableInteger('ChargeRemainTime', $this->Translate('Charging Remain Time'), 'TRON.Time', 5);
            $this->RegisterVariableBoolean('Plugged', $this->Translate('Plugged'), 'TRON.Plugged', 6);
            $this->RegisterVariableFloat('ChargerPower', $this->Translate('Charger Power'), 'TRON.ChargerPower', 7);
            $this->RegisterVariableFloat('Latitude', $this->Translate('Latitude'), 'TRON.Position', 8);
            $this->RegisterVariableFloat('Longitude', $this->Translate('Longitude'), 'TRON.Position', 9);
            $this->RegisterVariableInteger('Timestamp', $this->Translate('Timestamp'), '~UnixTimestamp', 10);
            $this->RegisterVariableInteger('LastUpdate', $this->Translate('Last Update'), '~UnixTimestamp', 11);

            $this->RegisterTimer('UpdateTimer', 0, 'TRON_RequestLastRecord($_IPS[\'TARGET\']);');
        }

        public function Destroy()
        {
            //Never delete this line!
            parent::Destroy();
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            $this->SetTimerInterval('UpdateTimer', $this->ReadPropertyInteger('UpdateInterval') * 60 * 1000);
        }

        public function RequestLastRecord(): void
        {
            $response = $this->Request('/last_record');
            $this->SetValue('Odometer', $response['odometer']);
            $this->SetValue('Range', $response['range']);
            $this->SetValue('Level', $response['level']);
            $this->SetValue('Charging', $response['charging']);
            $this->SetValue('ChargeRemainTime', $response['chargeRemainingTime']);
            $this->SetValue('Plugged', $response['plugged']);
            $this->SetValue('ChargerPower', $response['chargerPower']);
            $this->SetValue('Latitude', $response['latitude']);
            $this->SetValue('Longitude', $response['longitude']);
            $this->SetValue('Timestamp', $response['timestamp'] / 1000); // Milliseconds
            $this->SetValue('LastUpdate', $response['lastUpdate'] / 1000); // Milliseconds
        }

        public function StartCharging(): void
        {
            $response = $this->Request('/control/start_charging');
            $this->SendDebug('StartCharging', print_r($response, true), 0);
        }

        public function StopCharging(): void
        {
            $response = $this->Request('/control/start_charging');
            $this->SendDebug('StopCharging', print_r($response, true), 0);
        }

        private function Request($endpoint)
        {
            $data = json_encode([
                'DataID'        => '{63CE9905-4ED9-8E7E-2359-6FFD9D85B407}',
                'Buffer'        => json_encode([
                    'RequestMethod' => 'GET',
                    'RequestURL'    => '/tronity/vehicles/' . $this->ReadPropertyString('VehicleID') . $endpoint,
                    'RequestData'   => ''
                ])
            ]);

            $response = json_decode($this->SendDataToParent($data), true);

            // Throw exception if we have an error
            if (array_key_exists('statusCode', $response)) {
                if ($response['message'] == 'Not Found') {
                    $response['message'] = 'The requested vehicle id could not be found!';
                }
                die($response['message']);
            }

            return $response;
        }
    }