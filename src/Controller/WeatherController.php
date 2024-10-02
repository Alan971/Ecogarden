<?php

namespace App\Controller;

use App\Handler\WeatherHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WeatherController extends AbstractController
{
    /**
     *  affiche les informations météo du lieu de résidencede l'utilisateur
     *
     * @param WeatherHandler $weatherHandler
     * @return JsonResponse
     */
    #[Route('api/meteo', name: 'app_weather', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function yourWeather(WeatherHandler $weatherHandler): JsonResponse
    {
        $currentUser = $this->getUser();
        $jsonResponse = $weatherHandler->yourWeather($currentUser->getInfoUser(), null, $this->getParameter('WEATHER_API_KEY'));
        return new JsonResponse($jsonResponse['message'], $jsonResponse['status']);
    }

    /**
     *  affiche les informations météo du lieu demandé par son code postal et son pays
     *
     * @param int $zipcode
     * @param WeatherHandler $weatherHandler
     * @return JsonResponse
     */
    #[Route('api/meteo/{zipcode}', name: 'app_weather_zipcode', methods: ['GET'])]
    public function zipcodeWeather(int $zipcode, WeatherHandler $weatherHandler): JsonResponse
    {
        $jsonResponse = $weatherHandler->yourWeather( null, $zipcode, $this->getParameter('WEATHER_API_KEY'));
        return new JsonResponse($jsonResponse['message'], $jsonResponse['status']);
    }
}
