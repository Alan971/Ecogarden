<?php

namespace App\Handler;

use App\Entity\Location;
use App\Entity\Weather;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherHandler
{
    public function __construct(
    private SerializerInterface $serializer,
    private HttpClientInterface $httpClient
    )
    {
        $this->httpClient = $httpClient;
    }

    /**
     *  affiche les informations météo du lieu de résidencede l'utilisateur
     *
     * @param SerializerInterface $serializer
     * @param HttpClientInterface $httpClient
     * @param string $apiKey
     * @return JsonResponse
     */
    public function yourWeather($infoUser, $zipCodeRequested, $apiKey): array
    {
        if (!$zipCodeRequested){
            if (!$infoUser){
                return ['message' => 'Les informations de votre compte ne sont pas disponibles', 'status' => 400];
            }
            $zipcode = $infoUser->getZipCode();
            $country = $infoUser->getCountry();
            if ($zipcode == null || $country == null){
                return ['message' => 'Les informations de localisation de votre compte ne sont pas disponibles', 'status' => 400];
            }
        }
        else {
            $country='FR';
            $zipcode = $zipCodeRequested;
            if (!is_int($zipcode) || $zipcode < 1000 || $zipcode > 100000){
                return ['message' => 'Le code postal '.$zipcode.' n\'est pas valide', 'status' => 400];
            }
        }
        // récupération de la latitude et longitude
        try {
            $response = $this->httpClient->request(
                'GET',
                'http://api.openweathermap.org/geo/1.0/zip?zip=' . $zipcode . ',' . $country .'&appid=' . $apiKey,
            );
            $jsonreponse = $response->getContent();
            $location = $this->serializer->deserialize($jsonreponse, Location::class, 'json');
        } catch (\Exception $e) {
            return ['message' => 'Une erreur est survenue lors de la récupération de la localisation, vérifiez votre code postal : '.$zipcode, 'status' => 400];
        }

        // mise en forme de la nouvelle requette API
        $response = $this->httpClient->request(
            'GET',
            'http://api.openweathermap.org/data/3.0/onecall?lat=' . $location->getLat() . '&lon=' . $location->getLon() . '&units=metric&lang=FR'. '&appid=' . $apiKey,
         );

        //  formatage de la réponse
        $data = ['ZipCode' => $zipcode, 'Country' => $country];
        //  formatage arbitraire de la réponse
        $weather = $this->serializer->deserialize($response->getContent(), Weather::class, 'json');
        $data['Température'] = $weather->getCurrent()['temp'] ." °C";
        $data['Pression'] = $weather->getCurrent()['pressure'] ." hPa";
        $data['Humidité'] = $weather->getCurrent()['humidity'] ."%";
        $data['Vent'] = $weather->getCurrent()['wind_speed'] ." m/s";
        $data['Description'] = $weather->getCurrent()['weather'][0]['description'];

        return ['message' => $data , 'status' =>$response->getStatusCode() ];
    }
}