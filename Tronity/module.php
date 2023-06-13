<?php

declare(strict_types=1);
    class Tronity extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            //Properties
            $this->RegisterPropertyString('CarIDs', '[]');
            $this->RegisterPropertyInteger('QueryInterval', 0);
            $this->RegisterPropertyString('ClientID', '');
            $this->RegisterPropertyString('ClientSecret', '');

            //Timer
            $this->RegisterTimer('QueryTimer', 0, "TRON_RequestLastRecords(\$_IPS['TARGET']);");

            //Profiles
            if (!IPS_VariableProfileExists('TRON.Status')) {
                IPS_CreateVariableProfile('TRON.Status', VARIABLETYPE_INTEGER);
                IPS_SetVariableProfileAssociation('TRON.Status', 200, $this->Translate('All right'), '', 0x00FF00);
                IPS_SetVariableProfileAssociation('TRON.Status', 400, $this->Translate('The credentials are not valid'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Status', 401, $this->Translate('The provided token is incorrect'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Status', 403, $this->Translate('Insufficient permissions'), '', 0xFF0000);
                IPS_SetVariableProfileAssociation('TRON.Status', 500, $this->Translate('The server experienced an unexpected error'), '', 0xFF0000);
            }
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

            //Valid credentials
            if (!$this->isAuthTokenAvailable()) {
                $this->SetStatus(204);
                return;
            }
            $this->SetStatus(102);

            //Get all cars that are in the list
            $carIDList = json_decode($this->ReadPropertyString('CarIDs'), true);
            foreach ($carIDList as $row => $car) {
                if (!IPS_VariableExists(@$this->GetIDForIdent($car['CarID'] . '_Status'))) {
                    $status = $this->NewVehicle($car['CarID'], $car['CarName'], $row * 20);
                    $carIDList[$row]['Status'] = $status;
                    $carIDList[$row]['rowColor'] = $status != 'OK' ?? '#FFC0C0'; //Red
                }
            }

            //Tidy up the Cars that are not in the list
            $children = IPS_GetChildrenIDs($this->InstanceID);
            $carIDs = array_column($carIDList, 'CarID');
            foreach ($children as $child) {
                $carID = '';
                if (IPS_VariableExists($child)) {
                    $ident = IPS_GetObject($child)['ObjectIdent'];
                    $carID = substr($ident, 0, strpos($ident, '_'));
                }
                if (!in_array($carID, $carIDs)) {
                    $this->DeleteVehicle($carID);
                }
            }

            //Set the car variables
            $this->RequestLastRecords();
        }

        public function FetchAvailableCars(): void
        {
            /**
             * Fetch the cars that could be assigned to IPS
             */
            $url = 'https://api.tronity.tech/tronity/vehicles/';
            if (!$this->isAuthTokenAvailable()) {
                $this->UpdateFormField('TotalIDs', 'caption', 'Something is wrong (Auth)');
                return;
            }

            $context = [
                'http' => [
                    'method'        => 'GET',
                    'header'        => "Content-Type: application/json\r\nAuthorization: bearer " . $this->GetBuffer('authToken'),
                    'ignore_errors' => true,
                ]
            ];
            $response = $this->requestData($url, $context);
            if (array_key_exists('statusCode', $response)) {
                $this->UpdateFormField('TotalIDs', 'caption', 'Something ist wrong. The Message is: ' . $response['message']);
                return;
            }

            $this->UpdateFormField('TotalIDs', 'caption', $this->Translate('There are ' . $response['total'] . ' vehicles'));
            $cars = [];
            foreach ($response['data'] as $vehicle) {
                /**
                 * Response Date is:
                 * The Data is an array Array []
                 * id: required string
                 * scopes: required Array of strings
                 *     Items Enum: "read_battery" "read_location" "read_vehicle_info" "read_odometer" "read_vin" "read_trips" "read_charges" "read_charge" "read_sleeps" "read_idles" "read_records" "write_trips" "write_charges" "write_sleeps" "write_idles" "write_records_details" "write_charge_start_stop" "write_wake_up" "tronity_vehicle_data" "tronity_charges" "tronity_control_charging" "tronity_location" "tronity_soc" "tronity_range" "tronity_odometer" "tronity_charging" "tronity_factsheet"
                 * createdAt: required string <date-time>
                 * updatedAt: required string <date-time>
                 */
                $cars[] = ['CarID' => $vehicle['id']];
            }
            $this->UpdateFormField('AvailableCarIDs', 'values', json_encode($cars));
        }

        public function RequestLastRecords(): void
        {
            /**
             * Get the last Record of all registered cars
             */
            $carIDList = json_decode($this->ReadPropertyString('CarIDs'), true);
            foreach ($carIDList as $carRow) {
                $this->RequestLastRecord($carRow['CarID']);
            }

            $this->SetTimerInterval('QueryTimer', $this->ReadPropertyInteger('QueryInterval'));
            return;
        }

        public function RequestLastRecord(string $carID): void
        {
            /**
             * Get the last record of an single car
             * and set the variables
             */

            //get the AuthToken
            if (!$this->isAuthTokenAvailable()) {
                return;
            }

            $context = [
                'http' => [
                    'method'        => 'GET',
                    'header'        => "Content-Type: application/json\r\nAuthorization: bearer " . $this->GetBuffer('authToken'),
                    'ignore_errors' => true,
                ]
            ];
            $response = $this->requestData('https://api.tronity.tech/tronity/vehicles/' . $carID . '/last_record', $context);

            $this->SetValue($carID . '_Status', array_key_exists('statusCode', $response) ? $response['statusCode'] : 200); //Set value for the individuell car
            if (!array_key_exists('statusCode', $response)) {
                $this->SetValue($carID . '_Odometer', $response['odometer']);
                $this->SetValue($carID . '_Range', $response['range']);
                $this->SetValue($carID . '_Level', $response['level']);
                $this->SetValue($carID . '_Charging', $response['charging']);
                $this->SetValue($carID . '_ChargeRemainTime', $response['chargeRemainTime']);
                $this->SetValue($carID . '_Plugged', $response['plugged']);
                $this->SetValue($carID . '_ChargerPower', $response['chargerPower']);
                $this->SetValue($carID . '_Latitude', $response['latitude']);
                $this->SetValue($carID . '_Longitude', $response['longitude']);
                $this->SetValue($carID . '_Timestamp', $response['timestamp'] / 1000); //It is in Millisecs
                $this->SetValue($carID . '_LastUpdate', $response['lastUpdate'] / 1000); //It is in Millisecs
            } else {
                switch ($response['statusCode']) {
                    case 400:
                    case 401:
                        $this->SetStatus(204); //Failed credentials or incorrect token
                        break;
                    case 500:
                        $this->SetStatus(203); //Unexpected server error
                        break;
                    default:
                        $this->SetStatus(200); //Unexpected error
                        break;
                }
            }
        }

        public function AuthenticateUser(string $clientID, string $clientSecret): int
        {
            //Get the Access Token from the Tronity Platform
            $data = ['client_id' => $clientID, 'client_secret' => $clientSecret, 'grant_type' => 'app'];

            $context = [
                'http' => [
                    'method'        => 'POST',
                    'content'       => json_encode($data),
                    'header'        => "Content-Type: application/json\r\n",
                    'ignore_errors' => true,
                ]
            ];
            $response = $this->requestData('https://api.tronity.tech/authentication', $context);

            //Evaluation of the response
            if (array_key_exists('statusCode', $response)) {
                switch ($response['statusCode']) {
                case 401:
                        $this->UpdateFormField('AuthStatus', 'caption', $this->Translate('The provided credential are incorrect'));
                    return 204;
                    break;
                case 500:
                        $this->UpdateFormField('AuthStatus', 'caption', $this->Translate('The server experienced an unexpected error'));
                    return 203;
                    break;
                default:
                        $this->UpdateFormField('AuthStatus', 'caption', sprintf($this->Translate('Status code: %u is not supported'), $response['statusCode']));
                    return 200;
                    break;
                }
            } else {
                $this->UpdateFormField('AuthStatus', 'caption', $this->Translate('The authentication is succeed'));
            }

            $this->SetBuffer('authToken', $response['access_token']);
            $this->SetBuffer('expiresAt', time() + $response['expires_in']);
            return 102;
        }

        private function isAuthTokenAvailable(): bool
        {
            if ($this->GetBuffer('expiresAt') == '' || time() > $this->GetBuffer('expiresAt')) {
                $status = $this->AuthenticateUser($this->ReadPropertyString('ClientID'), $this->ReadPropertyString('ClientSecret'));
                if ($status != 102) {
                    $this->SetStatus($status);
                    return false;
                }
            }
            return true;
        }

        private function NewVehicle($newCarID, $newCarName, $startPosition): string //Startposition should be in 20 steps
        {
            //If a new car is added to the list, check if it is available

            //Valid the Vehicle
            $url = 'https://api.tronity.tech/tronity/vehicles/' . $newCarID;
            if (!$this->isAuthTokenAvailable()) {
                return 'Authentication is broken';
            }

            $context = [
                'http' => [
                    'method'        => 'GET',
                    'header'        => "Content-Type: application/json\r\nAuthorization: bearer " . $this->GetBuffer('authToken'),
                    'ignore_errors' => true,
                ]
            ];
            $response = $this->requestData($url, $context);
            if (array_key_exists('statusCode', $response)) {
                return 'Something wrong. Message: ' . $response['message'];
            }

            /**
             * Needed variable for one car:
             * Status of the last change -> Status code of the last request on 'LastValues'
             * Odometer : number
             * Range : number
             * Level : number
             * Charging : String
             * chargeRemainingTime	: number
             * plugged	: boolean
             * chargerPower	:number
             * latitude : number
             * longitude : number
             * timestamp : number
             * lastUpdate : number
             */
            if ($newCarName == '') {
                $newCarName = $newCarID;
            }

            $this->RegisterVariableInteger($newCarID . '_Status', 'Status ' . $newCarName, 'TRON.Status', $startPosition);
            $this->RegisterVariableInteger($newCarID . '_Odometer', $this->Translate('Odometer') . ' ' . $newCarName, '', $startPosition + 1); //TODO Profil km?
            $this->RegisterVariableInteger($newCarID . '_Range', $this->Translate('Range') . ' ' . $newCarName, '', $startPosition + 2);
            $this->RegisterVariableInteger($newCarID . '_Level', $this->Translate('Level') . ' ' . $newCarName, '', $startPosition + 2);
            $this->RegisterVariableString($newCarID . '_Charging', $this->Translate('Charging') . ' ' . $newCarName, '', $startPosition + 3); //TODO: Enums
            $this->RegisterVariableInteger($newCarID . '_ChargeRemainTime', $this->Translate('Charging Remain Time') . ' ' . $newCarName, '', $startPosition + 4); //TODO Profil mit Suffix Min oder sec?
            $this->RegisterVariableBoolean($newCarID . '_Plugged', $this->Translate('Plugged') . ' ' . $newCarName, '', $startPosition + 5);
            $this->RegisterVariableInteger($newCarID . '_ChargerPower', $this->Translate('Charger Power') . ' ' . $newCarName, '', $startPosition + 6);
            $this->RegisterVariableInteger($newCarID . '_Latitude', $this->Translate('Latitude') . ' ' . $newCarName, '', $startPosition + 7); //TODO Profil?
            $this->RegisterVariableInteger($newCarID . '_Longitude', $this->Translate('Longitude') . ' ' . $newCarName, '', $startPosition + 8); //TODO Profil?
            $this->RegisterVariableInteger($newCarID . '_Timestamp', $this->Translate('Timestamp') . ' ' . $newCarName, '~UnixTimestamp', $startPosition + 9);
            $this->RegisterVariableInteger($newCarID . '_LastUpdate', $this->Translate('Last Update') . ' ' . $newCarName, '~UnixTimestamp', $startPosition + 10);

            $this->RequestLastRecord($newCarID);

            return 'OK';
        }

        private function DeleteVehicle($carID): void
        {
            //Delete the Variables of the car
            $this->UnregisterVariable($carID . '_Status');
            $this->UnregisterVariable($carID . '_Odometer');
            $this->UnregisterVariable($carID . '_Range');
            $this->UnregisterVariable($carID . '_Level');
            $this->UnregisterVariable($carID . '_Charging');
            $this->UnregisterVariable($carID . '_ChargeRemainTime');
            $this->UnregisterVariable($carID . '_Plugged');
            $this->UnregisterVariable($carID . '_ChargerPower');
            $this->UnregisterVariable($carID . '_Latitude');
            $this->UnregisterVariable($carID . '_Longitude');
            $this->UnregisterVariable($carID . '_Timestamp');
            $this->UnregisterVariable($carID . '_LastUpdate');
        }

        private function requestData(string $url, array $context)
        {
            $this->SendDebug('Request on ' . $url . 'with', print_r($context, true), 0); //TODO: Test with real vehicle is not executed until now
            $context = stream_context_create($context);
            $response = file_get_contents($url, false, $context);
            $this->SendDebug('Response', print_r($response, true), 0); //TODO: Test with real vehicle is not executed until now
            return json_decode($response, true);
        }

        private function GetObjectIdent($id): string
        {
            if (IPS_VariableExists($id)) {
                $object = IPS_GetObject($id);
                $ident = $object['ObjectIdent'];
                return substr($ident, 0, strpos($ident, '_'));
            }
            return '';
        }
    }