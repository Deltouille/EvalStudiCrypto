<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Cryptocurrency;

class CryptoController extends AbstractController
{
    /**
     * @Route("/accueil", name="acueil")
     */
    public function index(): Response
    {
        $url = 'https://sandbox-api.coinmarketcap.com/v1/cryptocurrency/quotes/latest';
        $parameters = [
                'symbol' => 'BTC,ETH'
            ];
        $headers = [
                'Accepts: application/json',
                'X-CMC_PRO_API_KEY: b54bcf4d-1bca-4e8e-9a24-22ff2c3d462c',
            ];
        $qs = http_build_query($parameters); // query string encode the parameters
        $request = "{$url}?{$qs}"; // create the request URL
        $curl = curl_init(); // Get cURL resource
        // Set cURL options
        curl_setopt_array($curl, array(
                CURLOPT_URL => $request,            // set the request URL
                CURLOPT_HTTPHEADER => $headers,     // set the headers 
                CURLOPT_RETURNTRANSFER => 1         // ask for raw response instead of bool
            ));

        $response = curl_exec($curl); // Send the request, save the response
        curl_close($curl);
        //dd(json_encode(json_decode($response),JSON_PRETTY_PRINT));
        $em = $this->getDoctrine()->getManager();
        $cryptoRepository = $em->getRepository(Cryptocurrency::class);
        $listeCrypto = $cryptoRepository->findAll();
        return $this->render('crypto/accueil.html.twig', ['listeCrypto' => $listeCrypto]);
    }

    /**
     * @Route("/ajout", name="ajout")
     */
    public function ajout(): Response
    {

    }

    /**
     * @Route("/graph", name="graph")
     */
    public function graph(): Response
    {

    }

    /**
     * @Route("/suppression/{id}", name="suppression")
     */
    public function suppression(): Response
    {
    
    }
}
