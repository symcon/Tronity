<?php

declare(strict_types=1);
class TronityConfigurator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RequireParent('{AB3EC6A5-231D-0B9C-BCFE-46906BE3E434}');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function GetConfigurationForm(): string
    {
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if (!$this->HasActiveParent()) {
            return json_encode($form);
        }

        //get the list of vehicle
        $data = json_encode([
            'DataID'        => '{6254260D-00B7-6054-F4CB-00CBA128A988}',
            'Buffer'        => json_encode([
                'RequestMethod' => 'GET',
                'RequestURL'    => 'https://api.tronity.tech/tronity/vehicles',
                'RequestData'   => ''
            ])
        ]);
        $vehicles = json_decode($this->SendDataToParent($data), true);
        $vehicleInstances = IPS_GetInstanceListByModuleID('{D041B19E-6D70-A84F-B67C-4FF51CA38D3A}');

        $availableVehicles = [];
        $vehicles = !array_key_exists('statusCode', $vehicles) ? $vehicles['data'] : [];
        foreach ($vehicles as $key => $vehicle) {
            $instanceID = $this->searchID($vehicle['id']);
            $vehicleInstances = array_diff($vehicleInstances, [$instanceID]);

            $this->SendDebug('Vehicle', print_r($vehicle, true), 0);
            $availableVehicles[] =
                [
                    'VehicleID'   => strval($vehicle['id']),
                    'VehicleName' => strval($vehicle['displayName']),
                    'instanceID'  => $instanceID,
                    'create'      => [
                        'moduleID'      => '{D041B19E-6D70-A84F-B67C-4FF51CA38D3A}', //TronityDevice
                        'configuration' => [
                            'VehicleID'        => $vehicle['id'],
                            'VehicleName'      => strval($vehicle['displayName']),
                        ]
                    ]
                ];
        }

        //search the module instance
        foreach ($vehicleInstances as $instanceID) {
            $vehicleID = IPS_GetProperty($instanceID, 'VehicleID');
            $vehicleName = IPS_GetProperty($instanceID, 'VehicleName');
            //Vehicles available and have an id
            $availableVehicles[] =
                [
                    'VehicleID'   => strval($vehicleID),
                    'VehicleName' => strval($vehicleName),
                    'instanceID'  => $instanceID,
                ];
        }

        $form['actions'][0]['values'] = $availableVehicles;
        return json_encode($form);
    }

    public function searchID($vehicleID): int
    {
        $ids = IPS_GetInstanceListByModuleID('{D041B19E-6D70-A84F-B67C-4FF51CA38D3A}');
        foreach ($ids as $id) {
            if (IPS_GetProperty($id, 'VehicleID') == $vehicleID) {
                return $id;
            }
        }
        return 0;
    }
}