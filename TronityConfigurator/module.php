<?php

declare(strict_types=1);
class TronityConfigurator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RequireParent('{46826B03-733A-17E8-72E8-1ABBB0FF7608}');
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

        $vehicles = $this->Request('/tronity/vehicles');

        $vehicleInstances = IPS_GetInstanceListByModuleID('{D041B19E-6D70-A84F-B67C-4FF51CA38D3A}');

        $availableVehicles = [];
        foreach ($vehicles['data'] as $vehicle) {
            $instanceID = $this->searchID($vehicle['id']);
            $vehicleInstances = array_diff($vehicleInstances, [$instanceID]);

            $this->SendDebug('Vehicle', print_r($vehicle, true), 0);
            $availableVehicles[] =
                [
                    'vid'         => $vehicle['id'],
                    'name'        => $instanceID ? IPS_GetName($instanceID) : $vehicle['displayName'],
                    'instanceID'  => $instanceID,
                    'create'      => [
                        'moduleID'      => '{D041B19E-6D70-A84F-B67C-4FF51CA38D3A}', //TronityDevice
                        'configuration' => [
                            'VehicleID'        => $vehicle['id'],
                        ]
                    ]
                ];
        }

        //search the module instance
        foreach ($vehicleInstances as $instanceID) {
            $availableVehicles[] =
                [
                    'vid'         => IPS_GetProperty($instanceID, 'VehicleID'),
                    'name'        => IPS_GetName($instanceID),
                    'instanceID'  => $instanceID,
                ];
        }

        $form['actions'][0]['values'] = $availableVehicles;
        return json_encode($form);
    }

    private function Request($endpoint)
    {
        //get the list of vehicle
        $data = json_encode([
            'DataID'        => '{63CE9905-4ED9-8E7E-2359-6FFD9D85B407}',
            'Buffer'        => json_encode([
                'RequestMethod' => 'GET',
                'RequestURL'    => $endpoint,
                'RequestData'   => ''
            ])
        ]);
        return json_decode($this->SendDataToParent($data), true);
    }

    private function searchID($vehicleID): int
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