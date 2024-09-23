<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\InfoUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

class WeatherController extends AbstractController
{
    /**
     *  affiche les informations météo du lieu de résidencede l'utilisateur
     *
     * @param SerializerInterface $serializer
     * @param HttpClientInterface $httpClient
     * @return JsonResponse
     */
    #[Route('api/meteo', name: 'app_weather', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function yourWeather(SerializerInterface $serializer, HttpClientInterface $httpClient): JsonResponse
    {
        $currentUser = $this->getUser();
        $infoUser = $currentUser->getInfoUser();

        if($infoUser == null){
            return new JsonResponse(['message' => 'Les informations de votre compte ne sont pas disponibles'], 400);
        }
        $zipcode = $infoUser->getZipCode();
        $country = $infoUser->getCountry();

        if($zipcode == null || $country == null){
            return new JsonResponse(['message' => 'Les informations de localisation de votre compte ne sont pas disponibles'], 400);
        }

        // récupération de la latitude et longitude
        $response = $httpClient->request(
            'GET',
            'http://api.openweathermap.org/geo/1.0/zip?zip=' . $zipcode . ',' . $country .'&appid=' . $this->getParameter('WEATHER_API_KEY'),
        );
        $jsonreponse = $response->getContent();
        $location = $serializer->deserialize($jsonreponse, Location::class, 'json');

        // mise en forme de la nouvelle requette API
        $response = $httpClient->request(
            'GET',
            'http://api.openweathermap.org/data/3.0/onecall?lat=' . $location->getLat() . '&lon=' . $location->getLon() . '&appid=' . $this->getParameter('WEATHER_API_KEY'),
         );
         $formatData = "{\n Ville:" . $infoUser->getCity() ."\n ZipCode:" . $infoUser->getZipCode() . "\n Country:" . $infoUser->getCountry() . "\n}\n";
         return new JsonResponse( $formatData . $response->getContent(), $response->getStatusCode(), [], true);
    }

    /**
     *  affiche les informations météo du lieu demandé par son code postal et son pays
     *
     * @param int $zipcode
     * @param string $country
     * @param SerializerInterface $serializer
     * @param HttpClientInterface $httpClient
     * @return JsonResponse
     */
    #[Route('api/meteo/{zipcode}', name: 'app_weather_zipcode', methods: ['GET'])]
    public function zipcodeWeather(int $zipcode, SerializerInterface $serializer, HttpClientInterface $httpClient): JsonResponse
    {
        $country='FR';
        // récupération de la latitude et longitude
        $response = $httpClient->request(
            'GET',
            'http://api.openweathermap.org/geo/1.0/zip?zip=' . $zipcode . ',' . $country .'&appid=' . $this->getParameter('WEATHER_API_KEY'),
        );
        $jsonreponse = $response->getContent();
        $location = $serializer->deserialize($jsonreponse, Location::class, 'json');

        // mise en forme de la nouvelle requette API
        $response = $httpClient->request(
            'GET',
            'http://api.openweathermap.org/data/3.0/onecall?lat=' . $location->getLat() . '&lon=' . $location->getLon() . '&appid=' . $this->getParameter('WEATHER_API_KEY'),
         );

         $formatData = "{\n  ZipCode:" . $zipcode . "\n Country:" . $country . "\n}\n";
         return new JsonResponse( $formatData . $response->getContent(), $response->getStatusCode(), [], true);
    }
}
