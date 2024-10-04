<?php

namespace App\Controller;

use App\Handler\WeatherHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class WeatherController extends AbstractController
{
    /**
     *  affiche les informations météo du lieu de résidencede l'utilisateur
     *
     * @param WeatherHandler $weatherHandler
     * @return JsonResponse
     */
    #[Route('api/meteo', name: 'app_weather', methods: ['GET'])]
    public function yourWeather(WeatherHandler $weatherHandler, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $idCache = 'weather_' . time();
        $jsonResponse = $cachePool->get($idCache, function(ItemInterface $item) use ($weatherHandler)
        {
            $item->tag('weather');
            $jsonResponse = $weatherHandler->yourWeather($this->getUser()->getInfoUser(), null, $this->getParameter('WEATHER_API_KEY'));
            $item->expiresAfter(3600); //le cache dure 1 heure. il expire au bout du même tempe que le JWT
            return $jsonResponse;
        }
        );
        return new JsonResponse(['message' => $jsonResponse['message'], 'status' => $jsonResponse['status']], $jsonResponse['status']);
    }

    /**
     *  affiche les informations météo du lieu demandé par son code postal et son pays
     *
     * @param int $zipcode
     * @param WeatherHandler $weatherHandler
     * @return JsonResponse
     */
    #[Route('api/meteo/{zipcode}', name: 'app_weather_zipcode', methods: ['GET'])]
    public function zipcodeWeather(int $zipcode, WeatherHandler $weatherHandler, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $idCache = 'weather_'. time();
        $jsonResponse = $cachePool->get($idCache, function(ItemInterface $item) use ($weatherHandler, $zipcode)
        {
            $item->tag('weather');
            $jsonResponse = $weatherHandler->yourWeather( null, $zipcode, $this->getParameter('WEATHER_API_KEY'));
            $item->expiresAfter(3600); //le cache dure 1 heure. il expire au bout du même tempe que le JWT
            return $jsonResponse;
        }
        );
        return new JsonResponse(['message' => $jsonResponse['message'], 'status' => $jsonResponse['status']], $jsonResponse['status']);
    }
}
