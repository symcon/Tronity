<?php

declare(strict_types=1);
    class TronitySplitter extends IPSModule
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

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
        }

        public function ForwardData($JSONString)
        {

            // Empfangene Daten von der Device Instanz
            $data = json_decode($JSONString);

            // Weiterleiten zur I/O Instanz
            $result = $this->SendDataToParent(json_encode(['DataID' => '{63CE9905-4ED9-8E7E-2359-6FFD9D85B407}', 'Buffer' => $data->Buffer]));

            $this->SendDebug('Result', $result, 0);
            // Weiterverarbeiten und durchreichen zum Device
            return $result;
        }

        public function ReceiveData($JSONString)
        {
            // Empfangene Daten vom I/O
            $data = json_decode($JSONString, true);
            $this->SendDebug('ReceiveData', print_r($data['Buffer']), 0);

            // Weiterleitung zu allen Gerät-/Device-Instanzen
            $this->SendDataToChildren(json_encode(['DataID' => '{A3079924-2CCF-03ED-BAEA-2EBCE72F7613}', 'Buffer' => $data['Buffer']]));
        }
    }