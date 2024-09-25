<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Hubspots extends Controller
{
    public function webhook(Request $req)
    {
        $res = $req->all();

        foreach ($res as $deal) {
            Log::info($deal);
            $dealId = $deal['objectId'];
            $curl = curl_init();
            
            $authorizationToken = env("HUBSPOT_ACCESS",' ');
            // Set cURL options
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.hubapi.com/crm/v3/objects/deals/" . $dealId . "?properties=status,closedate,amount,dealname,state,loan_type",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    "Authorization: Bearer " . $authorizationToken
                ),
            ));

            // Execute the cURL request
            $response = curl_exec($curl);

            // Close the cURL session
            curl_close($curl);

            $this->createHubdbRecords($response);
        }
    }
    public function createHubdbRecords($data)
    {

        $data = json_decode($data, true);
        $deal_id = $data['id'];
        $searchDeal = $this->searchhubdbRecord($data['properties']['dealname']);
        $imagedetails = $this->getImageURL($deal_id);
        
        if (isset($imagedetails)) {
            $imageurl = $imagedetails;

            $res = json_decode($searchDeal, true);
            if ($res['results'] == 0) {
                $curl = curl_init();
                $tableId = 28045995; // Replace with your table ID
                $rowId = 27843717; // Replace with your row ID
                $authorizationToken = env("HUBSPOT_ACCESS",' ');
                $sendData = [
                    'values' => [ // Ensure you're using 'values' key
                        'deal_name' => $data['properties']['dealname'],
                        'loan_type' => $data['properties']['loan_type'],
                        'state' => $data['properties']['state'],
                        'loan_amount' => (int)$data['properties']['amount'],
                        'close_date' => (int)$data['properties']['closedate'],
                        'thumbnail' => [
                            'url' => $imageurl,
                            'width' => 683,
                            'height' => 512,
                            'altText' => $data['properties']['dealname'],
                            'fileId' => (int)$data['id'],
                            'type' => 'image'
                        ],
                        'stage' => [
                            'id' => '10',
                            'name' => $data['properties']['status'],
                            'label' => $data['properties']['status'],
                            'type' => 'option'
                        ]
                    ]
                ];
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.hubapi.com/cms/v3/hubdb/tables/$tableId/rows",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS =>  json_encode($sendData),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        "Authorization: Bearer " . $authorizationToken
                    ),
                ));

                // Execute the cURL request
                $response = curl_exec($curl);

                // Close the cURL session
                curl_close($curl);
                // Log::info($response);
                $res = json_decode($response, true);

                return $response;
            } else {

                
                $res = json_decode($searchDeal, true);
                $rowId = $res['results'][0]['id'];
                $curl = curl_init();
                $tableId = 28045995; // Replace with your table ID
                // $rowId = 27843717; // Replace with your row ID
                $authorizationToken = env("HUBSPOT_ACCESS",' ');
                $sendData = [
                    'values' => [ // Ensure you're using 'values' key
                        'deal_name' => $data['properties']['dealname'],
                        'loan_type' => $data['properties']['loan_type'],
                        'state' => $data['properties']['state'],
                        'loan_amount' => (int)$data['properties']['amount'],
                        'close_date' => (int)$data['properties']['closedate'],
                        'thumbnail' => [
                            'url' => $imageurl,
                            'width' => 683,
                            'height' => 512,
                            'altText' => $data['properties']['dealname'],
                            'fileId' => (int)$data['id'],
                            'type' => 'image'
                        ],
                        'stage' => [
                            'id' => '10',
                            'name' => $data['properties']['status'],
                            'label' => $data['properties']['status'],
                            'type' => 'option'
                        ]
                    ]
                ];
                Log::info($sendData);
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.hubapi.com/cms/v3/hubdb/tables/$tableId/rows/$rowId/draft",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'PATCH',
                    CURLOPT_POSTFIELDS =>  json_encode($sendData),
                    CURLOPT_HTTPHEADER => array(
                        'Content-Type: application/json',
                        "Authorization: Bearer " . $authorizationToken
                    ),
                ));

                // Execute the cURL request
                $response = curl_exec($curl);

                // Close the cURL session
                curl_close($curl);
                Log::info($response);
                $res = json_decode($response, true);

                return $response;
            }
        } else {
            Log::info("Image Not found");
        }
    }
    public function searchhubdbRecord($data)
    {
        $curl = curl_init();
        $authorizationToken = env("HUBSPOT_ACCESS",' ');
        $tableId = 28045995; // Replace with your table ID

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.hubapi.com/cms/v3/hubdb/tables/' . $tableId . '/rows?deal_name=' . urlencode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization: Bearer " . $authorizationToken
            ),
        ));
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            Log::info(curl_error($curl));
            return false;
        } else {

            
            return $response;
        }
    }

    public function getImageURL($deal_id)
    {

        $authorizationToken = env("HUBSPOT_ACCESS",' ');
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.hubapi.com/crm-associations/v1/associations/' . $deal_id . '/HUBSPOT_DEFINED/11',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$authorizationToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $notes = json_decode($response, true);
        if (!empty($notes['results'])) {

            foreach ($notes['results'] as  $note) {

                Log::info($note);
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.hubapi.com/engagements/v1/engagements/' . $note,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$authorizationToken
                    ),
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                Log::info($response);
                $files = json_decode($response, true);
                if (isset($files['attachments'])) {


                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://api.hubapi.com/files/v3/files/'.$files['attachments'][0]['id'],
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: Bearer '.$authorizationToken
                        ),
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    $file = json_decode($response, true);
                    return $file['url'];
                } else {
                    Log::info("Attachemnt Not found");
                    continue;
                }
            }
        } else {
            Log::info("Notes not found");
        }
    }
}
