<?php

declare(strict_types=1);
    class TronityDevice extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->ConnectParent('{AB3EC6A5-231D-0B9C-BCFE-46906BE3E434}');

            //Properties
            $this->RegisterPropertyString('VehicleID', '');
            $this->RegisterPropertyString('VehicleName', '');
            $this->RegisterPropertyInteger('QueryInterval', 60); //30min

            //Profiles
            if (!IPS_VariableProfileExists('TRON.Kilometer')) {
                IPS_CreateVariableProfile('TRON.Kilometer', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileText('TRON.Kilometer', '', 'km');
            }
            if (!IPS_VariableProfileExists('TRON.Plugged')) {
                IPS_CreateVariableProfile('TRON.Plugged', VARIABLETYPE_BOOLEAN);
                IPS_SetVariableProfileAssociation('TRON.Plugged', true, $this->Translate('Plugged'), 'Plug', 0x00FF00);
                IPS_SetVariableProfileAssociation('TRON.Plugged', false, $this->Translate('Unplugged'), 'Plug', 0x00FF00);
            }
            if (!IPS_VariableProfileExists('TRON.Position')) {
                IPS_CreateVariableProfile('TRON.Position', VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileText('TRON.Position', '', '°');
            }
            if (!IPS_VariableProfileExists('TRON.Time')) {
                IPS_CreateVariableProfile('TRON.Time', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileText('TRON.Time', '', $this->Translate('seconds'));
            }
            //Profiles
            if (!IPS_VariableProfileExists('TRON.Status')) {
                IPS_CreateVariableProfile('TRON.Status', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileAssociation('TRON.Status', 200, $this->Translate('All right'), '', 0x00FF00);
                IPS_SetVariableProfileAssociation('TRON.Status', 400, $this->Translate('The credentials are not valid'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Status', 401, $this->Translate('The provided token is incorrect'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Status', 403, $this->Translate('Insufficient permissions'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Status', 500, $this->Translate('The server experienced an unexpected error'), '', 0xFF0000);
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
            }

            //Variables
            $this->RegisterVariableInteger('Status', 'Status ', 'TRON.Status', 0);
            $this->RegisterVariableInteger('Odometer', $this->Translate('Odometer'), 'TRON.Kilometer', 1);
            $this->RegisterVariableInteger('Range', $this->Translate('Range'), 'TRON.Kilometer', 2);
            $this->RegisterVariableInteger('Level', $this->Translate('Battery Level'), '~Battery.100', 3);
            $this->RegisterVariableString('Charging', $this->Translate('Charging Status'), 'TRON.Charging', 4);
            $this->RegisterVariableInteger('ChargeRemainTime', $this->Translate('Charging Remain Time'), 'TRON.Time', 5);
            $this->RegisterVariableBoolean('Plugged', $this->Translate('Plugged'), 'TRON.Plugged', 6);
            $this->RegisterVariableInteger('ChargerPower', $this->Translate('Charger Power'), '', 7);
            $this->RegisterVariableFloat('Latitude', $this->Translate('Latitude'), 'TRON.Position', 8);
            $this->RegisterVariableFloat('Longitude', $this->Translate('Longitude'), 'TRON.Position', 9);
            $this->RegisterVariableInteger('Timestamp', $this->Translate('Timestamp'), '~UnixTimestamp', 10);
            $this->RegisterVariableInteger('LastUpdate', $this->Translate('Last Update'), '~UnixTimestamp', 11);

            $this->RegisterTimer('QueryTimer', 0, 'TRON_RequestLastRecord($_IPS[\'TARGET\']);');
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

            $this->RequestLastRecord();
            $this->SetTimerInterval('QueryTimer', $this->ReadPropertyInteger('QueryInterval') * 60 * 1000);
        }

        public function RequestLastRecord(): string
        {

            /**
             * Get the last record of an single car
             * and set the variables
             */
            $response = json_decode($this->SendDataToParent(
                json_encode([
                    'DataID'        => '{6254260D-00B7-6054-F4CB-00CBA128A988}',
                    'Buffer'        => json_encode([
                        'RequestMethod' => 'GET',
                        'RequestURL'    => 'https://api.tronity.tech/tronity/vehicles/' . $this->ReadPropertyString('VehicleID') . '/last_record',
                        'RequestData'   => ''
                    ])
                ])
            ), true);
            $this->SetValue('Status', array_key_exists('statusCode', $response) ? $response['statusCode'] : 200); //Set value for the individuell car

            if (!array_key_exists('statusCode', $response)) {
                $this->SetValue('Odometer', $response['odometer']);
                $this->SetValue('Range', $response['range']);
                $this->SetValue('Level', $response['level']);
                $this->SetValue('Charging', $response['charging']);
                $this->SetValue('ChargeRemainTime', $response['chargeRemainingTime']);
                $this->SetValue('Plugged', $response['plugged']);
                $this->SetValue('ChargerPower', $response['chargerPower']);
                $this->SetValue('Latitude', $response['latitude']);
                $this->SetValue('Longitude', $response['longitude']);
                $this->SetValue('Timestamp', $response['timestamp'] / 1000); //It is in Millisecs
                $this->SetValue('LastUpdate', $response['lastUpdate'] / 1000); //It is in Millisecs

                $this->SetTimerInterval('QueryTimer', $this->ReadPropertyInteger('QueryInterval') * 60 * 1000);
                return $this->Translate('Finished');
            } else {
                switch ($response['statusCode']) {
                    case 400:
                    case 401:
                        $this->SetStatus(201); //Failed credentials or incorrect token

                        break;
                    case 500:
                        $this->SetStatus(202); //Unexpected server error
                        break;
                    default:
                        $this->SetStatus(200); //Unexpected error
                        break;
                }
                $this->SetTimerInterval('QueryTimer', 0);
                return $this->Translate($response['message']);
            }
        }

        public function StartCharging(): string
        {
            $response = json_decode($this->SendDataToParent(
                json_encode([
                    'EventID'       => 0,
                    'DataID'        => '{6254260D-00B7-6054-F4CB-00CBA128A988}',
                    'Buffer'        => json_encode([
                        'RequestMethod' => 'GET',
                        'RequestURL'    => 'https://api.tronity.tech/tronity/vehicles/' . $this->ReadPropertyString('VehicleID') . '/control/start_charging',
                        'RequestData'   => ''
                    ])
                ])
            ), true);

            $this->SendDebug('Response', print_r($response, true), 0);
            if ($response['statusCode'] == 200) {
                return $this->Translate('Start Charging succeed.');
            } else {
                return $response['message'];
            }
        }

        public function StopCharging(): string
        {
            $response = json_decode($this->SendDataToParent(
                json_encode([
                    'EventID'       => 0,
                    'DataID'        => '{6254260D-00B7-6054-F4CB-00CBA128A988}',
                    'Buffer'        => json_encode([
                        'RequestMethod' => 'GET',
                        'RequestURL'    => 'https://api.tronity.tech/tronity/vehicles/' . $this->ReadPropertyString('VehicleID') . '/control/stop_charging',
                        'RequestData'   => ''
                    ])
                ])
            ), true);

            $this->SendDebug('Response', print_r($response, true), 0);
            if ($response == 200) {
                return $this->Translate('Stop Charging succeed.');
            } else {
                return $response['message'];
            }
        }
    }